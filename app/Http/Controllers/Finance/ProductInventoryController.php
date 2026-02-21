<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreInventoryItemRequest;
use App\Http\Requests\Finance\UpdateInventoryItemRequest;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductInventoryController extends Controller
{
    public function index(): Response
    {
        $productItems = InventoryItem::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryItem $inventoryItem) {
                return [
                    'id' => $inventoryItem->id,
                    'name' => $inventoryItem->name,
                    'type' => $inventoryItem->type,
                    'type_label' => $this->formatTypeLabel($inventoryItem->type),
                    'price' => (float) $inventoryItem->price,
                    'updated_at' => $inventoryItem->updated_at?->toDateString(),
                ];
            })
            ->values();

        return Inertia::render('finance/product-inventory/index', [
            'product_items' => $productItems,
        ]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        InventoryItem::query()->create($request->validated());

        return back()->with('success', 'Product price added.');
    }

    public function update(
        UpdateInventoryItemRequest $request,
        InventoryItem $inventoryItem
    ): RedirectResponse {
        $inventoryItem->update($request->validated());

        return back()->with('success', 'Product price updated.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->delete();

        return back()->with('success', 'Product price removed.');
    }

    private function formatTypeLabel(string $type): string
    {
        return match ($type) {
            'uniform' => 'Uniform',
            'book' => 'Book',
            'other' => 'Other',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
