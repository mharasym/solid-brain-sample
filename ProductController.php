<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Product as ProductModel;
use App\Http\Controllers\AbstractRestApiController;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product as ProductResource;
use Illuminate\Support\Facades\DB;

class ProductController extends AbstractRestApiController
{
    public function index(Request $request)
    {
        try {
            $products = QueryBuilder::for(ProductModel::class, $request)
                ->allowedFilters($this->allowedFilters())
                ->allowedIncludes($this->allowedIncludes())
                ->allowedSorts($this->allowedSorts())
                ->jsonApiPaginate();

            return ProductResource::collection($products);
        } catch (\Exception $exception) {
            return response()->json(['exception' => $exception->getMessage()]);
        }
    }
    
    public function store(StoreProductRequest $request)
    {
        try {
            $validated = $request->validated();
            DB::transaction(function () use ($validated) {
                foreach ($validated as $valid) {
                    $valid['url_slug'] = empty($valid['url_slug']) ? $valid['uuid'] : $valid['url_slug'];
                    $product = new ProductModel;
                    $product->fill($valid)->save();
                    if (array_key_exists('filter_value_uuids', $valid)) {
                        $product->filterValues()->sync($valid['filter_value_uuids']);
                    }
                    if (array_key_exists('analog_uuids', $valid)) {
                        $product->analogs()->sync($valid['analog_uuids']);
                    }
                }
            });
            
            return $this->createdResponse(ProductResource::collection(ProductModel
                ::whereIn('uuid', array_column($validated, 'uuid'))
                ->get()));
        } catch (ValidationException $exception) {
            return $this->clientErrorResponse([
                'form_validations' => $request->validator->errors(),
            ]);
        } catch (\Exception $exception) {
            return $this->clientErrorResponse([
                'exception' => $exception->getMessage()
            ]);
        }
    }
    
    public function show(Request $request, string $uuid)
    {
        try {
            $product = QueryBuilder::for(ProductModel::where('uuid', $uuid), $request)
                ->allowedIncludes($this->allowedIncludes())
                ->first();
            if (!$product) {
                return $this->notFoundResponse();
            }

            return new ProductResource($product);
        } catch (\Exception $exception) {
            return response()->json(['exception' => $exception->getMessage()]);
        }
    }
    
    public function update(UpdateProductRequest $request, string $uuid)
    {
        $product = ProductModel::findOrFail($uuid);
        try {
            $request->validated();
            DB::transaction(function () use ($request, $product, $uuid) {
                $product->fill($request->all())->save();
                if ($request->filled('filter_value_uuids')) {
                    $product->filterValues()->sync($request->filter_value_uuids);
                }
                if ($request->filled('analog_uuids')) {
                    $product->analogs()->sync($request->analog_uuids);
                }
            });

            return $this->updatedResponse(new ProductResource(
                ProductModel::where('uuid', $product->uuid)
                    ->first()
            ));
        } catch (ValidationException $exception) {
            return $this->clientErrorResponse([
                'form_validations' => $request->validator->errors(),
            ]);
        } catch (\Exception $exception) {
            return $this->clientErrorResponse([
                'exception' => $exception->getMessage()
            ]);
        }
    }
    
    public function destroy(string $uuid)
    {
        try {
            $product = ProductModel::find($uuid);
            if (!$product) {
                return $this->notFoundResponse();
            }
            $product->delete();

            return $this->deletedResponse();
        } catch (\Exception $exception) {
            return response()->json(['exception' => $exception->getMessage()]);
        }
    }
    
    protected function allowedFilters()
    {
        return [
            'name',
            'description',
            'oem',
            AllowedFilter::exact('uuid'),
            AllowedFilter::exact('category_uuid'),
            AllowedFilter::exact('brand_uuid'),
            AllowedFilter::exact('product_group_uuid'),
            AllowedFilter::exact('article'),
            AllowedFilter::exact('disabled'),
            AllowedFilter::exact('price'),
            AllowedFilter::exact('prop_value'),
            AllowedFilter::exact('url_slug'),
        ];
    }

    protected function allowedIncludes()
    {
        return [
            'brand',
            'filterValues',
            'productGroup',
            'category',
            'analogs',
        ];
    }

    protected function allowedSorts()
    {
        return [
            'uuid',
            'name',
            'image_prefix',
            'created_at',
            'updated_at',
        ];
    }
}
