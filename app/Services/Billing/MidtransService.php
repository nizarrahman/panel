<?php

namespace Pterodactyl\Services\Billing;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use Pterodactyl\Models\BillingOrder;

class MidtransService
{
    public function __construct(private BillingSettingsService $settings)
    {
        $this->configure();
    }

    private function configure(): void
    {
        Config::$serverKey = $this->settings->getMidtransServerKey();
        Config::$clientKey = $this->settings->getMidtransClientKey();
        Config::$isProduction = $this->settings->isMidtransProduction();
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Buat Snap token untuk order baru.
     */
    public function createSnapToken(BillingOrder $order): string
    {
        $this->configure();

        $params = [
            'transaction_details' => [
                'order_id'     => $order->order_id,
                'gross_amount' => $order->amount,
            ],
            'customer_details' => [
                'first_name' => $order->user->name_first,
                'last_name'  => $order->user->name_last,
                'email'      => $order->user->email,
            ],
            'item_details' => [
                [
                    'id'       => 'PLAN-' . $order->plan_id,
                    'price'    => $order->amount,
                    'quantity' => 1,
                    'name'     => $order->plan->name,
                ],
            ],
        ];

        return Snap::getSnapToken($params);
    }

    /**
     * Handle notifikasi dari Midtrans webhook.
     */
    public function handleNotification(): array
    {
        $this->configure();

        $notification = new Notification();

        return [
            'order_id'     => $notification->order_id,
            'status'       => $notification->transaction_status,
            'fraud_status' => $notification->fraud_status,
            'payment_type' => $notification->payment_type,
        ];
    }
}
