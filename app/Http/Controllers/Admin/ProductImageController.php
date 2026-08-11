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
        if (!$product->image || !$product->image->thumbnail_data) {
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
        if (!$product->image) {
            abort(404);
        }
        return $this->imageService->streamData(
            $product->image->image_data,
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
        $hadImage = $product->image()->exists();

        DB::transaction(function () use ($product, $data) {
            // Lock the product while replacing/adding its single picture so two
            // simultaneous uploads cannot leave the UI in an inconsistent state.
            Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            $product->image()->delete();
            $product->image()->create($data);
        });

        $product->unsetRelation('image');
        $product->load('image');
        $version = $product->image?->updated_at?->timestamp
            ?? $product->image?->created_at?->timestamp
            ?? now()->timestamp;

        return response()->json([
            'message' => $hadImage ? 'Picture updated successfully.' : 'Picture added successfully.',
            'has_image' => true,
            'image_version' => $version,
            'thumbnail_url' => route('admin.products.picture.thumbnail', ['product' => $product]),
            'picture_url' => route('admin.products.picture.show', ['product' => $product]),
        ]);
    }
}
