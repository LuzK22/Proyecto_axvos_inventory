<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\ActaSignature;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssignmentAsset;
use App\Models\Branch;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetTransitionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | RETIRO DE ASIGNACIÓN → Disponible
    | Quita el activo de la asignación actual y lo deja libre para reasignar
    |--------------------------------------------------------------------------
    */
    public function retire(Request $request, Asset $asset)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        // Buscamos el pivot activo (sin fecha de devolución)
        $pivot = AssignmentAsset::where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->latest()
            ->firstOrFail();

        $assignment = $pivot->assignment;

        DB::transaction(function () use ($asset, $pivot, $assignment, $request) {
            $availableStatus = Status::where('name', 'Disponible')->firstOrFail();

            // Marcamos la fila pivot como devuelta
            $pivot->update([
                'returned_at'  => now(),
                'return_notes' => $request->notes,
                'returned_by'  => auth()->id(),
            ]);

            AssetEvent::log($asset, 'devolucion', 'Disponible', [
                'assignment_id'   => $assignment->id,
                'collaborator_id' => $assignment->collaborator_id,
                'notes'           => $request->notes,
            ]);

            $asset->update(['status_id' => $availableStatus->id]);

            // Si todos los activos de la asignación fueron devueltos, la cerramos
            $assignment->refreshStatus();
        });

        return back()->with('success', "Activo {$asset->internal_code} retirado de la asignación y marcado como Disponible.");
    }

    /*
    |--------------------------------------------------------------------------
    | ENVIAR A MANTENIMIENTO
    |--------------------------------------------------------------------------
    */
    public function toMaintenance(Request $request, Asset $asset)
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        // Si estaba asignado lo desvinculamos antes de cambiar estado
        $this->detachFromAssignment($asset, $request->notes);

        $status = Status::where('name', 'Mantenimiento')->firstOrFail();

        AssetEvent::log($asset, 'mantenimiento', 'Mantenimiento', [
            'notes' => $request->notes,
        ]);

        $asset->update(['status_id' => $status->id]);

        return back()->with('success', "Activo {$asset->internal_code} enviado a Mantenimiento.");
    }

    /*
    |--------------------------------------------------------------------------
    | ENVIAR A GARANTÍA
    |--------------------------------------------------------------------------
    */
    public function toWarranty(Request $request, Asset $asset)
    {
        $request->validate([
            'notes'    => 'nullable|string|max:500',
            'provider' => 'nullable|string|max:200',
        ]);

        $this->detachFromAssignment($asset, $request->notes);

        $status = Status::where('name', 'En Garantía')->firstOrFail();

        // Concatenamos el proveedor en las notas para tener trazabilidad
        AssetEvent::log($asset, 'garantia', 'En Garantía', [
            'notes' => trim(($request->provider ? "Proveedor: {$request->provider}. " : '') . ($request->notes ?? '')),
        ]);

        $asset->update(['status_id' => $status->id]);

        return back()->with('success', "Activo {$asset->internal_code} enviado a Garantía.");
    }

    /*
    |--------------------------------------------------------------------------
    | TRASLADO DE SEDE
    | Cambia la sucursal del activo y lo pone "En Traslado" hasta confirmar llegada
    |--------------------------------------------------------------------------
    */
    public function transfer(Request $request, Asset $asset)
    {
        $request->validate([
            'to_branch_id' => 'required|exists:branches,id',
            'notes'        => 'nullable|string|max:500',
        ]);

        $this->detachFromAssignment($asset, $request->notes);

        $status = Status::where('name', 'En Traslado')->firstOrFail();

        DB::transaction(function () use ($asset, $request, $status) {
            $oldBranch = $asset->branch?->name ?? 'Sin sede';

            // Guardamos origen en las notas para saber de dónde venía
            AssetEvent::log($asset, 'traslado', 'En Traslado', [
                'to_branch_id' => $request->to_branch_id,
                'notes'        => "Desde: {$oldBranch}. " . ($request->notes ?? ''),
            ]);

            // La sede cambia de inmediato; el estado vuelve a Disponible cuando confirman llegada
            $asset->update([
                'status_id' => $status->id,
                'branch_id' => $request->to_branch_id,
            ]);
        });

        return back()->with('success', "Activo {$asset->internal_code} marcado En Traslado a la nueva sede.");
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR LLEGADA (En Traslado → Disponible)
    | El responsable en destino confirma que el activo llegó
    |--------------------------------------------------------------------------
    */
    public function arrivalConfirm(Request $request, Asset $asset)
    {
        $available = Status::where('name', 'Disponible')->firstOrFail();

        AssetEvent::log($asset, 'disponible', 'Disponible', [
            'notes' => 'Llegada confirmada tras traslado.',
        ]);

        $asset->update(['status_id' => $available->id]);

        return back()->with('success', "Activo {$asset->internal_code} confirmado como recibido. Ahora está Disponible.");
    }

    /*
    |--------------------------------------------------------------------------
    | DAR DE BAJA → genera Acta de Baja
    |--------------------------------------------------------------------------
    */
    public function toBaja(Request $request, Asset $asset)
    {
        $request->validate([
            'notes'  => 'required|string|max:1000',
            'reason' => 'required|in:danado,obsoleto,perdido,otro',
        ]);

        $this->detachFromAssignment($asset, $request->notes);

        $status = Status::where('name', 'Baja')->firstOrFail();

        $acta = DB::transaction(function () use ($asset, $request, $status) {
            AssetEvent::log($asset, 'baja', 'Baja', [
                'notes' => "Motivo: {$request->reason}. {$request->notes}",
            ]);

            $asset->update(['status_id' => $status->id]);

            // Si tuvo alguna asignación generamos el acta vinculada a ella
            $assignment = $asset->assignmentAssets()
                ->with('assignment')
                ->latest()
                ->first()?->assignment;

            if ($assignment) {
                $acta = Acta::create([
                    'assignment_id' => $assignment->id,
                    'acta_number'   => Acta::generateActaNumber('baja'),
                    'acta_type'     => Acta::TYPE_BAJA,
                    'status'        => Acta::STATUS_BORRADOR,
                    'generated_by'  => auth()->id(),
                    'notes'         => "Baja de activo {$asset->internal_code}. Motivo: {$request->reason}. {$request->notes}",
                ]);

                // Solo firma el responsable; el activo ya no está con ningún colaborador
                ActaSignature::create([
                    'acta_id'          => $acta->id,
                    'signer_role'      => 'responsible',
                    'signer_name'      => auth()->user()->name,
                    'signer_email'     => auth()->user()->email,
                    'signer_user_id'   => auth()->id(),
                    'token'            => ActaSignature::generateToken(),
                    'token_expires_at' => now()->addDays(30),
                ]);

                return $acta;
            }

            return null;
        });

        $msg = "Activo {$asset->internal_code} dado de Baja.";
        if ($acta) {
            return redirect()
                ->route('actas.show', $acta)
                ->with('success', $msg . ' Se generó el Acta de Baja.');
        }

        return back()->with('success', $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | DONACIÓN → genera Acta de Donación
    |--------------------------------------------------------------------------
    */
    public function toDonation(Request $request, Asset $asset)
    {
        $request->validate([
            'recipient' => 'required|string|max:300',
            'notes'     => 'nullable|string|max:1000',
        ]);

        $this->detachFromAssignment($asset, $request->notes);

        $status = Status::where('name', 'Donado')->firstOrFail();

        $acta = DB::transaction(function () use ($asset, $request, $status) {
            AssetEvent::log($asset, 'donacion', 'Donado', [
                'notes' => "Receptor: {$request->recipient}. {$request->notes}",
            ]);

            $asset->update(['status_id' => $status->id]);

            $assignment = $asset->assignmentAssets()->with('assignment')->latest()->first()?->assignment;

            if ($assignment) {
                $acta = Acta::create([
                    'assignment_id' => $assignment->id,
                    'acta_number'   => Acta::generateActaNumber('donacion'),
                    'acta_type'     => Acta::TYPE_DONACION,
                    'status'        => Acta::STATUS_BORRADOR,
                    'generated_by'  => auth()->id(),
                    'notes'         => "Donación a: {$request->recipient}. {$request->notes}",
                ]);

                ActaSignature::create([
                    'acta_id'          => $acta->id,
                    'signer_role'      => 'responsible',
                    'signer_name'      => auth()->user()->name,
                    'signer_email'     => auth()->user()->email,
                    'signer_user_id'   => auth()->id(),
                    'token'            => ActaSignature::generateToken(),
                    'token_expires_at' => now()->addDays(30),
                ]);

                return $acta;
            }

            return null;
        });

        $msg = "Activo {$asset->internal_code} marcado como Donado.";
        if ($acta) {
            return redirect()->route('actas.show', $acta)->with('success', $msg . ' Se generó el Acta de Donación.');
        }

        return back()->with('success', $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | VENTA → genera Acta de Venta
    |--------------------------------------------------------------------------
    */
    public function toSale(Request $request, Asset $asset)
    {
        $request->validate([
            'buyer'      => 'required|string|max:300',
            'sale_value' => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $this->detachFromAssignment($asset, $request->notes);

        $status = Status::where('name', 'Vendido')->firstOrFail();

        $acta = DB::transaction(function () use ($asset, $request, $status) {
            $valueNote = $request->sale_value ? " Valor: $" . number_format($request->sale_value, 2) . "." : '';

            AssetEvent::log($asset, 'venta', 'Vendido', [
                'notes' => "Comprador: {$request->buyer}.{$valueNote} {$request->notes}",
            ]);

            $asset->update(['status_id' => $status->id]);

            $assignment = $asset->assignmentAssets()->with('assignment')->latest()->first()?->assignment;

            if ($assignment) {
                $acta = Acta::create([
                    'assignment_id' => $assignment->id,
                    'acta_number'   => Acta::generateActaNumber('venta'),
                    'acta_type'     => Acta::TYPE_VENTA,
                    'status'        => Acta::STATUS_BORRADOR,
                    'generated_by'  => auth()->id(),
                    'notes'         => "Venta a: {$request->buyer}.{$valueNote} {$request->notes}",
                ]);

                ActaSignature::create([
                    'acta_id'          => $acta->id,
                    'signer_role'      => 'responsible',
                    'signer_name'      => auth()->user()->name,
                    'signer_email'     => auth()->user()->email,
                    'signer_user_id'   => auth()->id(),
                    'token'            => ActaSignature::generateToken(),
                    'token_expires_at' => now()->addDays(30),
                ]);

                return $acta;
            }

            return null;
        });

        $msg = "Activo {$asset->internal_code} marcado como Vendido.";
        if ($acta) {
            return redirect()->route('actas.show', $acta)->with('success', $msg . ' Se generó el Acta de Venta.');
        }

        return back()->with('success', $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER PRIVADO: desvincula el activo de su asignación activa si tiene una
    | Se llama antes de cualquier cambio de estado para no dejar activos huérfanos
    |--------------------------------------------------------------------------
    */
    private function detachFromAssignment(Asset $asset, ?string $notes = null): void
    {
        $pivot = AssignmentAsset::where('asset_id', $asset->id)
            ->whereNull('returned_at')
            ->latest()
            ->first();

        if ($pivot) {
            $pivot->update([
                'returned_at'  => now(),
                'return_notes' => $notes,
                'returned_by'  => auth()->id(),
            ]);

            // Revisamos si la asignación completa quedó sin activos activos
            $pivot->assignment->refreshStatus();
        }
    }
}
