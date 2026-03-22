<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Matriz de roles vs permisos
     */
    public function index()
    {
        $roles = Role::orderBy('name')->get();

        // Agrupar permisos por módulo (primer componente del nombre)
        $permissions = Permission::orderBy('name')->get()->groupBy(function ($p) {
            $parts = explode('.', $p->name);
            // Grupo por primeros 2 segmentos si existen
            return count($parts) >= 2 ? $parts[0] . '.' . $parts[1] : $parts[0];
        })->sortKeys();

        // Mapa: role_id → [permission_id, ...]
        $rolePerms = [];
        foreach ($roles as $role) {
            $rolePerms[$role->id] = $role->permissions->pluck('id')->flip()->all();
        }

        return view('admin.permissions.index', compact('roles', 'permissions', 'rolePerms'));
    }

    /**
     * Guardar cambios de permisos por rol
     */
    public function update(Request $request)
    {
        $roles = Role::where('name', '!=', 'Admin')->get();

        foreach ($roles as $role) {
            $before = $role->permissions->pluck('name')->sort()->values()->toArray();

            $ids = $request->input('perms.' . $role->id, []);
            $role->syncPermissions($ids);

            // Reload permissions after sync for accurate after state
            $role->refresh()->load('permissions');
            $after = $role->permissions->pluck('name')->sort()->values()->toArray();

            $added   = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            if (!empty($added) || !empty($removed)) {
                activity()
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'role'    => $role->name,
                        'added'   => $added,
                        'removed' => $removed,
                    ])
                    ->log("Permisos del rol '{$role->name}' actualizados");
            }
        }

        // Admin siempre tiene todos los permisos
        Role::where('name', 'Admin')->first()
            ?->syncPermissions(Permission::all());

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', 'Permisos actualizados correctamente.');
    }
}
