<?php

namespace App\Services;

use App\Models\SalesQuotationSequence;
use Illuminate\Support\Facades\DB;

class SalesQuotationNumberService
{
    public function generateQuotationNo(): string
    {
        $now = now();
        $year = (int) $now->format('Y');
        $month = (int) $now->format('m');

        return DB::transaction(function () use ($year, $month) {
            $sequence = SalesQuotationSequence::query()
                ->lockForUpdate()
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if (! $sequence) {
                $sequence = SalesQuotationSequence::query()->create([
                    'year' => $year,
                    'month' => $month,
                    'last_sequence' => 0,
                ]);
            }

            $sequence->increment('last_sequence');
            $seq = $sequence->fresh()->last_sequence;

            return 'QT-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT)
                 . $year
                 . '-'
                 . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        });
    }
}
