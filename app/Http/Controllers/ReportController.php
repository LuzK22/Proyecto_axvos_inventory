<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REPORTES GENERALES
    |--------------------------------------------------------------------------
    | Este controlador se usará más adelante para:
    | - Reportes de activos TI
    | - Reportes de otros activos
    | - Reportes de bajas
    | - Reportes globales (auditoría)
    |
    | Por ahora solo devolvemos vistas simples
    */

    /**
     * Reportes generales (auditoría)
     */
    public function global()
    {
        // Vista temporal
        return view('reports.global');
    }

    /**
     * Reportes normales
     */
    public function index()
    {
        // Vista temporal
        return view('reports.index');
    }
}

