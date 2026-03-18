<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'city' => 'required|string',
        ]);

        Branch::create($request->all());

        return redirect()->route('branches.index')
            ->with('success', 'Sucursal creada');
    }
}
