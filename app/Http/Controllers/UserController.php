<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GESTIÓN DE USUARIOS — CRUD completo
    |--------------------------------------------------------------------------
    */

    /**
     * Listado de usuarios con búsqueda y filtro por rol.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'branch'])->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($r) => $r->where('name', $request->role));
        }

        $users    = $query->paginate(20)->withQueryString();
        $roles    = Role::orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'branches'));
    }

    /**
     * Formulario para crear usuario.
     */
    public function create()
    {
        $roles    = Role::orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();
        return view('users.create', compact('roles', 'branches'));
    }

    /**
     * Almacenar nuevo usuario.
     */
    public function store(Request $request)
    {
        // Auto-set username from email prefix if not provided
        if (empty($request->username) && $request->filled('email')) {
            $request->merge(['username' => explode('@', $request->email)[0]]);
        }

        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username'  => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username'],
            'role'      => ['required', 'exists:roles,name'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'password'  => [
                'required', 'confirmed',
                Password::min(10)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'name.required'     => 'El nombre es obligatorio.',
            'email.unique'      => 'Este correo ya está registrado.',
            'username.unique'   => 'Este nombre de usuario ya está en uso.',
            'role.exists'       => 'El rol seleccionado no existe.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $user = User::create([
            'name'               => $request->name,
            'email'              => $request->email,
            'username'           => $request->username,
            'password'           => Hash::make($request->password),
            'branch_id'          => $request->branch_id,
            'email_verified_at'  => now(),
            'password_changed_at'=> now(),
        ]);

        $user->assignRole($request->role);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'rol'      => $request->role,
                'email'    => $user->email,
                'sucursal' => $user->branch?->name,
            ])
            ->log("Usuario '{$user->name}' creado con rol {$request->role}");

        return redirect()->route('users.index')
            ->with('success', "Usuario '{$user->name}' creado correctamente.");
    }

    /**
     * Formulario de edición.
     */
    public function edit(User $user)
    {
        $roles    = Role::orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();
        return view('users.edit', compact('user', 'roles', 'branches'));
    }

    /**
     * Actualizar usuario.
     */
    public function update(Request $request, User $user)
    {
        // Auto-set username from email prefix if not provided
        if (empty($request->username) && $request->filled('email')) {
            $request->merge(['username' => explode('@', $request->email)[0]]);
        }

        $rules = [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'username'  => ['required', 'string', 'max:60', 'alpha_dash', "unique:users,username,{$user->id}"],
            'role'      => ['required', 'exists:roles,name'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = [
                'required', 'confirmed',
                Password::min(10)->mixedCase()->numbers()->symbols(),
            ];
        }

        $request->validate($rules, [
            'name.required'   => 'El nombre es obligatorio.',
            'email.unique'    => 'Este correo ya está registrado.',
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'role.exists'     => 'El rol seleccionado no existe.',
        ]);

        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'username'  => $request->username,
            'branch_id' => $request->branch_id,
        ];

        if ($request->filled('password')) {
            $data['password']           = Hash::make($request->password);
            $data['password_changed_at'] = now();
        }

        $rolAnterior = $user->roles->first()?->name ?? 'Sin rol';
        $user->update($data);
        $user->syncRoles([$request->role]);

        $props = ['email' => $user->email];
        if ($rolAnterior !== $request->role) {
            $props['rol_anterior'] = $rolAnterior;
            $props['rol_nuevo']    = $request->role;
        }
        if ($request->filled('password')) {
            $props['password_cambiada'] = true;
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties($props)
            ->log(
                $rolAnterior !== $request->role
                    ? "Rol de '{$user->name}' cambiado: {$rolAnterior} → {$request->role}"
                    : "Usuario '{$user->name}' actualizado"
            );

        return redirect()->route('users.index')
            ->with('success', "Usuario '{$user->name}' actualizado correctamente.");
    }

    /**
     * Revocar todas las sesiones activas de un usuario.
     * Útil para cerrar el acceso de un empleado suspendido de inmediato.
     */
    public function revokeAllSessions(User $user)
    {
        // Impedir que alguien revoque su propia sesión activa desde aquí
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Usa "Cerrar sesión" para terminar tu propia sesión.');
        }

        // Elimina todas las filas de sessions para este usuario (driver: database)
        DB::table('sessions')->where('user_id', $user->id)->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['action' => 'revoke_sessions'])
            ->log("Sesiones revocadas para {$user->name}");

        return back()->with('success', "Todas las sesiones de '{$user->name}' han sido cerradas.");
    }

    /**
     * Desbloquea manualmente una cuenta bloqueada por intentos fallidos.
     * Solo Admin puede realizar esta acción.
     */
    public function unlock(User $user)
    {
        $user->unlock();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Cuenta desbloqueada manualmente para {$user->name}");

        return back()->with('success', "La cuenta de '{$user->name}' ha sido desbloqueada.");
    }

    /**
     * Eliminar usuario.
     */
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return back()->withErrors(['No puedes eliminar tu propia cuenta.']);
        }

        // Prevent deleting last Admin
        if ($user->hasRole('Admin')) {
            $adminCount = User::role('Admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['No puedes eliminar el último administrador del sistema.']);
            }
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Usuario '{$name}' eliminado.");
    }
}
