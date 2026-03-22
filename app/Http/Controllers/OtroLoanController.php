<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Branch;
use App\Models\Collaborator;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtroLoanController extends Controller
{
    public function index(Request $request)
    {
        // Auto-update overdue status for OTRO loans
        Loan::forCategory('OTRO')
            ->where('status', 'activo')
            ->where('end_date', '<', now()->startOfDay())
            ->update(['status' => 'vencido']);

        $filter = $request->get('filter', 'activo');
        $q = Loan::forCategory('OTRO')
            ->with(['asset.type', 'collaborator.branch', 'destinationBranch', 'creator']);

        match ($filter) {
            'vencido'  => $q->where('status', 'vencido'),
            'devuelto' => $q->where('status', 'devuelto'),
            'all'      => null,
            default    => $q->where('status', 'activo'),
        };

        if ($request->filled('collaborator')) {
            $s = $request->string('collaborator')->trim()->value();
            $q->where(function ($sq) use ($s) {
                $sq->whereHas('collaborator', fn($cq) => $cq->where('full_name', 'like', '%'.$s.'%'))
                   ->orWhereHas('destinationBranch', fn($bq) => $bq->where('name', 'like', '%'.$s.'%'));
            });
        }
        if ($request->filled('branch_id')) {
            $q->where(function ($sq) use ($request) {
                $sq->whereHas('collaborator', fn($cq) => $cq->where('branch_id', $request->branch_id))
                   ->orWhere('destination_branch_id', $request->branch_id);
            });
        }

        $loans         = $q->orderByDesc('created_at')->paginate(20)->withQueryString();
        $activoCount   = Loan::forCategory('OTRO')->where('status', 'activo')->count();
        $vencidoCount  = Loan::forCategory('OTRO')->where('status', 'vencido')->count();
        $devueltoCount = Loan::forCategory('OTRO')->where('status', 'devuelto')->count();
        $branches      = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.loans.index', compact('loans', 'filter', 'activoCount', 'vencidoCount', 'devueltoCount', 'branches'));
    }

    public function create()
    {
        $assets = Asset::with(['type', 'status'])
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->whereHas('status', fn($q) => $q->where('name', 'like', '%Disponible%'))
            ->orderBy('internal_code')
            ->get();

        $collaborators = Collaborator::with('branch')->where('active', true)->orderBy('full_name')->get();
        $branches      = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.loans.create', compact('assets', 'collaborators', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id'              => 'required|exists:assets,id',
            'destination_type'      => 'required|in:collaborator,branch',
            'collaborator_id'       => 'required_if:destination_type,collaborator|nullable|exists:collaborators,id',
            'destination_branch_id' => 'required_if:destination_type,branch|nullable|exists:branches,id',
            'start_date'            => 'required|date',
            'end_date'              => 'required|date|after_or_equal:start_date',
            'notes'                 => 'nullable|string|max:1000',
        ]);

        // Verify no active loan for this asset
        if (Loan::where('asset_id', $request->asset_id)->whereIn('status', ['activo', 'vencido'])->exists()) {
            return back()->withInput()->withErrors(['asset_id' => 'Este activo ya tiene un préstamo activo.']);
        }

        $loan = Loan::create([
            'category'              => 'OTRO',
            'asset_id'              => $request->asset_id,
            'collaborator_id'       => $request->destination_type === 'collaborator' ? $request->collaborator_id : null,
            'destination_type'      => $request->destination_type,
            'destination_branch_id' => $request->destination_type === 'branch' ? $request->destination_branch_id : null,
            'start_date'            => $request->start_date,
            'end_date'              => $request->end_date,
            'notes'                 => $request->notes,
            'status'                => 'activo',
            'created_by'            => Auth::id(),
        ]);

        $loanAsset = Asset::with('status')->find($request->asset_id);
        AssetEvent::log($loanAsset, 'prestamo', $loanAsset->status?->name ?? 'Disponible', [
            'notes' => "Préstamo OTRO registrado. Vence: {$loan->end_date->format('d/m/Y')}.",
        ]);

        return redirect()->route('assets.loans.show', $loan)->with('success', 'Préstamo registrado correctamente.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['asset.type', 'asset.branch', 'collaborator.branch', 'destinationBranch', 'creator', 'returnedBy']);
        return view('assets.loans.show', compact('loan'));
    }

    public function returnForm(Loan $loan)
    {
        if ($loan->status === 'devuelto') {
            return redirect()->route('assets.loans.show', $loan)->with('info', 'Este préstamo ya fue devuelto.');
        }
        $loan->load(['asset.type', 'collaborator', 'destinationBranch']);
        return view('assets.loans.return', compact('loan'));
    }

    public function processReturn(Request $request, Loan $loan)
    {
        if ($loan->status === 'devuelto') {
            return redirect()->route('assets.loans.show', $loan);
        }
        $request->validate(['notes' => 'nullable|string|max:1000']);
        $loan->update([
            'status'      => 'devuelto',
            'returned_at' => now(),
            'returned_by' => Auth::id(),
            'notes'       => $request->notes ?: $loan->notes,
        ]);

        $loan->load('asset.status');
        AssetEvent::log($loan->asset, 'devolucion', $loan->asset->status?->name ?? 'Disponible', [
            'notes' => "Devolución de préstamo OTRO #{$loan->id}." . ($request->notes ? " {$request->notes}" : ''),
        ]);

        return redirect()->route('assets.loans.index')->with('success', "Préstamo #{$loan->id} registrado como devuelto.");
    }

    public function export(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $q = Loan::forCategory('OTRO')
            ->with(['asset.type', 'collaborator.branch', 'destinationBranch', 'creator', 'returnedBy']);
        if ($filter === 'activo')   $q->where('status', 'activo');
        if ($filter === 'vencido')  $q->where('status', 'vencido');
        if ($filter === 'devuelto') $q->where('status', 'devuelto');

        $loans   = $q->orderByDesc('created_at')->get();
        $headers = ['ID', 'Activo', 'Tipo', 'Destino', 'Colaborador/Sucursal', 'Cédula', 'Sucursal Activo', 'Sucursal Destino', 'Inicio', 'Vence', 'Devuelto', 'Estado', 'Notas', 'Creado por'];

        return response()->streamDownload(function () use ($loans, $headers) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($loans as $l) {
                $destinoLabel = $l->destination_type === 'branch'
                    ? ($l->destinationBranch?->name ?? '—')
                    : ($l->collaborator?->full_name ?? '—');
                fputcsv($out, [
                    $l->id,
                    $l->asset?->internal_code,
                    $l->asset?->type?->name,
                    $l->destination_type === 'branch' ? 'Sucursal' : 'Colaborador',
                    $destinoLabel,
                    $l->collaborator?->document ?? '—',
                    $l->asset?->branch?->name ?? '—',
                    $l->destinationBranch?->name ?? '—',
                    $l->start_date?->format('d/m/Y'),
                    $l->end_date?->format('d/m/Y'),
                    $l->returned_at?->format('d/m/Y H:i') ?? '',
                    $l->status,
                    $l->notes,
                    $l->creator?->name,
                ], ';');
            }
            fclose($out);
        }, 'prestamos_otros_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
