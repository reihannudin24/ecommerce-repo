<?php

namespace App\Http\Controllers\User;

use App\Helpers\ControllerHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function create(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'phone_number' => 'required',
            'full_address' => 'required',
            'district' => 'required',
            'city' => 'required',
            'province' => 'required',
            'country' => 'required',
            'coordinate' => 'required',
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        try {
            DB::transaction(function () use ($validatedData, $user) {
                Address::create([
                    'name' => $validatedData['name'],
                    'phone_number' => $validatedData['phone_number'],
                    'full_address' => $validatedData['full_address'],
                    'district' => $validatedData['district'],
                    'city' => $validatedData['city'],
                    'province' => $validatedData['province'],
                    'country' => $validatedData['country'],
                    'coordinate' => $validatedData['coordinate'],
                    'user_id' => $user->id,
                ]);
            });

            // Return a success response if the address is created successfully
            return ResponseHelper::responseJson(201, 'Successfully created address', [], '/address');
        } catch (\Exception $e) {
            // Return a more detailed error response if an exception occurs
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/address');
        }
    }

    public function update(Request $request, $id)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }
        $rules = [
            'name' => 'sometimes|required',
            'phone_number' => 'sometimes|required',
            'full_address' => 'sometimes|required',
            'district' => 'sometimes|required',
            'city' => 'sometimes|required',
            'province' => 'sometimes|required',
            'country' => 'sometimes|required',
            'coordinate' => 'sometimes|required',
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/update-address');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $address = Address::where('id', $id)->where('user_id', $user->id)->first();
        if (!$address) {
            return ResponseHelper::responseJson(404, 'Address not found', [], '/update-address');
        }
        try {
            $address->update($validatedData);

            return ResponseHelper::responseJson(200, 'Address updated successfully', [
                'address' => $address
            ], '/update-address');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/update-address');
        }
    }

    public function delete(Request $request, $id)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $address = Address::where('id', $id)->where('user_id', $user->id)->first();
        if (!$address) {
            return ResponseHelper::responseJson(404, 'Address not found', [], '/delete-address');
        }
        try {
            $address->delete();
            return ResponseHelper::responseJson(200, 'Address deleted successfully', [], '/delete-address');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/delete-address');
        }
    }

    public function show(Request $request, $id = null)
    {
        $user = ControllerHelper::checkUserHasToken($request);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        if ($id) {
            $address = Address::where('id', $id)->where('user_id', $user->id)->first();
            if (!$address) {
                return ResponseHelper::responseJson(404, 'Address not found', [], '/show-address');
            }

            return ResponseHelper::responseJson(200, 'Address retrieved successfully', [
                'address' => $address
            ], '/show-address');
        } else {
            $addresses = Address::where('user_id', $user->id)->get();
            return ResponseHelper::responseJson(200, 'Addresses retrieved successfully', [
                'addresses' => $addresses
            ], '/show-address');
        }
    }


}
