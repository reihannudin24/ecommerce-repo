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
    public function registered(Request $request)
    {
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

        $checkStore = Store::query()->where('user_id', $user->id)->first();
        if ($checkStore) {
            return ResponseHelper::responseJson(401, 'User already has a store', ['store' => $checkStore], '/store/show');
        }

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/store/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $hashPassword = Hash::make($validatedData['password']);

        try {
            $imageUrl = $request->hasFile('images')
                ? Storage::url($request->file('images')->store('upload/store', 'public'))
                : null;

            $slug = Str::slug($validatedData['name'], '_');

            DB::transaction(function () use ($validatedData, $hashPassword, $slug, $imageUrl, $user) {
                Store::create([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'image' => $imageUrl,
                    'description' => $validatedData['description'],
                    'address' => $validatedData['address'],
                    'rating' => 0,
                    'total_buyer' => 0,
                    'status' => 'active',
                    'type' => $validatedData['type'],
                    'category' => $validatedData['category'],
                    'password' => $hashPassword,
                    'user_id' => $user->id,
                ]);
            });

            $store = Store::query()->where('user_id', $user->id)->first();
            PivotUserInStore::create([
                'user_id' => $user->id,
                'store_id' => $store->id,
                'type' => 'owner',
            ]);

            return ResponseHelper::responseJson(201, 'Store registered successfully', ['store' => $store], '/store/show');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/store/register');
        }
    }

    public function update(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);

        $rules = [
            'id' => 'required',
            'name' => 'required',
            'images' => 'nullable|file|mimes:jpg,jpeg,png',
            'description' => 'nullable',
            'address' => 'required',
            'type' => 'required',
            'category' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/store/update');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $pivotQuery = PivotUserInStore::query()->where('user_id', $user->id)->where('store_id', $validatedData['id'])->first();
        if (!$pivotQuery) {
            return ResponseHelper::responseJson(403, 'Unauthorized access to store', ['error' => 'User not in store'], '/store/update');
        }

        $store = Store::find($validatedData['id']);
        if (!$store) {
            return ResponseHelper::responseJson(404, 'Store not found', ['error' => 'Store not found'], '/store/update');
        }

        try {
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

            DB::transaction(function () use ($validatedData, $slug, $store) {
                $store->update([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'type' => $validatedData['type'],
                    'category' => $validatedData['category'],
                    'address' => $validatedData['address'],
                ]);
            });

            return ResponseHelper::responseJson(200, 'Store updated successfully', [], '/store/show');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/store/update');
        }
    }

    public function updateStatus(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'status' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/status-update');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $pivotQuery = PivotUserInStore::query()->where('user_id', $user->id)->where('store_id', $validatedData['id'])->first();
        if (!$pivotQuery) {
            return ResponseHelper::responseJson(401, 'User is not associated with the store', ['error' => 'user not in store'], '/status-update');
        }

        $store = Store::query()->where('id', $validatedData['id'])->first();
        if (!$store) {
            return ResponseHelper::responseJson(404, 'Store not found', ['error' => 'store not found'], '/status-update');
        }

        try {
            DB::transaction(function () use ($validatedData, $store) {
                $store->update(['status' => $validatedData['status']]);
            });

            return ResponseHelper::responseJson(200, 'Store status updated successfully', [], '/status-update');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/status-update');
        }
    }

    public function show(Request $request, $id = null)
    {
        $user = ControllerHelper::checkUserHasToken($request);

        if ($id) {
            $store = Store::where('id', $id)->where('user_id', $user->id)->first();
            if (!$store) {
                return ResponseHelper::responseJson(404, 'Store not found', [], '/store-show');
            }

            return ResponseHelper::responseJson(200, 'Store details retrieved successfully', ['store' => $store], '/store-show');
        } else {
            $stores = Store::where('user_id', $user->id)->get();
            return ResponseHelper::responseJson(200, 'Stores retrieved successfully', ['stores' => $stores], '/store-list');
        }
    }


    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'store_email' => 'required',
            'password' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/login');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'], $user->id);
        if ($store instanceof \Illuminate\Http\JsonResponse) {
            return $store;
        }

        if (!Hash::check($validatedData['password'], $store->password)) {
            return ResponseHelper::responseJson(401, 'Incorrect password', [], '/login');
        }

        try {
            $token = $user->createToken('API Token Store for ' . $store->email)->plainTextToken;
            DB::transaction(function () use ($store, $user, $token) {
                PivotUserInStore::query()->where('user_id', $user->id)->where('store_id', $store->id)->update([
                    'token' => $token
                ]);
            });

            return ResponseHelper::responseJson(200, 'Login successful', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                    'token' => $token
                ]
            ], '/dashboard');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function logout(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'store_email' => 'required',
            'token' => 'required',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/logout');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $user = ControllerHelper::checkUserByEmailOrRespond($validatedData['email']);
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'], $user->id);
        if ($store instanceof \Illuminate\Http\JsonResponse) {
            return $store;
        }

        try {
            DB::transaction(function () use ($store, $user, $validatedData) {
                PivotUserInStore::query()->where('user_id', $user->id)->where('store_id', $store->id)->where('token', $validatedData['token'])->update([
                    'token' => null
                ]);
            });

            return ResponseHelper::responseJson(200, 'Logout successful', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                    'token' => null
                ]
            ], '/dashboard');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/logout');
        }
    }

}
