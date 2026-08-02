<?php

namespace App\Services\Identity;

use App\Models\User;
use App\Support\RegistryAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UserAdministrationService
{
    public function __construct(private readonly RegistryAudit $audit) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $roleIds
     */
    public function create(array $data, array $roleIds, User $actor): User
    {
        return DB::transaction(function () use ($data, $roleIds, $actor): User {
            $user = User::query()->create([
                'name' => trim((string) $data['name']),
                'email' => mb_strtolower(trim((string) $data['email'])),
                'password' => Hash::make((string) $data['password']),
                'email_verified_at' => now(),
                'is_active' => (bool) $data['is_active'],
            ]);
            $user->roles()->sync($roleIds);
            $this->audit->record('identity.user.created', $user, $actor, [
                'name' => $user->name, 'email' => $user->email, 'role_ids' => $roleIds,
            ]);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>|null  $roleIds
     */
    public function update(User $user, array $data, ?array $roleIds, User $actor): void
    {
        DB::transaction(function () use ($user, $data, $roleIds, $actor): void {
            $before = ['name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active, 'role_ids' => $user->roles()->pluck('roles.id')->all()];
            $nextActive = (bool) $data['is_active'];
            if (! $nextActive && $user->is($actor)) {
                throw ValidationException::withMessages(['is_active' => 'Das eigene Konto kann nicht deaktiviert werden.']);
            }
            if (! $nextActive && $user->is_active) {
                $this->assertNotLastAdministrator($user);
            }
            if ($roleIds !== null && $user->is_active && $this->removesSystemAdministrator($user, $roleIds)) {
                $this->assertNotLastAdministrator($user);
            }
            $user->update([
                'name' => trim((string) $data['name']),
                'email' => mb_strtolower(trim((string) $data['email'])),
                'is_active' => $nextActive,
            ]);
            if ($roleIds !== null) {
                $user->roles()->sync($roleIds);
            }
            if (! $nextActive) {
                $this->revokeSessions($user);
            }
            $after = ['name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active, 'role_ids' => $user->roles()->pluck('roles.id')->all()];
            $changed = array_keys(array_filter($after, fn (mixed $value, string $key): bool => $value !== $before[$key], ARRAY_FILTER_USE_BOTH));
            $this->audit->record($nextActive ? 'identity.user.updated' : 'identity.user.deactivated', $user, $actor, [
                'changed_fields' => $changed, 'before' => array_intersect_key($before, array_flip($changed)),
                'after' => array_intersect_key($after, array_flip($changed)),
            ]);
        });
    }

    public function resetPassword(User $user, string $password, User $actor): void
    {
        DB::transaction(function () use ($user, $password, $actor): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => null])->save();
            $revoked = $this->revokeSessions($user);
            $this->audit->record('identity.user.password_reset', $user, $actor, ['revoked_sessions' => $revoked]);
        });
    }

    public function revokeSessions(User $user): int
    {
        return DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function assertNotLastAdministrator(User $user): void
    {
        $otherExists = User::query()->whereKeyNot($user->id)->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'system-administrator'))->exists();
        if (! $otherExists) {
            throw ValidationException::withMessages(['role_ids' => 'Der letzte aktive Systemadministrator muss erhalten bleiben.']);
        }
    }

    /** @param list<int> $roleIds */
    private function removesSystemAdministrator(User $user, array $roleIds): bool
    {
        $adminRole = $user->roles()->where('name', 'system-administrator')->first();

        return $adminRole !== null && ! in_array($adminRole->id, $roleIds, true);
    }
}
