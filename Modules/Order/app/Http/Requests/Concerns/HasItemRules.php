<?php

declare(strict_types=1);

namespace Modules\Order\Http\Requests\Concerns;

/**
 * Shared item validation rules for order line items.
 *
 * Used by both StoreOrderRequest and UpdateOrderRequest to keep
 * item-level constraints in a single place (DRY).
 */
trait HasItemRules
{
    /**
     * Validation rules for the items array and its nested fields.
     *
     * @param  string  $presence  'required' for store, 'sometimes' for update.
     * @return array<string, mixed>
     */
    protected static function itemRules(string $presence = 'required'): array
    {
        $itemPresence = $presence === 'required' ? 'required' : 'required_with:items';

        return [
            'items' => [$presence, 'array', 'min:1'],
            'items.*.product_name' => [$itemPresence, 'string', 'max:255'],
            'items.*.quantity' => [$itemPresence, 'integer', 'min:1'],
            'items.*.price' => [$itemPresence, 'numeric', 'min:0'],
        ];
    }
}
