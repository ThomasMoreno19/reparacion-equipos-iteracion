<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use App\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $roles = array_values(array_filter(array_map(
            static fn (string $role): ?Role => Role::tryFrom($role),
            $allowedRoles,
        )));
        $user = $request->user();

        abort_unless($user instanceof Usuario && $user->hasRole(...$roles), 403);

        return $next($request);
    }
}
