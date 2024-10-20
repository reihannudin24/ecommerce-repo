<?php

namespace App\Http\Controllers\Store;

use App\Helpers\ControllerHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\PivotUserInStore;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SebastianBergmann\Diff\Exception;

class StoreController extends Controller
{
    public function registered(Request $request){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'images' => 'nullable|file|mimes:jpg,jpeg,png',
            'description' => 'nullable',
            'address' => 'required',
            'type' => 'required',
            'category' => 'required',
            'password' => 'required',
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $hashPassword = Hash::make($validatedData['password']);

        try{
            // Store image if provided
            $imageUrl = $request->hasFile('images')
                ? Storage::url($request->file('images')->store('upload/store', 'public'))
                : null;

            $slug = Str::slug($validatedData['name'], '_');

            DB::transaction(function () use ($validatedData ,$hashPassword, $slug, $imageUrl, $user) {
                Store::create([
                   'name' => $validatedData['name'],
                   'slug' => $slug,
                   'image' => $imageUrl,
                   'description' => $validatedData['description'],
                   'rating' => 0,
                   'total_buyer' => 0,
                   'type' => $validatedData['type'],
                   'category' => $validatedData['category'],
                   'password' => $hashPassword,
                   'user_id' => $user->id,
                ]);
            });

            return ResponseHelper::responseJson(201, 'Successfully registered store', [], '/address');

        }catch (\Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/address');
        }
    }

    public function update(Request $request, $id){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'images' => 'nullable|file|mimes:jpg,jpeg,png',
            'description' => 'nullable',
            'address' => 'required',
            'type' => 'required',
            'category' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $pivotQuery = PivotUserInStore::query()->where('user_id', $user->id)->where('store_id' , $id)->first();
        if (!$pivotQuery) {
            return ResponseHelper::responseJson(401, 'user not in store', ['error' => 'user not in store'], '/use');
        }

        $store = Store::query()->where('id', $id)->first();
        if (!$store) {
            return ResponseHelper::responseJson(401, 'user not in store', ['error' => 'user not in store'], '/use');
        }

        try{

            $slug = Str::slug($validatedData['name'], '_');

            if ($request->hasFile('images')) {
                if ($store->image) {
                    $oldThumbnailPath = str_replace('/storage/', '', $store->image);
                    Storage::disk('public')->delete($oldThumbnailPath);
                }

                $imagePath = $request->file('images')->store('upload/store', 'public');
                $imageUrl = Storage::url($imagePath);
                $store->image = $imageUrl;
            }

            DB::transaction(function () use ($validatedData , $slug, $store, $user) {

                Store::query()->where('id', $store->id)->update([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'type' => $validatedData['type'],
                    'category' => $validatedData['category'],
                    'user_id' => $user->id,
                ]);
            });
            return ResponseHelper::responseJson(201, 'Successfully registered store', [], '/address');
        }catch (\Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/address');
        }
    }

    public function updateStatus(Request $request, $id){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'status' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $pivotQuery = PivotUserInStore::query()->where('user_id', $user->id)->where('store_id' , $id)->first();
        if (!$pivotQuery) {
            return ResponseHelper::responseJson(401, 'user not in store', ['error' => 'user not in store'], '/use');
        }

        $store = Store::query()->where('id', $id)->first();
        if (!$store) {
            return ResponseHelper::responseJson(401, 'user not in store', ['error' => 'user not in store'], '/use');
        }

        try{

            DB::transaction(function () use ($validatedData , $store) {

                Store::query()->where('id', $store->id)->update([
                    'status' => $validatedData['status'],
                ]);
            });
            return ResponseHelper::responseJson(201, 'Successfully registered store', [], '/address');
        }catch (\Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/address');
        }
    }

    public function show(Request $request , $id = null){
        $user = ControllerHelper::checkUserHasToken($request);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        if ($id) {
            $store = Store::where('id', $id)->where('user_id', $user->id)->first();
            if (!$store) {
                return ResponseHelper::responseJson(404, 'Store not found', [], '/show-address');
            }

            return ResponseHelper::responseJson(200, 'Address retrieved successfully', [
                'store' => $store
            ], '/show-address');
        } else {
            $addresses = Store::where('slug', )->get();
            return ResponseHelper::responseJson(200, 'Addresses retrieved successfully', [
                'addresses' => $addresses
            ], '/show-address');
        }
    }

    public function login(Request $request){
        $rules = [
            'email' => 'required|email',
            'store_email' => 'required',
            'password' => 'required'
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        if (!Hash::check($validatedData['password'], $store->password)) {
            return ResponseHelper::responseJson(401, 'Password not correct', [], '/register');
        }

        try {
            $token = $user->createToken('API Token Store for ' . $store->email)->plainTextToken;
            DB::transaction(function () use ($store, $user, $token) {
                PivotUserInStore::query()->where('user_id' , $user->id)->where('store_id', $store->id)->update([
                    'token' => $token
                ]);
            });

            return ResponseHelper::responseJson(201, 'Login to store successful', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                    'token' => $token
                ]
            ], '/dashboard');

        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function logout(Request $request){
        $rules = [
            'email' => 'required|email',
            'store_email' => 'required',
            'token' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        try {
            DB::transaction(function () use ($store, $user, $validatedData) {
                PivotUserInStore::query()->where('user_id' , $user->id)->where('store_id', $store->id)->where('token' , $validatedData['token'])->update([
                    'token' => null
                ]);
            });

            return ResponseHelper::responseJson(201, 'Logout to store successful', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                    'token' => null
                ]
            ], '/dashboard');

        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }
}
