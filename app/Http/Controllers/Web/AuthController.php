<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\Controller;
use App\Jobs\SendWelcomeMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\InfobipService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function __construct()
    {
    }

    public function verifyEmail($token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            //@todo: make home page:::
            //@todo: make validation failure or invalid token page
            //@todo: make vvalidation success mail
            return redirect()->route('home')->with('error', 'Invalid verification token.');
        }

        if (Carbon::now()->greaterThan($user->email_verification_expiry)) {
            return redirect()->route('home')->with('error', 'Verification link has expired. Please request a new one.');
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->email_verification_expiry = null;
        $user->save();

        return redirect()->route('home')->with('success', 'Your email has been successfully verified!');
    }

    public function resendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return back()->with('info', 'Your email is already verified.');
        }

        $expiryDate = Carbon::now()->addHours(24);
        $user->email_verification_token = Str::random(64);
        $user->email_verification_expiry = $expiryDate;
        $user->save();

        Mail::to($user->email)->send(new WelcomeMail($user->email_verification_token, $expiryDate));

        return back()->with('success', 'A new verification email has been sent!');
    }


}
