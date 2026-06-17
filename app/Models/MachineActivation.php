<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineActivation extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'machine_fingerprint',
        'machine_label',
        'activated_at',
        'last_seen_at',
        'deactivated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }
}
