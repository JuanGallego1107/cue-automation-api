<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    /**
     * Display a listing of roles with their permissions.
     */
    public function index(): AnonymousResourceCollection
    {
        $roles = Role::with('permissions')->get();

        return RoleResource::collection($roles);
    }

    /**
     * Display a single role.
     */
    public function show(Role $role): RoleResource
    {
        $role->load('permissions');

        return new RoleResource($role);
    }

    /**
     * Sync permissions to a specific role.
     */
    public function syncPermissions(Request $request, Role $role): RoleResource
    {
        $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        // Sync the relationships
        $role->permissions()->sync($request->input('permission_ids'));

        // Eager load relationships for the resource representation
        $role->load('permissions');

        return new RoleResource($role);
    }
}
