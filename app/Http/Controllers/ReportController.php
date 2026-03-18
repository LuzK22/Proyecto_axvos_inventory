<?php

namespace App\Http\Controllers;

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
        return view('reports.global');
    }

    /**
     * Reportes TI
     */
    public function tech()
    {
        return view('tech.reports.hub');
    }

    /**
     * Reportes OTRO
     */
    public function assets()
    {
        return view('assets.reports.hub');
    }

    /**
     * Reportes genéricos
     */
    public function index()
    {
        return view('reports.index');
    }
}
