<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\LeaveImpersonationAction;
use App\Actions\Admin\StartImpersonationAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ImpersonationToken;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly StartImpersonationAction $startAction,
        private readonly LeaveImpersonationAction $leaveAction
    ) {
    }

    public function start(Request $request, User $user)
    {
        $result = $this->startAction->execute(
            $request->user(),
            $request->user()->currentAccessToken(),
            $user,
            $request->ip(),
        );

        return response()->json([
            'access_token' => $result['access_token'],
            'token_type'   => 'Bearer',
            'user'         => $result['user'],
            'impersonation' => [
                'admin'      => $result['admin'],
                'started_at' => $result['started_at'],
                'expires_at' => $result['expires_at'],
            ],
        ]);
    }

    public function leave(Request $request)
    {
        $result = $this->leaveAction->execute(
            $request->user(),
            $request->user()->currentAccessToken(),
            $request->ip(),
        );

        return response()->json([
            'access_token' => $result['access_token'],
            'token_type'   => 'Bearer',
            'user'         => $result['admin'],
        ]);
    }

    public function whoami(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        $adminId = ImpersonationToken::decode($token->name);

        $impersonation = null;
        if ($adminId !== null) {
            $admin = User::find($adminId);
            $impersonation = [
                'admin'      => $admin ? ['id' => $admin->id, 'name' => $admin->name] : null,
                'expires_at' => $token->expires_at?->toISOString(),
            ];
        }

        $user = $request->user();
        $user->setAttribute('impersonation', $impersonation);

        return response()->json($user);
    }
}
