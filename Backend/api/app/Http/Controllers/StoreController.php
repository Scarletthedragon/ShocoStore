<?php

namespace App\Http\Controllers;

use App\Models\Pasutijums;
use App\Models\Prece;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function products(): JsonResponse
    {
        $products = Prece::query()
            ->whereNotIn('Nosaukums', ['Burbuļu sapnis', 'Melleņu bomba', 'Skarbā vafeļu šokolāde'])
            ->orderBy('Prece_ID')
            ->get()
            ->map(fn (Prece $product) => $this->productPayload($product));

        return response()->json($products->values());
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $data = $this->validateProduct($request);

        $attributes = $this->productAttributes($data);
        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->storePublicly('chocolates', 'public');
        }
        $product = Prece::query()->create($attributes);

        return response()->json($this->productPayload($product), 201);
    }

    public function updateProduct(Request $request, Prece $product): JsonResponse
    {
        $data = $this->validateProduct($request, true);
        $attributes = $this->productAttributes($data);
        $oldImage = $product->image_path;
        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->storePublicly('chocolates', 'public');
        }
        $product->update($attributes);
        if ($oldImage && isset($attributes['image_path'])) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json($this->productPayload($product->fresh()));
    }

    public function destroyProduct(Prece $product): JsonResponse
    {
        if ($product->pasutijumi()->exists()) {
            return response()->json([
                'error' => 'Šokolādi nevar dzēst, jo tai ir piesaistīti pasūtījumi.',
            ], 409);
        }

        $imagePath = $product->image_path;
        $product->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->json(['id' => (string) $product->getKey()]);
    }

    private function validateProduct(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'required' : 'sometimes';

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => [$required, 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'tone' => [$required, 'nullable', 'string', 'max:25'],
            'tag' => ['nullable', 'string', 'max:50'],
            'imageSelected' => ['sometimes', 'boolean'],
            'image' => ['required_if:imageSelected,1', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.required_if' => 'Neizdevās saņemt izvēlēto attēlu. Pārbaudi servera augšupielādes limitu un mēģini vēlreiz.',
        ]);
    }

    private function productAttributes(array $data): array
    {
        return [
            'Nosaukums' => trim($data['name']),
            'Cena' => $data['price'],
            'Atlikums' => $data['stock'] ?? 0,
            'Apraksts' => $data['description'] ?? null,
            'Tonis' => $data['tone'] ?? 'mango',
            'Birka' => $data['tag'] ?? '',
        ];
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Pasutijums::query()
            ->where('Lietotajs_ID', $request->user()->getKey())
            ->with('prece')
            ->orderByDesc('Pasutijums_ID')
            ->get()
            ->map(fn (Pasutijums $order) => $this->orderPayload($order));

        return response()->json($orders->values());
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'productId' => ['required', 'integer', 'exists:Prece,Prece_ID'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'customerName' => ['nullable', 'string', 'max:100'],
        ]);

        $order = DB::transaction(function () use ($data): ?Pasutijums {
            $product = Prece::query()
                ->whereKey($data['productId'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->Atlikums < $data['quantity']) {
                return null;
            }

            $product->decrement('Atlikums', $data['quantity']);

            return Pasutijums::query()->create([
                'Lietotajs_ID' => null,
                'Prece_ID' => $product->Prece_ID,
                'Daudzums' => $data['quantity'],
                'Kopeja_cena' => round((float) $product->Cena * $data['quantity'], 2),
                'Statuss' => 'gaida',
                'Klienta_vards' => trim($data['customerName'] ?? '') ?: 'Guest',
            ]);
        });

        if ($order === null) {
            return response()->json(['error' => 'Not enough stock.'], 409);
        }

        return response()->json($this->orderPayload($order->refresh()->load('prece')), 201);
    }

    private function productPayload(Prece $product): array
    {
        $catalog = json_decode(file_get_contents(database_path('seeders/catalog.json')), true, flags: JSON_THROW_ON_ERROR);
        $visual = collect($catalog)->first(fn (array $item) => $item['name'] === $product->Nosaukums || $item['id'] === $product->Tonis) ?? [];

        return [
            'id' => (string) $product->Prece_ID,
            'name' => $product->Nosaukums,
            'description' => $product->Apraksts ?? '',
            'price' => (float) $product->Cena,
            'stock' => $product->Atlikums,
            'tone' => $visual['tone'] ?? 'yellow',
            'tile' => $visual['tile'] ?? 8,
            'category' => $visual['category'] ?? 'Piena',
            'tag' => $product->Birka ?? '',
            'details' => $visual['details'] ?? null,
            'visualId' => $product->Tonis,
            'imageUrl' => $product->image_path ? Storage::disk('public')->url($product->image_path) : null,
        ];
    }

    private function orderPayload(Pasutijums $order): array
    {
        return [
            'id' => (string) $order->Pasutijums_ID,
            'productId' => (string) $order->Prece_ID,
            'productName' => $order->prece?->Nosaukums,
            'quantity' => $order->Daudzums,
            'total' => (float) $order->Kopeja_cena,
            'customerName' => $order->Klienta_vards,
            'createdAt' => $order->Izveidots?->toISOString(),
        ];
    }
}
