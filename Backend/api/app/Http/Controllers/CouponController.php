<?php

namespace App\Http\Controllers;

use App\Models\DiscountCoupon;
use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $request->merge([
            'coupon' => mb_strtoupper(trim((string) $request->input('coupon', ''))),
        ]);
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.productId' => ['required', 'integer', 'distinct', 'exists:Prece,Prece_ID'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon' => ['nullable', 'string', 'alpha_dash:ascii', 'max:64', Rule::exists('discount_coupons', 'code')],
        ]);

        $result = DB::transaction(function () use ($data, $request): array {
            $coupon = null;
            if (($data['coupon'] ?? '') !== '') {
                $coupon = DiscountCoupon::query()
                    ->with('products:Prece_ID')
                    ->where('code', $data['coupon'])
                    ->lockForUpdate()
                    ->first();

                if (! $coupon || ! $this->isAvailable($coupon)) {
                    throw ValidationException::withMessages([
                        'coupon' => 'Šis atlaižu kods vairs nav derīgs.',
                    ]);
                }
            }

            $items = collect($data['items'])->sortBy('productId');
            $products = Prece::query()
                ->whereIn('Prece_ID', $items->pluck('productId'))
                ->orderBy('Prece_ID')
                ->lockForUpdate()
                ->get()
                ->keyBy('Prece_ID');

            foreach ($items as $item) {
                $product = $products->get($item['productId']);
                if (! $product || $product->Atlikums < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => 'Kādai groza precei vairs nav pietiekams atlikums. Atjauno grozu.',
                    ]);
                }
            }

            $subtotal = 0;
            $total = 0;
            $ids = [];
            $discountPercent = $coupon ? (float) $coupon->discount_percent : 0;
            $eligibleProductIds = $coupon?->products->pluck('Prece_ID')->map(fn ($id) => (int) $id)->all() ?? [];
            $eligibleItemCount = 0;

            foreach ($items as $item) {
                $product = $products->get($item['productId']);
                $lineCents = (int) round((float) $product->Cena * 100) * $item['quantity'];
                $isEligible = $coupon && ($eligibleProductIds === [] || in_array((int) $product->getKey(), $eligibleProductIds, true));
                $finalCents = $isEligible
                    ? (int) round($lineCents * (100 - $discountPercent) / 100)
                    : $lineCents;
                $product->decrement('Atlikums', $item['quantity']);
                $order = Pasutijums::query()->create([
                    'Lietotajs_ID' => $request->user()->getKey(),
                    'Prece_ID' => $product->getKey(),
                    'Daudzums' => $item['quantity'],
                    'Kopeja_cena' => $finalCents / 100,
                    'Statuss' => 'gaida',
                    'Klienta_vards' => $request->user()->Vards,
                ]);
                $ids[] = (string) $order->getKey();
                $subtotal += $lineCents;
                $total += $finalCents;
                if ($isEligible) {
                    $eligibleItemCount += $item['quantity'];
                }
            }

            if ($coupon && $eligibleItemCount === 0) {
                throw ValidationException::withMessages([
                    'coupon' => 'Šis atlaižu kods neattiecas uz precēm tavā grozā.',
                ]);
            }

            if ($coupon) {
                $coupon->increment('usage_count');
            }

            return [
                'orderIds' => $ids,
                'subtotal' => $subtotal / 100,
                'discount' => ($subtotal - $total) / 100,
                'discountPercent' => $discountPercent,
                'coupon' => $coupon?->code,
                'total' => $total / 100,
                'status' => 'gaida',
            ];
        });

        return response()->json($result, 201);
    }

    private function isAvailable(DiscountCoupon $coupon): bool
    {
        return $coupon->active
            && $coupon->expires_at->isFuture()
            && ($coupon->usage_limit === null || $coupon->usage_count < $coupon->usage_limit);
    }
}
