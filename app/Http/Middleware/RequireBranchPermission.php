<?php

namespace App\Http\Middleware;

use App\Models\BranchUserPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireBranchPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $branchId = (int) ($request->input('branch_id') ?? $request->input('from_branch_id') ?? $request->route('branch'));

        if (! $user || $branchId <= 0) {
            return response()->json(['error' => 'Authenticated user and branch_id are required'], 401);
        }

        $allowed = BranchUserPermission::query()
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->where('permission', $permission)
            ->exists();

        if (! $allowed) {
            return response()->json(['error' => "Forbidden: missing {$permission} on branch {$branchId}"], 403);
        }

        return $next($request);
    }
}
