<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        $user = User::find($request->id);

        if ($user->hasVerifiedEmail()) {
            return response(['message' => 'Already confirmed Email.'], 422);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
        return response(['message' => 'Email verification done.'], 200);
    }
}
