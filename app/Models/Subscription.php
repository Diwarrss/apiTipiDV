<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'license_key',
        'customer_email',
        'customer_name',
        'organization_name',
        'billing_period',
        'machine_slots',
        'starts_at',
        'expires_at',
        'status',
        'wompi_reference',
        'transaction_uuid',
        'gridpay_product_uuid',
        'amount_cop',
        'metadata',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount_cop' => 'decimal:2',
        'metadata' => 'array',
        'machine_slots' => 'integer',
    ];

    public function activations(): HasMany
    {
        return $this->hasMany(MachineActivation::class, 'subscription_id');
    }

    public function activeActivations(): HasMany
    {
        return $this->activations()->whereNull('deactivated_at');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at->isFuture();
    }
}
