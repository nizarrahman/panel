<?php

namespace Pterodactyl\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\BillingOrder;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Billing\MidtransService;
use Pterodactyl\Services\Billing\AutoProvisionService;

class MidtransWebhookController extends Controller
{
    public function __construct(
        private MidtransService $midtrans,
        private AutoProvisionService $autoProvision,
    ) {
    }

    /**
     * Terima notifikasi pembayaran dari Midtrans.
     */
    public function __invoke(Request $request): Response
    {
        try {
            $notification = $this->midtrans->handleNotification();
        } catch (\Exception $e) {
            Log::error('[Billing] Midtrans webhook error: ' . $e->getMessage());
            return response('Error', 500);
        }

        $orderId       = $notification['order_id'];
        $txStatus      = $notification['status'];
        $fraudStatus   = $notification['fraud_status'];
        $paymentType   = $notification['payment_type'];

        $order = BillingOrder::where('order_id', $orderId)->first();

        if (!$order) {
            Log::warning('[Billing] Order not found: ' . $orderId);
            return response('Order not found', 404);
        }

        $wasPaid = $order->isPaid();

        // Update status order
        if ($txStatus === 'capture' || $txStatus === 'settlement') {
            if ($fraudStatus === 'accept' || $txStatus === 'settlement') {
                if (!$wasPaid) {
                    $order->update([
                        'status'       => 'paid',
                        'payment_type' => $paymentType,
                        'paid_at'      => now(),
                    ]);

                    // Auto-provision server setelah bayar
                    $this->provisionServer($order->fresh());
                }
            }
        } elseif (in_array($txStatus, ['cancel', 'deny', 'expire'])) {
            $order->update(['status' => $txStatus === 'expire' ? 'expired' : 'failed']);
        } elseif ($txStatus === 'pending') {
            $order->update(['status' => 'pending']);
        }

        return response('OK', 200);
    }

    /**
     * Provision server — catch error supaya webhook tetap return 200 ke Midtrans.
     */
    private function provisionServer(BillingOrder $order): void
    {
        try {
            $this->autoProvision->handle($order);
        } catch (\Throwable $e) {
            // Log tapi jangan throw — webhook harus tetap 200
            Log::error("[Billing] Auto-provision gagal untuk order {$order->order_id}: " . $e->getMessage());
        }
    }
}
