<?php

namespace App\Http\Controllers;

use App\Models\DiscountCoupon;
use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $request->merge([
            'coupon' => mb_strtoupper(trim((string) $request->input('coupon', ''))),
        ]);
        $data = $request->validate(array_merge([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.productId' => ['required', 'integer', 'distinct', 'exists:Prece,Prece_ID'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon' => ['nullable', 'string', 'alpha_dash:ascii', 'max:64', Rule::exists('discount_coupons', 'code')],
            'saveAddress' => ['sometimes', 'boolean'],
        ], ShipmentController::addressRules()));

        $result = DB::transaction(function () use ($data, $request): array {
            $coupon = null;
            if (($data['coupon'] ?? '') !== '') {
                $coupon = DiscountCoupon::query()
                    ->with('products:Prece_ID')
                    ->where('code', $data['coupon'])
                    ->lockForUpdate()
                    ->first();

                if (! $coupon || ! $coupon->isAvailable()) {
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
            $shipmentId = (string) Str::uuid();
            if ($data['saveAddress'] ?? false) {
                $request->user()->delivery_address = $data['address'];
                $request->user()->save();
            }
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
                    'shipment_id' => $shipmentId,
                    'delivery_address' => $data['address'],
                    'unit_price' => $finalCents / 100 / $item['quantity'],
                    'status_history' => [['status' => 'gaida', 'at' => now()->toISOString(), 'actor' => 'customer', 'action' => 'created']],
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
                'shipmentId' => $shipmentId,
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
}
