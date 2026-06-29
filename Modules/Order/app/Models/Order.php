<?php

declare(strict_types=1);

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\Filterable;
use Modules\Order\Database\Factories\OrderFactory;
use Modules\Order\Enums\OrderStatus;
use Modules\Order\Observers\OrderObserver;
use Modules\Payment\Models\Payment;

#[ObservedBy([OrderObserver::class])]
class Order extends Model
{
    use Filterable;

    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'status',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Whether the order has any associated payments (blocks deletion).
     */
    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }
}
