<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles) :Response
    {
        $user = User::find(Auth::id());
        $user_roles = UserRole::where('user_id', $user->id)
            ->join('roles', 'roles.id', '=', 'role_id')
            ->pluck('roles.name')
            ->toArray();

        if (count(array_intersect($user_roles, $roles)) > 0) {
            return $next($request);
        }

        return response([
            'user_type' => 'Unauthorized',
            'status' => 401
        ], 401);

    }
}
