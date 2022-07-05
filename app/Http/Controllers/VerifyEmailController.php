<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\User;
use App\Services\UserService;

class VerifyEmailController extends Controller
{
    private $userService;
        /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(UserService $userService){
        $this->userService = $userService;
    }
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::find($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONT_URL') . '/email/verify/already-success');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect(env('FRONT_URL') . '/email/verify/success');
    }

    public function verifyEmail($id, $hash) {
        $user = User::find($id);
        abort_if(!$user, 403);
        abort_if(!hash_equals($hash, sha1($user->getEmailForVerification())), 403);
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }
        return ['message'=> 'OK.'];
    }

    public function resendNotification(Request $request) {
        $user = $this->userService->getUserByEmail($request['email']);
        if(!$user) {
            return response(['error' => 'User not found'],400);
        } else{
            $user->sendEmailVerificationNotification();
            return ['message'=> 'OK.'];
        }

    }
}
