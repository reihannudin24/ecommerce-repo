<?php

namespace App\Helpers;

use App\Models\PivotUserInStore;
use App\Models\Store;
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

    public static function checkUserHasToken(Request $request)
    {
        $user = $request->user();
        if ($user && !empty($user->remember_token)) {
            return $user;
        }

        return response()->json([
            'status' => 404,
            'message' => 'Token not found for the user.',
        ], 404);
    }



    public static function checkUserByEmailOrRespond(string $email)
    {
        $user = User::query()->where('email', $email)->first();
        if (!$user) {
            return ResponseHelper::responseJson(401, 'Email not registered', [], '/register');
        }
        return $user;
    }


    public static function checkStoreByEmailStoreAndUserPivot(string $email, int $user_id){
        $store = Store::query()->where('email' , $email)->first();
        $pivot = PivotUserInStore::query()->where('user_id' , $user_id)->where('store_id' ,$store->id)->first();

        if (!$pivot){
            return ResponseHelper::responseJson(401, 'You not a member of ', [], '/register');
        }

        if (!$store) {
            return ResponseHelper::responseJson(401, 'Store not there', [], '/register');
        }

        return $store;
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
