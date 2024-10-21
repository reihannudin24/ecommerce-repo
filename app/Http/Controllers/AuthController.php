<?php

namespace App\Http\Controllers;

use App\Helpers\ControllerHelper;
use App\Helpers\ResponseHelper;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function Register(Request $request)
    {
        $rules = ['email' => 'required|email|unique:users,email'];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) return $validatedData;

        ControllerHelper::checkUserByEmailRegitered($validatedData['email']);

        DB::beginTransaction();
        try {
            $sessionToken = Str::random(60);
            $user = User::create([
                'email' => $validatedData['email'],
                'session' => $sessionToken
            ]);
            DB::commit();
            return ResponseHelper::responseJson(201, 'User registered successfully', [
                'user' => ['id' => $user->id, 'email' => $user->email, 'session' => $user->session]
            ], '/verify-email');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::responseJson(500, 'Registration failed: ' . $e->getMessage(), [], '/register');
        }
    }
    public function SendEmailCode(Request $request)
    {
        $rules = ['email' => 'required|email'];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);

        try {
            DB::transaction(function () use ($user) {
                $verificationCode = mt_rand(100000, 999999);
                Mail::to($user->email)->send(new EmailVerificationMail($verificationCode));
                $user->update(['email_verification_code' => $verificationCode]);
            });

            return ResponseHelper::responseJson(201, 'Verification code sent successfully', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'email_verification_code' => $user->email_verification_code
                ]
            ], '/add-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/verify-email');
        }
    }
    public function VerifyEmail(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'code' => 'required',
            'session' => 'required'
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailAndSessionOrRespond($validatedData['email'], $validatedData['session']);
        if ($user->email_verification_code !== $validatedData['code']) {
            return ResponseHelper::responseJson(401, 'Email verify code is not correct', [], '/verify-email');
        }

        try {
            DB::transaction(function () use ($user) {
                $user->update([
                    'email_verify' => true,
                    'email_verified_at' => Carbon::now()
                ]);
            });

            return ResponseHelper::responseJson(201, 'Email verified successfully', [
                'user' => [
                    'id' => $user->id,
                    'email_verify' => $user->email_verify,
                    'email_verified_at' => $user->email_verified_at
                ]
            ], '/add-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', [], '/verify-email');
        }
    }
    public function SendPhoneNumberCode(Request $request)
    {
        $rules = [
            'email' => 'required|email', // Validates a typical phone number length
            'phone_number' => 'required|digits_between:10,15', // Validates a typical phone number length
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);

        try {
            $randomNumber = mt_rand(100000, 999999);
            DB::transaction(function () use ($randomNumber, $user, $validatedData) {
                $user->update([
                    'phone_number' => $validatedData['phone_number'],
                    'phone_number_verification_code' => $randomNumber
                ]);
            });

            return ResponseHelper::responseJson(201, 'Phone number verification code sent successfully', [
                'user' => [
                    'id' => $user->id,
                    'phone_number_verification_code' => $user->phone_number_verification_code
                ]
            ], '/add-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/verify-email');
        }
    }
    public function VerifyPhoneNumber(Request $request)
    {
        $rules = [
            'phone_number' => 'required',
            'code' => 'required',
            'session' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByPhoneAndSessionOrRespond($validatedData['phone_number'], $validatedData['session']);
        if ($user->phone_number_verification_code !== $validatedData['code']) {
            return ResponseHelper::responseJson(401, 'Phone number verify code is not correct', [], '/verify-email');
        }

        try {
            DB::transaction(function () use ($user) {
                $user->update([
                    'phone_number_verify' => true,
                    'phone_number_verified_at' => Carbon::now()
                ]);
            });
            return ResponseHelper::responseJson(201, 'Phone verified successfully', [
                'user' => [
                    'id' => $user->id,
                    'phone_number_verify' => $user->phone_number_verify,
                    'phone_number_verified_at' => $user->phone_number_verified_at
                ]
            ], '/add-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', [], '/verify-email');
        }
    }

    public function AddPassword(Request $request)
    {
        $rules = [
            'password' => 'required',
            'confirm_password' => 'required|same:password',
            'session' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserBySessionOrRespond($validatedData['session']);
        $hashedPassword = Hash::make($validatedData['password']);

        try {
            DB::transaction(function () use ($user, $hashedPassword) {
                $user->update(['password' => $hashedPassword]);
            });
            return ResponseHelper::responseJson(201, 'Password added successfully', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ]
            ], '/add-information');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/verify-email');
        }
    }

    public function AddInformation(Request $request)
    {
        $rules = [
            'firstname' => 'required',
            'lastname' => 'required',
            'username' => 'required',
            'session' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }
        $user = ControllerHelper::checkUserBySessionOrRespond($validatedData['session']);

        try {
            DB::transaction(function () use ($user, $validatedData) {
                $user->update([
                    'firstname' => $validatedData['firstname'],
                    'lastname' => $validatedData['lastname'],
                    'username' => $validatedData['username'],
                ]);
            });
            return ResponseHelper::responseJson(201, 'Information added successfully', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email
                ]
            ], '/verify-email');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/verify-email');
        }
    }

    public function Login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        if (!Hash::check($validatedData['password'], $user->password)) {
            return ResponseHelper::responseJson(401, 'Password not correct', [], '/register');
        }

        try {
            DB::transaction(function () use ($user) {
                $token = $user->createToken('API Token for ' . $user->email)->plainTextToken;
                $user->update(['remember_token' => $token]);
                Auth::login($user);
            });
            return ResponseHelper::responseJson(201, 'Login successful', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'token' => $user->remember_token
                ]
            ], '/dashboard');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function Logout(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        try {
            DB::transaction(function () use ($user, $request) {
                $request->user()->currentAccessToken()->delete();
                User::query()->where('id', $user->id)->update([
                    'remember_token' => null
                ]);
            });
            return ResponseHelper::responseJson(201, 'Logout successful', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'token' => $user->null
                ]
            ], '/dashboard');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function ForgotPassword(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        try {
            $resetToken = Str::random(60);
            $user->update(['reset_token' => $resetToken, 'token_expires_at' => now()->addMinutes(30)]); // Token expires in 30 minutes
            Mail::to($user->email)->send(new PasswordResetMail($resetToken));

            return ResponseHelper::responseJson(200, 'Password reset email sent successfully', [], '/reset-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/reset-password');
        }
    }

}
