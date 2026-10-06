<?php

namespace Pterodactyl\Services\Billing;

use Illuminate\Support\Facades\DB;

class BillingSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $row = DB::table('billing_settings')->where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    public function set(string $key, mixed $value): void
    {
        DB::table('billing_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function all(): array
    {
        return DB::table('billing_settings')->pluck('value', 'key')->toArray();
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get('billing_enabled', false);
    }

    public function getMidtransServerKey(): string
    {
        return (string) $this->get('midtrans_server_key', '');
    }

    public function getMidtransClientKey(): string
    {
        return (string) $this->get('midtrans_client_key', '');
    }

    public function isMidtransProduction(): bool
    {
        return (bool) $this->get('midtrans_production', false);
    }
}
