<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\UserLifecycleService;
use App\Support\Auth\CmsPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivateAccountController extends Controller
{
    public function __construct(private readonly UserLifecycleService $lifecycle) {}

    public function show(string $token): View|RedirectResponse
    {
        $user = $this->lifecycle->findPendingByInvitationToken($token);

        if ($user === null) {
            return redirect()
                ->route('filament.admin.auth.login')
                ->withErrors(['email' => 'This invitation link is invalid or has expired.']);
        }

        return view('auth.activate', [
            'token' => $token,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $user = $this->lifecycle->findPendingByInvitationToken($token);

        if ($user === null) {
            return redirect()
                ->route('filament.admin.auth.login')
                ->withErrors(['email' => 'This invitation link is invalid or has expired.']);
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                CmsPassword::rules(),
            ],
        ]);

        $this->lifecycle->activate($user, $token, $validated['password']);

        return redirect()
            ->route('filament.admin.auth.login')
            ->with('status', 'Your account is active. You can sign in now.');
    }
}
