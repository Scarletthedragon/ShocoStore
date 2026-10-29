<?php

namespace App\Http\Controllers;

use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public const TRANSITIONS = [
        'gaida' => ['apstiprinats', 'atcelts'],
        'apstiprinats' => ['sagatavo', 'atcelts'],
        'sagatavo' => ['nosutits', 'atcelts'],
        'nosutits' => ['piegadats'],
        'piegadats' => [],
        'atcelts' => [],
    ];

    public static function addressRules(string $prefix = 'address'): array
    {
        return [
            $prefix => ['required', 'array:recipient,phone,street,city,postalCode,country'],
            "$prefix.recipient" => ['required', 'string', 'max:100'],
            "$prefix.phone" => ['required', 'string', 'max:40'],
            "$prefix.street" => ['required', 'string', 'max:255'],
            "$prefix.city" => ['required', 'string', 'max:100'],
            "$prefix.postalCode" => ['required', 'string', 'max:20'],
            "$prefix.country" => ['required', 'string', 'max:100'],
        ];
    }

    public function saveAddress(Request $request): JsonResponse
    {
        $data = $request->validate(self::addressRules());
        $request->user()->delivery_address = $data['address'];
        $request->user()->save();

        return response()->json(['address' => $data['address']]);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->listing(Pasutijums::query()->where('Lietotajs_ID', $request->user()->getKey())->with('prece')->orderByDesc('Pasutijums_ID')->get());
    }

    public function adminIndex(): JsonResponse
    {
        return $this->listing(Pasutijums::query()->with('prece')->orderByDesc('Pasutijums_ID')->get());
    }

    private function listing(Collection $orders): JsonResponse
    {
        $shipments = $orders->groupBy(fn (Pasutijums $order): string => $order->shipment_id ?? (string) $order->getKey());

        return response()->json($shipments->map(function ($lines, string $id): array {
            $first = $lines->first();

            return [
                'id' => $id,
                'customerName' => $first->Klienta_vards,
                'status' => $first->Statuss,
                'address' => $first->delivery_address,
                'trackingNumber' => $first->tracking_number,
                'history' => $first->status_history ?? [],
                'createdAt' => $first->Izveidots?->toISOString(),
                'total' => round($lines->sum(fn (Pasutijums $line): float => (float) $line->Kopeja_cena), 2),
                'items' => $lines->map(fn (Pasutijums $line): array => [
                    'id' => (string) $line->getKey(),
                    'name' => $line->prece?->Nosaukums ?? 'Prece vairs nav pieejama',
                    'quantity' => $line->Daudzums,
                    'total' => (float) $line->Kopeja_cena,
                ])->values()->all(),
            ];
        })->values());
    }

    public function update(Request $request, string $shipment): JsonResponse
    {
        return $this->change($request, $shipment, false);
    }

    public function adminUpdate(Request $request, string $shipment): JsonResponse
    {
        return $this->change($request, $shipment, true);
    }

    private function change(Request $request, string $shipment, bool $admin): JsonResponse
    {
        $rules = [
            'status' => ['sometimes', Rule::in($admin ? array_keys(self::TRANSITIONS) : ['atcelts'])],
            'items' => ['sometimes', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
        if ($admin) {
            $rules['trackingNumber'] = ['sometimes', 'nullable', 'string', 'max:120'];
        }
        if ($request->has('address')) {
            $rules = array_merge($rules, self::addressRules());
        }
        $data = $request->validate($rules);
        DB::transaction(function () use ($request, $shipment, $admin, $data): void {
            $query = Pasutijums::query()->where(function ($query) use ($shipment): void {
                $query->where('shipment_id', $shipment);
                if (ctype_digit($shipment)) {
                    $query->orWhere(fn ($query) => $query->whereNull('shipment_id')->whereKey($shipment));
                }
            });
            if (! $admin) {
                $query->where('Lietotajs_ID', $request->user()->getKey());
            }
            $lines = $query->orderBy('Pasutijums_ID')->lockForUpdate()->get();
            abort_if($lines->isEmpty(), 404);
            $current = $lines->first()->Statuss;
            $next = $data['status'] ?? $current;
            if ($next !== $current && ! in_array($next, self::TRANSITIONS[$current] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Šī statusa maiņa nav atļauta. Atjauno pasūtījumu sarakstu.']);
            }
            if (isset($data['address']) || isset($data['items'])) {
                $editable = $admin ? ['gaida', 'apstiprinats', 'sagatavo'] : ['gaida'];
                if (! in_array($current, $editable, true) || in_array($next, ['atcelts', 'nosutits', 'piegadats'], true)) {
                    throw ValidationException::withMessages(['order' => 'Šo pasūtījumu vairs nevar mainīt.']);
                }
            }
            if (array_key_exists('trackingNumber', $data) && in_array($current, ['atcelts', 'piegadats'], true)) {
                throw ValidationException::withMessages(['order' => 'Pabeigtu pasūtījumu nevar mainīt.']);
            }
            $changes = collect($data['items'] ?? [])->keyBy('id');
            if ($changes->keys()->diff($lines->modelKeys())->isNotEmpty()) {
                throw ValidationException::withMessages(['items' => 'Prece nepieder šim pasūtījumam.']);
            }
            $products = Prece::query()->whereIn('Prece_ID', $lines->pluck('Prece_ID'))->orderBy('Prece_ID')->lockForUpdate()->get()->keyBy('Prece_ID');
            foreach ($lines as $line) {
                $quantity = $changes->get($line->getKey())['quantity'] ?? $line->Daudzums;
                $difference = $quantity - $line->Daudzums;
                $product = $products->get($line->Prece_ID);
                if ($difference > 0 && (! $product || $product->Atlikums < $difference)) {
                    throw ValidationException::withMessages(['items' => 'Nav pietiekams preces atlikums.']);
                }
                if ($difference !== 0) {
                    $product?->decrement('Atlikums', $difference);
                    $line->unit_price ??= (float) $line->Kopeja_cena / $line->Daudzums;
                    $line->Kopeja_cena = round((float) $line->unit_price * $quantity, 2);
                    $line->Daudzums = $quantity;
                }
                if ($next === 'atcelts' && $current !== 'atcelts') {
                    $product?->increment('Atlikums', $line->Daudzums);
                }
                $line->Statuss = $next;
                if (isset($data['address'])) {
                    $line->delivery_address = $data['address'];
                }
                if ($admin && array_key_exists('trackingNumber', $data)) {
                    $line->tracking_number = $data['trackingNumber'];
                }
                $history = $line->status_history ?? [];
                if ($next !== $current || isset($data['address']) || isset($data['items'])) {
                    $history[] = ['status' => $next, 'at' => now()->toISOString(), 'actor' => $admin ? 'admin' : 'customer', 'action' => $next !== $current ? 'status' : 'edited'];
                }
                $line->status_history = $history;
                $line->save();
            }
        });

        return response()->json(['saved' => true]);
    }
}
