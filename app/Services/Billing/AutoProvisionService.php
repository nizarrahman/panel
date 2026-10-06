<?php

namespace Pterodactyl\Services\Billing;

use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\BillingOrder;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException;
use Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException;

class AutoProvisionService
{
    public function __construct(private ServerCreationService $serverCreationService)
    {
    }

    /**
     * Provision a server for the user after a successful payment.
     *
     * @throws \Throwable
     */
    public function handle(BillingOrder $order): void
    {
        $order->loadMissing(['user', 'plan']);

        $plan = $order->plan;
        $user = $order->user;

        // Pastikan plan sudah dikonfigurasi dengan egg
        if (empty($plan->egg_id)) {
            Log::error("[Billing] Plan #{$plan->id} belum dikonfigurasi dengan egg_id. Server tidak dapat dibuat untuk order {$order->order_id}.");
            return;
        }

        // Resolve egg untuk mendapatkan nest_id, startup default, dan image default
        $egg = Egg::with('variables')->find($plan->egg_id);
        if (!$egg) {
            Log::error("[Billing] Egg #{$plan->egg_id} tidak ditemukan untuk order {$order->order_id}.");
            return;
        }

        // Build environment variables — gunakan default dari egg variables
        $environment = [];
        foreach ($egg->variables as $variable) {
            $planEnv = $plan->environment ?? [];
            $environment[$variable->env_variable] = $planEnv[$variable->env_variable]
                ?? $variable->default_value
                ?? '';
        }

        // Nama server: "username - nama plan"
        $serverName = $user->username . ' - ' . $plan->name;

        // Gunakan DeploymentObject supaya node & allocation dipilih otomatis
        $deployment = new DeploymentObject();
        $deployment->setDedicated(false);

        // Gunakan location_ids dari plan jika ada, atau kosong (semua lokasi)
        $locationIds = $plan->location_ids ?? [];
        $deployment->setLocations($locationIds);
        $deployment->setPorts([]);

        $data = [
            'name'             => $serverName,
            'description'      => 'Dibuat otomatis dari pembelian plan ' . $plan->name,
            'owner_id'         => $user->id,
            'egg_id'           => $plan->egg_id,
            'nest_id'          => $egg->nest_id,
            'startup'          => $plan->startup ?: $egg->startup,
            'image'            => $plan->image ?: (is_array($egg->docker_images) ? array_values($egg->docker_images)[0] : ''),
            'memory'           => $plan->memory,
            'swap'             => $plan->swap ?? 0,
            'disk'             => $plan->disk,
            'io'               => $plan->io ?? 500,
            'cpu'              => $plan->cpu,
            'threads'          => null,
            'oom_disabled'     => true,
            'database_limit'   => $plan->databases ?? 0,
            'allocation_limit' => $plan->allocations ?? 1,
            'backup_limit'     => $plan->backups ?? 0,
            'environment'      => $environment,
            'skip_scripts'     => false,
            'start_on_completion' => true,
        ];

        try {
            $server = $this->serverCreationService->handle($data, $deployment);

            Log::info("[Billing] Server #{$server->id} berhasil dibuat untuk user {$user->username} (order {$order->order_id}).");
        } catch (NoViableNodeException $e) {
            Log::error("[Billing] Tidak ada node yang tersedia untuk order {$order->order_id}: " . $e->getMessage());
            throw $e;
        } catch (NoViableAllocationException $e) {
            Log::error("[Billing] Tidak ada allocation yang tersedia untuk order {$order->order_id}: " . $e->getMessage());
            throw $e;
        }
    }
}
