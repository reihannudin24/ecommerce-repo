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
    public function create(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required|integer',
            'images' => 'nullable|file|mimes:jpg,jpeg,png',
            'store_id' => 'required|integer',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $getStore = Store::query()->where('id', $validatedData['store_id'])->first();
        if (!$getStore){
            return ResponseHelper::responseJson(401, 'Store not found', [], '/register');
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($getStore->store_email, $user->id);

        try {
            $imageUrl = $request->hasFile('images')
                ? Storage::url($request->file('images')->store('upload/products', 'public'))
                : null;

            $uniqueId = Str::random(16);
            $slug = Str::slug($validatedData['name'], '_');

            DB::transaction(function () use ($store, $validatedData, $slug, $uniqueId, $imageUrl) {
                Product::create([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                    'image' => $imageUrl,
                    'unique_id' => $uniqueId,
                    'store_id' => 'required|integer',
                ]);
            });

            $product = Product::where('store_id', $store->id)->latest()->first();

            return ResponseHelper::responseJson(201, 'Product created successfully', [
                'product' => [
                    'store' => $store->email,
                    'product' => $product,
                ]
            ], '/dashboard');
        } catch (Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/dashboard');
        }
    }

    public function update(Request $request, $id)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required|integer',
            'images' => 'nullable|file|mimes:jpg,jpeg,png',
            'store_id' => 'required|integer',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $getStore = Store::query()->where('id', $validatedData['store_id'])->first();
        if (!$getStore){
            return ResponseHelper::responseJson(401, 'Store not found', [], '/register');
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($getStore->store_email, $user->id);

        $product = Product::query()->where('id' , $id)->where('store_id', $store->id)->first();

        if (!$product) {
            return ResponseHelper::responseJson(404, 'Product not found', [], '/dashboard');
        }

        try {
            if ($request->hasFile('images')) {
                if ($product->image) {
                    $oldImagePath = str_replace('/storage/', '', $product->image);
                    Storage::disk('public')->delete($oldImagePath);
                }

                $imagePath = $request->file('images')->store('upload/products', 'public');
                $product->image = Storage::url($imagePath);
            }

            $slug = Str::slug($validatedData['name'], '_');

            DB::transaction(function () use ($product, $validatedData, $slug) {
                $product->update([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                ]);
            });

            return ResponseHelper::responseJson(200, 'Product updated successfully', [
                'product' => $product
            ], '/dashboard');
        } catch (Exception $e) {
            return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/dashboard');
        }
    }

    public function delete(Request $request)
    {
        {
            $user = ControllerHelper::checkUserHasToken($request);
            $rules = [
                'store_id' => 'required|integer',
            ];
            $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation error', '/register');
            if (!is_array($validatedData)) {
                return $validatedData;
            }

            $getStore = Store::query()->where('id', $validatedData['store_id'])->first();
            if (!$getStore) {
                return ResponseHelper::responseJson(401, 'Store not found', [], '/register');
            }

            $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($getStore->store_email, $user->id);
            $product = Product::query()->where('id', $validatedData['id'])->where('store_id', $store->id)->first();

            if (!$product) {
                return ResponseHelper::responseJson(404, 'Product not found', [], '/dashboard');
            }

            try {
                DB::transaction(function () use ($product) {
                    if ($product->image) {
                        $oldImagePath = str_replace('/storage/', '', $product->image);
                        Storage::disk('public')->delete($oldImagePath);
                    }
                    $product->delete();
                });

                return ResponseHelper::responseJson(200, 'Product deleted successfully', [], '/dashboard');
            } catch (Exception $e) {
                return ResponseHelper::responseJson(500, 'Internal Server Error', ['error' => $e->getMessage()], '/dashboard');
            }
        }
    }


    // Inside ProductController.php

    public function addType(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required|integer',
            'images' => 'nullable|file|mimes:jpg,jpeg,png',
            'store_id' => 'required|integer',
            'product_id' => 'required|integer',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation failed', '/add-type');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $getStore = Store::find($validatedData['store_id']);
        if (!$getStore) {
            return ResponseHelper::responseJson(404, 'Store not found', [], '/add-type');
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($getStore->store_email, $user->id);
        if ($store instanceof \Illuminate\Http\JsonResponse) {
            return $store;
        }

        $product = Product::find($validatedData['product_id']);
        if (!$product) {
            return ResponseHelper::responseJson(404, 'Product not found', [], '/add-type');
        }

        try {
            $imageUrl = $request->hasFile('images')
                ? Storage::url($request->file('images')->store('upload/product/type', 'public'))
                : null;
            $slug = Str::slug($validatedData['name'], '_');
            $uniqueId = Str::random(16);

            DB::transaction(function () use ($store, $product, $validatedData, $slug, $imageUrl, $uniqueId) {
                TypeProduct::create([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                    'image' => $imageUrl,
                    'product_id' => $product->id,
                    'unique_id' => $uniqueId,
                    'store_id' => $store->id,
                ]);
            });

            return ResponseHelper::responseJson(201, 'Type added successfully', [], '/dashboard');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'An error occurred while adding the type', ['error' => $e->getMessage()], '/dashboard');
        }
    }

    public function updateType(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'id' => 'required|integer',
            'name' => 'required',
            'description' => 'required',
            'price' => 'required|numeric',
            'categories' => 'required',
            'type' => 'required',
            'quantity' => 'required|integer',
            'images' => 'nullable|file|mimes:jpg,jpeg,png',
            'store_id' => 'required|integer',
            'product_id' => 'required|integer',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation failed', '/update-type');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $getStore = Store::find($validatedData['store_id']);
        if (!$getStore) {
            return ResponseHelper::responseJson(404, 'Store not found', [], '/update-type');
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($getStore->store_email, $user->id);
        if ($store instanceof \Illuminate\Http\JsonResponse) {
            return $store;
        }

        $product = Product::find($validatedData['product_id']);
        if (!$product) {
            return ResponseHelper::responseJson(404, 'Product not found', [], '/update-type');
        }

        $typeProduct = TypeProduct::find($validatedData['id']);
        if (!$typeProduct) {
            return ResponseHelper::responseJson(404, 'Type not found', [], '/update-type');
        }

        try {
            if ($request->hasFile('images')) {
                if ($typeProduct->image) {
                    $oldImagePath = str_replace('/storage/', '', $typeProduct->image);
                    Storage::disk('public')->delete($oldImagePath);
                }

                $imagePath = $request->file('images')->store('upload/product/type', 'public');
                $typeProduct->image = Storage::url($imagePath);
            }

            $slug = Str::slug($validatedData['name'], '_');

            DB::transaction(function () use ($typeProduct, $validatedData, $slug) {
                $typeProduct->update([
                    'name' => $validatedData['name'],
                    'slug' => $slug,
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'categories' => $validatedData['categories'],
                    'type' => $validatedData['type'],
                    'quantity' => $validatedData['quantity'],
                ]);
            });

            return ResponseHelper::responseJson(200, 'Type updated successfully', [], '/dashboard');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'An error occurred while updating the type', ['error' => $e->getMessage()], '/dashboard');
        }
    }

    public function deleteType(Request $request)
    {
        $user = ControllerHelper::checkUserHasToken($request);
        $rules = [
            'id' => 'required|integer',
            'store_id' => 'required|integer',
        ];

        $validatedData = ControllerHelper::validateRequest($request, $rules, 422, 'Validation failed', '/delete-type');
        if (!is_array($validatedData)) {
            return $validatedData;
        }

        $getStore = Store::find($validatedData['store_id']);
        if (!$getStore) {
            return ResponseHelper::responseJson(404, 'Store not found', [], '/delete-type');
        }

        $store = ControllerHelper::checkStoreByEmailStoreAndUserPivot($getStore->store_email, $user->id);
        if ($store instanceof \Illuminate\Http\JsonResponse) {
            return $store;
        }

        $typeProduct = TypeProduct::find($validatedData['id']);
        if (!$typeProduct) {
            return ResponseHelper::responseJson(404, 'Type not found', [], '/delete-type');
        }

        try {
            DB::transaction(function () use ($typeProduct) {
                if ($typeProduct->image) {
                    $oldImagePath = str_replace('/storage/', '', $typeProduct->image);
                    Storage::disk('public')->delete($oldImagePath);
                }
                $typeProduct->delete();
            });

            return ResponseHelper::responseJson(200, 'Type deleted successfully', [], '/dashboard');
        } catch (\Exception $e) {
            return ResponseHelper::responseJson(500, 'An error occurred while deleting the type', ['error' => $e->getMessage()], '/dashboard');
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
