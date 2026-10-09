<?php

namespace App\Http\Controllers;

use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge(['coupon' => strtoupper(trim((string) $request->input('coupon', '')))]);
        $data = $request->validate(['items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.productId' => ['required', 'integer', 'distinct', 'exists:Prece,Prece_ID'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'], 'coupon' => ['nullable', 'in:SIGMA15']]);
        $result = DB::transaction(function () use ($data, $request): array {
            $items = collect($data['items'])->sortBy('productId');
            $products = Prece::query()->whereIn('Prece_ID', $items->pluck('productId'))->orderBy('Prece_ID')->lockForUpdate()->get()->keyBy('Prece_ID');
            foreach ($items as $item) {
                $product = $products->get($item['productId']);
                if (! $product || $product->Atlikums < $item['quantity']) {
                    throw ValidationException::withMessages(['items' => 'Kādai groza precei vairs nav pietiekams atlikums. Atjauno grozu.']);
                }
            }
            $subtotal = 0;
            $total = 0;
            $ids = [];
            foreach ($items as $item) {
                $product = $products->get($item['productId']);
                $lineCents = (int) round((float) $product->Cena * 100) * $item['quantity'];
                $finalCents = ($data['coupon'] ?? '') === 'SIGMA15' ? (int) round($lineCents * 0.85) : $lineCents;
                $product->decrement('Atlikums', $item['quantity']);
                $order = Pasutijums::query()->create(['Lietotajs_ID' => $request->user()->getKey(), 'Prece_ID' => $product->getKey(), 'Daudzums' => $item['quantity'], 'Kopeja_cena' => $finalCents / 100, 'Statuss' => 'gaida', 'Klienta_vards' => $request->user()->Vards]);
                $ids[] = (string) $order->getKey();
                $subtotal += $lineCents;
                $total += $finalCents;
            }

            return ['orderIds' => $ids, 'subtotal' => $subtotal / 100, 'discount' => ($subtotal - $total) / 100, 'total' => $total / 100, 'status' => 'gaida'];
        });

        return response()->json($result, 201);
    }
}
