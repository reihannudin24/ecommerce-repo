<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ControllerHelper {
    public static function validateRequest(Request $request, array $rules, int $status = 422, string $message = 'Validation Error', string $link = '') {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ResponseHelper::responseJson($status, $message, $validator->errors(), $link);
        }
        return $validator->validated();
    }

    public static function checkUserByEmailOrRespond(string $email)
    {
        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return ResponseHelper::responseJson(401, 'Email not registered', [], '/register');
        }
        return $user;
    }

    public static function checkUserByEmailAndSessionOrRespond(string $email, string $session)
    {
        $user = User::query()->where('email', $email)->where('session', $session)->first();
        if (!$user) {
            return ResponseHelper::responseJson(401, 'Invalid email or session', [], '/register');
        }
        return $user;
    }

    public static function checkUserByPhoneAndSessionOrRespond(string $phoneNumber, string $session)
    {
        $user = User::query()->where('phone_number', $phoneNumber)->where('session', $session)->first();
        if (!$user) {
            return ResponseHelper::responseJson(401, 'Invalid phone number or session', [], '/register');
        }
        return $user;
    }

    public static function checkUserBySessionOrRespond(string $session)
    {
        $user = User::query()->where('session', $session)->first();
        if (!$user) {
            return ResponseHelper::responseJson(401, 'Invalid session', [], '/register');
        }
        return $user;
    }


}
