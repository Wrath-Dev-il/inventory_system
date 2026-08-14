<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductImageService $imageService,
    ) {}

    public function thumbnail(Product $product)
    {
        $product->load('image');
        if (! $product->image || ! $product->image->thumbnail_data || ! $this->imageService->isDisplayable($product->image->thumbnail_data)) {
            abort(404);
        }

        return $this->imageService->streamData(
            $product->image->thumbnail_data,
            $product->image->mime_type
        );
    }

    public function show(Product $product)
    {
        $product->load('image');
        if (! $product->image) {
            abort(404);
        }

        $data = $product->image->image_data;

        if (! $this->imageService->isDisplayable($data) && $this->imageService->isDisplayable($product->image->thumbnail_data)) {
            $data = $product->image->thumbnail_data;
        }

        if (! $this->imageService->isDisplayable($data)) {
            abort(404);
        }

        return $this->imageService->streamData(
            $data,
            $product->image->mime_type
        );
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'picture' => ['required', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('picture');
        $this->imageService->validate($file);

        $data = $this->imageService->process($file);

        DB::transaction(function () use ($product, $data) {
            $product->image()->delete();
            $product->image()->create($data);
        });

        return response()->json([
            'message' => 'Picture updated successfully.',
            'has_image' => true,
        ]);
    }
}
