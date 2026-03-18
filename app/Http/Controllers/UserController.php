<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GESTIÓN DE USUARIOS
    |--------------------------------------------------------------------------
    | Este controlador se ampliará luego.
    | Por ahora solo evita errores de rutas
    */

    /**
     * Listado de usuarios
     */
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }

    /**
     * Formulario crear usuario
     */
    public function create()
    {
        return view('users.create');
    }
}
