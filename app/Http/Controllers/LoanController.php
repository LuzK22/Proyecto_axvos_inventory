<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Branch;
use App\Models\Collaborator;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        // Auto-update overdue status for TI loans only
        Loan::forCategory('TI')->where('status', 'activo')->where('end_date', '<', now()->startOfDay())->update(['status' => 'vencido']);

        $filter = $request->get('filter', 'activo');
        $q = Loan::forCategory('TI')->with(['asset.type', 'collaborator.branch', 'creator']);

        match ($filter) {
            'vencido'  => $q->where('status', 'vencido'),
            'devuelto' => $q->where('status', 'devuelto'),
            'all'      => null,
            default    => $q->where('status', 'activo'),
        };

        if ($request->filled('collaborator')) {
            $s = $request->string('collaborator')->trim()->value();
            $q->whereHas('collaborator', fn($sq) => $sq->where('full_name', 'like', '%'.$s.'%'));
        }
        if ($request->filled('branch_id')) {
            $q->whereHas('collaborator', fn($sq) => $sq->where('branch_id', $request->branch_id));
        }

        $loans         = $q->orderByDesc('created_at')->paginate(20)->withQueryString();
        $activoCount   = Loan::forCategory('TI')->where('status','activo')->count();
        $vencidoCount  = Loan::forCategory('TI')->where('status','vencido')->count();
        $devueltoCount = Loan::forCategory('TI')->where('status','devuelto')->count();
        $branches      = Branch::where('active', true)->orderBy('name')->get();

        return view('tech.loans.index', compact('loans','filter','activoCount','vencidoCount','devueltoCount','branches'));
    }

    public function create()
    {
        $assets = Asset::with(['type','status'])
            ->whereHas('type', fn($q) => $q->where('category','TI'))
            ->whereHas('status', fn($q) => $q->where('name','like','%Disponible%'))
            ->orderBy('internal_code')->get();

        $collaborators = Collaborator::with('branch')->where('active', true)->orderBy('full_name')->get();

        return view('tech.loans.create', compact('assets','collaborators'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id'        => 'required|exists:assets,id',
            'collaborator_id' => 'required|exists:collaborators,id',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'notes'           => 'nullable|string|max:1000',
        ]);

        if (Loan::where('asset_id', $data['asset_id'])->whereIn('status',['activo','vencido'])->exists()) {
            return back()->withInput()->withErrors(['asset_id' => 'Este activo ya tiene un préstamo activo.']);
        }

        $loan = Loan::create([...$data, 'status' => 'activo', 'created_by' => Auth::id()]);

        $loanAsset = Asset::with('status')->find($data['asset_id']);
        AssetEvent::log($loanAsset, 'prestamo', $loanAsset->status?->name ?? 'Disponible', [
            'notes' => "Préstamo TI registrado. Vence: {$loan->end_date->format('d/m/Y')}.",
        ]);

        return redirect()->route('tech.loans.show', $loan)->with('success', 'Préstamo registrado correctamente.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['asset.type','asset.branch','collaborator.branch','creator','returnedBy']);
        return view('tech.loans.show', compact('loan'));
    }

    public function returnForm(Loan $loan)
    {
        if ($loan->status === 'devuelto') {
            return redirect()->route('tech.loans.show', $loan)->with('info', 'Este préstamo ya fue devuelto.');
        }
        $loan->load(['asset.type','collaborator']);
        return view('tech.loans.return', compact('loan'));
    }

    public function processReturn(Request $request, Loan $loan)
    {
        if ($loan->status === 'devuelto') {
            return redirect()->route('tech.loans.show', $loan);
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
            'notes' => "Devolución de préstamo TI #{$loan->id}." . ($request->notes ? " {$request->notes}" : ''),
        ]);

        return redirect()->route('tech.loans.index')->with('success', "Préstamo #{$loan->id} registrado como devuelto.");
    }

    public function export(Request $request)
    {
        $filter = $request->get('filter','all');
        $q = Loan::with(['asset.type','collaborator.branch','creator','returnedBy']);
        if ($filter === 'activo')   $q->where('status','activo');
        if ($filter === 'vencido')  $q->where('status','vencido');
        if ($filter === 'devuelto') $q->where('status','devuelto');

        $loans = $q->orderByDesc('created_at')->get();
        $headers = ['ID','Activo','Tipo','Colaborador','Cédula','Sucursal','Inicio','Vence','Devuelto','Estado','Notas','Creado por'];

        return response()->streamDownload(function () use ($loans, $headers) {
            $out = fopen('php://output','w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($loans as $l) {
                fputcsv($out, [
                    $l->id, $l->asset?->internal_code, $l->asset?->type?->name,
                    $l->collaborator?->full_name, $l->collaborator?->document,
                    $l->collaborator?->branch?->name,
                    $l->start_date?->format('d/m/Y'), $l->end_date?->format('d/m/Y'),
                    $l->returned_at?->format('d/m/Y H:i') ?? '',
                    $l->status, $l->notes, $l->creator?->name,
                ], ';');
            }
            fclose($out);
        }, 'prestamos_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
