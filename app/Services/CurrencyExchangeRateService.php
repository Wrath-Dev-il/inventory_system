<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyExchangeRateService
{
    private string $endpoint;
    private ?string $token;
    private int $timeout;
    private int $cacheMinutes;
    private string $publicApiUrl;

    public function __construct()
    {
        $this->endpoint = config('services.currency_rate.endpoint', '');
        $this->token = config('services.currency_rate.token');
        $this->timeout = (int) config('services.currency_rate.timeout', 8);
        $this->cacheMinutes = (int) config('services.currency_rate.cache_minutes', 30);
        $this->publicApiUrl = config('services.currency_rate.public_api', 'https://api.frankfurter.app');
    }

    public function isConfigured(): bool
    {
        return filled($this->endpoint) || filled($this->publicApiUrl);
    }

    public function getCnyPhpRate(): array
    {
        $cacheKey = 'cny_php_rate';

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () {
            return $this->fetchFromSource();
        });
    }

    public function refreshRate(): array
    {
        Cache::forget('cny_php_rate');

        return $this->fetchFromSource(true);
    }

    private function fetchFromSource(bool $store = false): array
    {
        if (filled($this->endpoint)) {
            return $this->fetchFromEndpoint($this->endpoint, $this->token, $store, 'Google Finance');
        }

        return $this->fetchFromPublicApi($store);
    }

    private function fetchFromEndpoint(string $endpoint, ?string $token, bool $store, string $provider): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($endpoint, $token ? ['token' => $token] : []);

            if (!$response->successful()) {
                Log::warning('Currency rate endpoint returned non-200', ['status' => $response->status(), 'provider' => $provider]);
                return $this->fallbackOrError();
            }

            $body = $response->json();

            if (!is_array($body) || empty($body['success']) || !isset($body['rate'])) {
                Log::warning('Currency rate endpoint returned unexpected payload', ['body' => $body, 'provider' => $provider]);
                return $this->fallbackOrError();
            }

            $rate = (float) $body['rate'];

            if ($rate <= 0) {
                Log::warning('Currency rate endpoint returned non-positive rate', ['rate' => $rate, 'provider' => $provider]);
                return $this->fallbackOrError();
            }

            return $this->buildResult($rate, $provider . ' via configured endpoint', $body['retrieved_at'] ?? null, $store);
        } catch (RequestException $e) {
            Log::warning('Currency rate HTTP request failed', ['error' => $e->getMessage(), 'provider' => $provider]);
            return $this->fallbackOrError();
        } catch (\Exception $e) {
            Log::warning('Currency rate fetch failed', ['error' => $e->getMessage(), 'provider' => $provider]);
            return $this->fallbackOrError();
        }
    }

    private function fetchFromPublicApi(bool $store): array
    {
        try {
            $url = rtrim($this->publicApiUrl, '/') . '/latest?from=CNY&to=PHP';

            $response = Http::timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Public currency API returned non-200', ['status' => $response->status(), 'url' => $url]);
                return $this->fallbackOrError();
            }

            $body = $response->json();

            if (!is_array($body) || !isset($body['rates']['PHP'])) {
                Log::warning('Public currency API returned unexpected payload', ['body' => $body]);
                return $this->fallbackOrError();
            }

            $rate = (float) $body['rates']['PHP'];

            if ($rate <= 0) {
                Log::warning('Public currency API returned non-positive rate', ['rate' => $rate]);
                return $this->fallbackOrError();
            }

            $retrievedAt = $body['date'] ?? null;

            return $this->buildResult($rate, 'Frankfurter API (public)', $retrievedAt, $store);
        } catch (RequestException $e) {
            Log::warning('Public currency API HTTP request failed', ['error' => $e->getMessage()]);
            return $this->fallbackOrError();
        } catch (\Exception $e) {
            Log::warning('Public currency API fetch failed', ['error' => $e->getMessage()]);
            return $this->fallbackOrError();
        }
    }

    private function buildResult(float $rate, string $provider, ?string $retrievedAt, bool $store): array
    {
        $cny = Currency::where('code', 'CNY')->first();
        $php = Currency::where('code', 'PHP')->first();

        if (!$cny || !$php) {
            return ['success' => false, 'message' => 'Currency reference records not found.'];
        }

        $effectiveAt = $retrievedAt ? Carbon::parse($retrievedAt) : now();

        $rateRecord = ExchangeRate::create([
            'from_currency_id' => $cny->id,
            'to_currency_id' => $php->id,
            'rate' => $rate,
            'provider' => $provider,
            'provider_reference' => $retrievedAt,
            'effective_at' => $effectiveAt,
        ]);

        $result = [
            'success' => true,
            'rate' => $rate,
            'provider' => $provider,
            'retrieved_at' => $rateRecord->effective_at->toIso8601String(),
            'cached' => false,
        ];

        return $store ? $result : $result;
    }

    public function fallbackOrError(): array
    {
        $latest = ExchangeRate::whereHas('fromCurrency', function ($q) {
            $q->where('code', 'CNY');
        })->whereHas('toCurrency', function ($q) {
            $q->where('code', 'PHP');
        })->latest()->first();

        if ($latest) {
            return [
                'success' => true,
                'rate' => (float) $latest->rate,
                'provider' => $latest->provider ?? 'Previous stored rate',
                'retrieved_at' => $latest->effective_at?->toIso8601String(),
                'cached' => true,
                'message' => 'Using last known rate. Unable to fetch fresh rate.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Unable to retrieve currency rate. No cached rate available.',
        ];
    }

    public function getTrustedRate(): ?float
    {
        $result = $this->getCnyPhpRate();

        if ($result['success'] && isset($result['rate'])) {
            return (float) $result['rate'];
        }

        return null;
    }
}
