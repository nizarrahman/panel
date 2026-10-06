<?php

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    protected $table = 'billing_plans';

    protected $fillable = [
        'name',
        'description',
        'price',
        'memory',
        'cpu',
        'disk',
        'databases',
        'backups',
        'allocations',
        'egg_id',
        'startup',
        'image',
        'environment',
        'location_ids',
        'swap',
        'io',
        'duration_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'price'        => 'integer',
        'memory'       => 'integer',
        'cpu'          => 'integer',
        'disk'         => 'integer',
        'swap'         => 'integer',
        'io'           => 'integer',
        'duration_days'=> 'integer',
        'environment'  => 'array',
        'location_ids' => 'array',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(BillingOrder::class, 'plan_id');
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFormattedMemoryAttribute(): string
    {
        return $this->memory >= 1024
            ? ($this->memory / 1024) . ' GB'
            : $this->memory . ' MB';
    }

    public function getFormattedDiskAttribute(): string
    {
        return $this->disk >= 1024
            ? ($this->disk / 1024) . ' GB'
            : $this->disk . ' MB';
    }
}
