<?php

namespace App\Http\Controllers;

use App\Helpers\ControllerHelper;
use App\Helpers\ResponseHelper;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:users,email',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $userCheckResponse = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);
        if ($userCheckResponse !== true) {
            return $userCheckResponse;
        }

        DB::beginTransaction();

        try {
            $user = User::create([
                'email' => $validatedData['email'],
            ]);

            DB::commit();
            return ResponseHelper::responseJson(201, 'Berhasil menambahkan pengguna', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ]
            ], '/verify-email');
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::responseJson(500, 'Registration failed: ' . $e->getMessage(), [], '/register');
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
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        if ($user->verify_code !== $validatedData['code']) {
            return ResponseHelper::responseJson(401, 'Verify code is not correct', [], '/verify-email');
        }

        try {
            DB::transaction(function () use ($user) {
                $user->update(['email_verify' => true]);
            });

            return ResponseHelper::responseJson(201, 'Email verified successfully', [
                'user' => [
                    'id' => $user->id,
                    'email_verify' => $user->email_verify
                ]
            ], '/add-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', [], '/verify-email');
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
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        if ($user->verify_code !== $validatedData['code']) {
            return ResponseHelper::responseJson(401, 'Verify code is not correct', [], '/verify-email');
        }

        try {
            DB::transaction(function () use ($user) {
                $user->update(['phone_number_verify' => true]);
            });
            return ResponseHelper::responseJson(201, 'Phone verified successfully', [
                'user' => [
                    'id' => $user->id,
                    'phone_number_verify' => $user->phone_number_verify
                ]
            ], '/add-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', [], '/verify-email');
        }
    }

    public function SendEmailCode(Request $request)
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

    public function SendPhoneNumberCode(Request $request)
    {
        $rules = [
            'phone_number' => 'required|digits_between:10,15', // Validates a typical phone number length
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
            $randomNumber = mt_rand(100000, 999999);
            DB::transaction(function () use ($randomNumber, $user) {
                $user->update(['code_phone_number' => $randomNumber]);
            });

            // Optionally, send the SMS/WhatsApp code here using an external service (e.g., Twilio, Textbelt)

            return ResponseHelper::responseJson(201, 'Phone number verification code sent successfully', [
                'user' => [
                    'id' => $user->id,
                    'phone_number_verify_code' => $user->code_phone_number // Returning the verification code
                ]
            ], '/add-password');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/verify-email');
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
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
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
            ], '/verify-email');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/verify-email');
        }
    }
    public function AddPhoneNumber(Request $request)
    {
        $rules = [
            'phone_number' => 'required',
            'session' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserBySessionOrRespond($validatedData['session']);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        try {
            DB::transaction(function () use ($user, $validatedData) {
                $user->update(['phone_number' => $validatedData['phone_number']]);
            });
            return ResponseHelper::responseJson(201, 'Phone number added successfully', [
                'user' => [
                    'id' => $user->id,
                    'phone_number' => $user->phone_number
                ]
            ], '/verify-email');
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
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

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
