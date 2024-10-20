<?php

namespace App\Http\Controllers\Order;

use App\Helpers\ControllerHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Favorite;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function order(Request $request){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
          'total' => 'required',
          'address_id' => 'required',
          'payment_id' => 'required',
          'disc_id' => 'required',
          'user_id' => 'required',
          'checkouts.*.quantity' => 'required',
          'checkouts.*.status' => 'required',
          'checkouts.*.price' => 'required',
          'checkouts.*.total' => 'required',
          'checkouts.*.after_disc' => 'required',
          'checkouts.*.fee_shipping' => 'required',
          'checkouts.*.distance_shipping' => 'required',
          'checkouts.*.product_id' => 'required',
          'checkouts.*.user_id' => 'required',
          'checkouts.*.order_id' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        try{

            DB::transaction(function () use ($validatedData){
               $order =Order::query()->create([
                   'total' => 'required',
                   'total_disc' => 'required',
                   'status' => 'required',
                   'payment_id' => 'required',
                   'disc_id' => 'required',
                   'user_id' => 'required',
               ]);

               foreach ($validatedData['checkouts'] as $checkout){
                   Checkout::create([
                       'quantity' => 'required',
                       'status' => 'required',
                       'price' => 'required',
                       'total' => 'required',
                       'after_disc' => 'required',
                       'fee_shipping' => 'required',
                       'distance_shipping' => 'required',
                       'product_id' => 'required',
                       'user_id' => 'required',
                       'order_id' => 'required',
                   ]);
               }


                return ResponseHelper::responseJson(201, 'Login to store successful', [
                    'order' => $order
                ], '/dashboard');

            });

        }catch (\Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function show(Request $request, $id = null){

        $user = ControllerHelper::checkUserHasToken($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        if ($id) {
            $order = Order::where('id', $id)->where('user_id', $user->id)->first();
            if (!$order) {
                return ResponseHelper::responseJson(404, 'Address not found', [], '/show-address');
            }

            return ResponseHelper::responseJson(200, 'Address retrieved successfully', [
                'order' => $order
            ], '/show-address');
        } else {
            $order = Favorite::where('user_id', $user->id)->get();
            return ResponseHelper::responseJson(200, 'Addresses retrieved successfully', [
                'order' => $order
            ], '/show-address');
        }
    }

}
