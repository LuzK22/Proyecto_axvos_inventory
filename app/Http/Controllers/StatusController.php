<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index()
    {
        $statuses = Status::withCount('assets')->orderBy('name')->get();
        return view('admin.statuses.index', compact('statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:60|unique:statuses,name',
            'color' => 'required|string|max:30',
        ], [
            'name.unique' => 'Ya existe un estado con ese nombre.',
        ]);

        Status::create($data);
        return back()->with('success', 'Estado creado.');
    }

    public function update(Request $request, Status $status)
    {
        $data = $request->validate([
            'name'  => "required|string|max:60|unique:statuses,name,{$status->id}",
            'color' => 'required|string|max:30',
        ], [
            'name.unique' => 'Ya existe un estado con ese nombre.',
        ]);

        $status->update($data);
        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(Status $status)
    {
        if ($status->assets()->count() > 0) {
            return back()->withErrors(['Este estado tiene activos asociados y no puede eliminarse.']);
        }

        $status->delete();
        return back()->with('success', 'Estado eliminado.');
    }
}
