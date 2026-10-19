<?php

namespace App\Http\Controllers;

use App\Models\DiscountCoupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminCouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            DiscountCoupon::query()->with('products:Prece_ID,Nosaukums')->orderByDesc('created_at')->get(),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['code' => mb_strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'alpha_dash:ascii', 'min:3', 'max:64', 'unique:discount_coupons,code'],
            'discountPercent' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'usageLimit' => ['required', 'integer', 'in:1,0'],
            'expiresAt' => ['required', 'date', 'after:now'],
            'productIds' => ['sometimes', 'array'],
            'productIds.*' => ['integer', 'distinct', 'exists:Prece,Prece_ID'],
        ]);

        $coupon = DiscountCoupon::query()->create([
            'code' => $data['code'],
            'discount_percent' => $data['discountPercent'],
            'usage_limit' => $data['usageLimit'] === 1 ? 1 : null,
            'usage_count' => 0,
            'expires_at' => $data['expiresAt'],
        ]);
        $coupon->products()->sync($data['productIds'] ?? []);

        return response()->json($coupon->load('products:Prece_ID,Nosaukums'), 201);
    }

    public function revoke(DiscountCoupon $coupon): JsonResponse
    {
        $coupon->update(['active' => false]);

        return response()->json($coupon->fresh()->load('products:Prece_ID,Nosaukums'));
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $request->merge(['code' => mb_strtoupper(trim((string) $request->query('code', '')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'alpha_dash:ascii', 'min:3', 'max:64'],
            'productIds' => ['required', 'array', 'min:1'],
            'productIds.*' => ['integer', 'distinct', 'exists:Prece,Prece_ID'],
        ]);
        $coupon = DiscountCoupon::query()->with('products:Prece_ID')->where('code', $data['code'])->first();

        if (! $coupon || ! $this->isAvailable($coupon)) {
            throw ValidationException::withMessages(['coupon' => 'Šis atlaižu kods nav derīgs vai ir beidzies.']);
        }

        $eligibleProductIds = $coupon->products->pluck('Prece_ID')->map(fn ($id) => (string) $id)->all();
        if ($eligibleProductIds !== [] && array_intersect(
            $eligibleProductIds,
            array_map('strval', $data['productIds']),
        ) === []) {
            throw ValidationException::withMessages(['coupon' => 'Šis atlaižu kods neattiecas uz precēm tavā grozā.']);
        }

        return response()->json([
            'code' => $coupon->code,
            'discountPercent' => (float) $coupon->discount_percent,
            'productIds' => $eligibleProductIds,
        ]);
    }

    private function isAvailable(DiscountCoupon $coupon): bool
    {
        return $coupon->active
            && $coupon->expires_at->isFuture()
            && ($coupon->usage_limit === null || $coupon->usage_count < $coupon->usage_limit);
    }
}
