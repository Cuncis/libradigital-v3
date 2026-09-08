<?php

namespace App\Services\Mayar;

use App\Exceptions\MayarCheckoutException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Mayar's Membership API (docs.mayar.id/api-reference-v2/membership).
 * No Cashier-equivalent package exists for Mayar, so this talks to the HTTP API directly.
 */
class MayarClient
{
    protected function http(): PendingRequest
    {
        return Http::withToken((string) config('services.mayar.api_key'))
            ->baseUrl($this->baseUrl())
            ->acceptJson();
    }

    protected function baseUrl(): string
    {
        if ($override = config('services.mayar.base_url')) {
            return rtrim((string) $override, '/');
        }

        $host = config('services.mayar.is_production') ? 'https://api.mayar.id' : 'https://api.mayar.club';

        return "{$host}/hl/v2";
    }

    /**
     * Register a membership member for the given tier and billing period.
     *
     * @return array<string, mixed> the `membershipCustomer` object (includes `id`/`memberId`)
     */
    public function createMember(
        string $membershipTierId,
        string $customerName,
        string $customerEmail,
        string $customerMobile,
        int $monthlyPeriod,
    ): array {
        try {
            $response = $this->http()->post('/memberships/members/create', [
                'productId' => config('services.mayar.product_id'),
                'membershipTierId' => $membershipTierId,
                'customerInfo' => [
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'mobile' => $customerMobile,
                ],
                'membershipMonthlyPeriod' => $monthlyPeriod,
            ])->throw();
        } catch (RequestException $e) {
            throw new MayarCheckoutException('Mayar member registration failed: '.$e->getMessage(), previous: $e);
        }

        return $response->json('data.membershipCustomer') ?? [];
    }

    /**
     * Create (or reuse) an invoice for a member's current billing term.
     *
     * @return array<string, mixed> includes `id`, `membershipBillUrl`, `expiredAt`
     */
    public function createInvoice(string $memberId): array
    {
        try {
            $response = $this->http()
                ->post("/memberships/members/{$memberId}/invoice/create", [
                    'productId' => config('services.mayar.product_id'),
                ])
                ->throw();
        } catch (RequestException $e) {
            throw new MayarCheckoutException('Mayar invoice creation failed: '.$e->getMessage(), previous: $e);
        }

        return $response->json('data') ?? [];
    }
}
