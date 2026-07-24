<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PriceReference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceReferenceConfigurationController extends Controller
{
    public function show(): JsonResponse
    {
        $yellow = PriceReference::query()->where('code', 'YELLOW')->firstOrFail();
        $green = PriceReference::query()->where('code', 'GREEN')->firstOrFail();

        return response()->json([
            'yellow' => [
                'id' => $yellow->id,
                'discount_percent' => (float) ($yellow->default_discount_percent ?? 0),
            ],
            'green' => [
                'id' => $green->id,
                'discount_percent' => (float) ($green->default_discount_percent ?? 0),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'yellow_discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'green_discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        abort_unless((int) $request->user()?->account_type === 1, 403);

        $result = DB::transaction(function () use ($validated) {
            $yellow = PriceReference::query()->where('code', 'YELLOW')->lockForUpdate()->firstOrFail();
            $green = PriceReference::query()->where('code', 'GREEN')->lockForUpdate()->firstOrFail();

            $oldYellowDiscount = (float) ($yellow->default_discount_percent ?? 0);
            $oldGreenDiscount = (float) ($green->default_discount_percent ?? 0);
            $newYellowDiscount = (float) $validated['yellow_discount'];
            $newGreenDiscount = (float) $validated['green_discount'];

            $yellow->default_discount_percent = $newYellowDiscount;
            $yellow->save();

            $green->default_discount_percent = $newGreenDiscount;
            $green->save();

            $yellowCustomerCount = 0;
            if ($newYellowDiscount !== $oldYellowDiscount) {
                $yellowCustomerCount = Customer::query()
                    ->where('price_reference_id', $yellow->id)
                    ->update(['discount_percent' => $newYellowDiscount]);
            }

            $greenCustomerCount = 0;
            if ($newGreenDiscount !== $oldGreenDiscount) {
                $greenCustomerCount = Customer::query()
                    ->where('price_reference_id', $green->id)
                    ->update(['discount_percent' => $newGreenDiscount]);
            }

            return [
                'yellow' => [
                    'id' => $yellow->id,
                    'old_discount' => $oldYellowDiscount,
                    'new_discount' => $newYellowDiscount,
                    'customers_updated' => $yellowCustomerCount,
                ],
                'green' => [
                    'id' => $green->id,
                    'old_discount' => $oldGreenDiscount,
                    'new_discount' => $newGreenDiscount,
                    'customers_updated' => $greenCustomerCount,
                ],
            ];
        });

        return response()->json([
            'message' => 'Price Reference Configuration Updated Successfully',
            'changes' => $result,
        ]);
    }
}
