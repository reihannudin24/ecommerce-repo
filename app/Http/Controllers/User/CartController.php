<?php

namespace App\Http\Controllers\User;

use App\Helpers\ControllerHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{

    public function add(Request $request){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'product_id' => 'required',
            'quantity' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if(!is_array($validatedData)){
            return $validatedData;
        }

        try{
            DB::transaction(function () use($validatedData, $user) {
                Cart::create([
                    'product_id' => $validatedData['product_id'],
                    'quantity' => $validatedData['quantity'],
                    'user_id' =>  $user->id
                ]);
            });

            return ResponseHelper::responseJson(201, 'Successfully created address', [], '/address');
        }catch (\Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/address');
        }
    }

    public function delete(Request $request){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'id' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if(!is_array($validatedData)){
            return $validatedData;
        }

        try{
            DB::transaction(function () use($validatedData) {
                Cart::query()->where('id' , $validatedData['id'])->delete();
            });

            return ResponseHelper::responseJson(201, 'Successfully created address', [], '/address');
        }catch (\Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/address');
        }
    }

    public function show(Request $request, $id = null){
        $user = ControllerHelper::checkUserHasToken($request);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        if ($id) {
            $favorite = Favorite::where('id', $id)->where('user_id', $user->id)->first();
            if (!$favorite) {
                return ResponseHelper::responseJson(404, 'Address not found', [], '/show-address');
            }

            return ResponseHelper::responseJson(200, 'Address retrieved successfully', [
                'favorite' => $favorite
            ], '/show-address');
        } else {
            $favorite = Favorite::where('user_id', $user->id)->get();
            return ResponseHelper::responseJson(200, 'Addresses retrieved successfully', [
                'favorite' => $favorite
            ], '/show-address');
        }
    }

    public function update(Request $request){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'id' => 'required',
            'type' => 'required'
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if(!is_array($validatedData)){
            return $validatedData;
        }

        $cart = Cart::query()->where('id' , $validatedData['id'])->first();
        if(!$cart){
            return ResponseHelper::responseJson(401, 'Cart not found', [], '/register');
        }

        try{
            DB::transaction(function () use($validatedData, $cart, $user) {

                if ($validatedData['type'] === 'inc'){
                    Cart::query()->where('id' , $validatedData['id'])->update([
                       'quantity' => $cart->quantity += 1
                    ]);
                }else{
                    Cart::query()->where('id' , $validatedData['id'])->update([
                        'quantity' => $cart->quantity -= 1
                    ]);
                }

            });

            return ResponseHelper::responseJson(201, 'Successfully created address', [], '/address');
        }catch (\Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/address');
        }
    }

}
