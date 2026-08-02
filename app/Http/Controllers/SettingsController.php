<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class SettingsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $canManageUsers = $actor->can('viewAny', User::class);
        $canManageRoles = $actor->can('viewAny', Role::class);
        abort_unless($canManageUsers || $canManageRoles || $actor->hasPermission('settings.manage'), 403);
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $roleId = $request->integer('role');

        $users = $canManageUsers ? User::query()
            ->with('roles:id,name,display_name')
            ->withCount('diagnosticTestRuns')
            ->addSelect(['last_activity' => DB::table('sessions')->selectRaw('max(last_activity)')->whereColumn('user_id', 'users.id')])
            ->when($search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery->where('name', 'ilike', "%{$search}%")->orWhere('email', 'ilike', "%{$search}%")))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($roleId > 0, fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->whereKey($roleId)))
            ->orderBy('name')->paginate(25)->withQueryString() : null;

        return Inertia::render('Settings/Index', [
            'users' => $users,
            'roles' => Role::query()->with('permissions:id,name,display_name')->withCount('users')->orderBy('display_name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get()->map(fn (Permission $permission): array => [
                'id' => $permission->id, 'name' => $permission->name, 'display_name' => $permission->display_name,
                'group' => str($permission->name)->before('.')->headline()->toString(),
            ])->groupBy('group'),
            'filters' => ['search' => $search, 'status' => $status, 'role' => $roleId ?: null],
            'canManageUsers' => $canManageUsers,
            'canManageRoles' => $canManageRoles,
            'currentUserId' => $actor->public_id,
        ]);
    }
}
