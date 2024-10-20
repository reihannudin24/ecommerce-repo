<?php

namespace App\Http\Controllers\Store;

use App\Helpers\ControllerHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\PivotUserInStore;
use App\Models\Product;
use App\Models\Store;
use App\Models\TypeProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nette\Utils\Type;
use SebastianBergmann\Diff\Exception;

class ProductController extends Controller
{

    public function create(Request $request){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required',
            'image' => 'required',
            'store_id' => 'required',
            'store_email' => 'required',
            'password' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        try {
            $imageUrl = $request->hasFile('images') ?
                Storage::url($request->file('images')->store('upload/product' , 'public'))
                : null;
            $slug = Str::slug($validatedData['name'], '_');
            DB::transaction(function () use ($store, $user, $validatedData, $slug, $imageUrl) {
                Product::create([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                    'image' => $imageUrl,
                    'unique_id' => $validatedData['unique_id'],
                    'store_id' => $validatedData['store_id'],
                ]);
            });

            return ResponseHelper::responseJson(201, 'Login to store successful', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                ]
            ], '/dashboard');
        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function update(Request $request, $id){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required',
            'image' => 'required',
            'store_id' => 'required',
            'store_email' => 'required',
            'password' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        $products = Product::query()->where('id' , $id)->first();
        if (!$products){
            return ResponseHelper::responseJson(401, 'product not found ', [], '/register');
        }

        try {
            $slug = Str::slug($validatedData['name'], '_');

            if ($request->hasFile('images')) {
                if ($products->image){
                    $oldThumbnailPath = str_replace('/storage/' , '', $products->image);
                    Storage::disk('public')->delete($oldThumbnailPath);
                }

                $imagePath = $request->file('images')->store('upload/product', 'public');
                $imageUrl = Storage::url($imagePath);
                $products->image = $imageUrl;
            }

            DB::transaction(function () use ($store, $products, $validatedData, $slug) {
                Product::query()->where('id', $products->id)->update([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                    'unique_id' => $validatedData['unique_id'],
                    'store_id' => $store->id,
                ]);
            });

            return ResponseHelper::responseJson(201, 'Success update product', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                ]
            ], '/dashboard');
        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function delete(Request $request, $id){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required',
            'image' => 'required',
            'store_id' => 'required',
            'store_email' => 'required',
            'password' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        $products = Product::query()->where('id' , $id)->first();
        if (!$products){
            return ResponseHelper::responseJson(401, 'product not found ', [], '/register');
        }

        try {

            if ($request->hasFile('images')) {
                if ($products->image){
                    $oldThumbnailPath = str_replace('/storage/' , '', $products->image);
                    Storage::disk('public')->delete($oldThumbnailPath);
                }
                $products->image = null;
            }

            DB::transaction(function () use ($products) {
                Product::query()->where('id', $products->id)->delete();
            });

            return ResponseHelper::responseJson(201, 'Success delete product', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                ]
            ], '/dashboard');
        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function addType(Request $request){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required',
            'image' => 'required',
            'store_id' => 'required',
            'product_id' => 'required',
            'store_email' => 'required',
            'password' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        $products = Product::query()->where('id' , $validatedData['product_id'])->first();
        if (!$products){
            return ResponseHelper::responseJson(401, 'product not found ', [], '/register');
        }

        try {
            $imageUrl = $request->hasFile('images') ?
                Storage::url($request->file('images')->store('upload/product' , 'public'))
                : null;
            $slug = Str::slug($validatedData['name'], '_');
            DB::transaction(function () use ($store, $user, $validatedData, $slug, $imageUrl) {
                TypeProduct::create([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                    'image' => $imageUrl,
                    'product_id' => $validatedData['product_id'],
                    'unique_id' => $validatedData['unique_id'],
                    'store_id' => $validatedData['store_id'],
                ]);
            });

            return ResponseHelper::responseJson(201, 'Login to store successful', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                ]
            ], '/dashboard');
        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }


    public function updateType(Request $request, $id){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required',
            'image' => 'required',
            'store_id' => 'required',
            'product_id' => 'required',
            'store_email' => 'required',
            'password' => 'required'
        ];
        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        $products = Product::query()->where('id' , $validatedData['product_id'])->first();
        if (!$products){
            return ResponseHelper::responseJson(401, 'product not found ', [], '/register');
        }

        $typeProducts = TypeProduct::query()->where('id' , $id)->first();
        if (!$typeProducts){
            return ResponseHelper::responseJson(401, 'product not found ', [], '/register');
        }

        try {
            $slug = Str::slug($validatedData['name'], '_');

            if ($request->hasFile('images')) {
                if ($products->image){
                    $oldThumbnailPath = str_replace('/storage/' , '', $products->image);
                    Storage::disk('public')->delete($oldThumbnailPath);
                }

                $imagePath = $request->file('images')->store('upload/product', 'public');
                $imageUrl = Storage::url($imagePath);
                $products->image = $imageUrl;
            }

            DB::transaction(function () use ($store, $products, $validatedData, $slug , $id) {
                TypeProduct::query()->where('id', $id)->where('product_id', $validatedData['product_id'])->update([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                    'unique_id' => $validatedData['unique_id'],
                    'store_id' => $store->id,
                ]);
            });

            return ResponseHelper::responseJson(201, 'Success update product', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                ]
            ], '/dashboard');
        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }

    public function deleteType(Request $request , $id){
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'store_id' => 'required',
            'store_email' => 'required',
            'password' => 'required'
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error' , '/register');
        if (!is_array($validatedData)){
            return $validatedData;
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($validatedData['store_email'] , $user->id );
        if ($store instanceof  \Illuminate\Http\JsonResponse){
            return  $store;
        }

        $products = Product::query()->where('id' , $validatedData['product_id'])->first();
        if (!$products){
            return ResponseHelper::responseJson(401, 'product not found ', [], '/register');
        }

        $typeProducts = TypeProduct::query()->where('id' , $id)->first();
        if (!$typeProducts){
            return ResponseHelper::responseJson(401, 'product not found ', [], '/register');
        }


        try {

            if ($request->hasFile('images')) {
                if ($typeProducts->image){
                    $oldThumbnailPath = str_replace('/storage/' , '', $typeProducts->image);
                    Storage::disk('public')->delete($oldThumbnailPath);
                }
                $typeProducts->image = null;
            }

            DB::transaction(function () use ($typeProducts) {
                TypeProduct::query()->where('id', $typeProducts->id)->delete();
            });

            return ResponseHelper::responseJson(201, 'Success delete product', [
                'store' => [
                    'store' => $store->email,
                    'user' => $user->email,
                ]
            ], '/dashboard');
        }   catch (Exception $e){
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/login');
        }
    }


    public function show(Request $request, $id = null){
        $user = ControllerHelper::checkUserHasToken($request);

        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }


        if ($id) {
            $product = Product::where('id', $id)->where('user_id', $user->id)->first();
            if (!$product) {
                return ResponseHelper::responseJson(404, 'Product not found', [], '/show-address');
            }

            return ResponseHelper::responseJson(200, 'Product retrieved successfully', [
                'product' => $product
            ], '/show-address');
        } else {
            $product = Product::where('slug', )->get();
            return ResponseHelper::responseJson(200, 'Addresses retrieved successfully', [
                'product' => $product
            ], '/show-address');
        }
    }

}
