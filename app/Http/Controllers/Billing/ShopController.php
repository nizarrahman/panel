<?php

namespace Pterodactyl\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Pterodactyl\Models\BillingPlan;
use Pterodactyl\Models\BillingOrder;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Billing\BillingSettingsService;
use Pterodactyl\Services\Billing\MidtransService;

class ShopController extends Controller
{
    public function __construct(
        private BillingSettingsService $billingSettings,
        private MidtransService $midtrans,
    ) {
    }

    /**
     * Halaman shop untuk user.
     */
    public function index(): View
    {
        abort_unless($this->billingSettings->isEnabled(), 404);

        $plans = BillingPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return view('billing.shop', [
            'plans'            => $plans,
            'clientKey'        => $this->billingSettings->getMidtransClientKey(),
            'isProduction'     => $this->billingSettings->isMidtransProduction(),
            'shopTitle'        => $this->billingSettings->get('shop_title', 'Shop'),
            'shopDescription'  => $this->billingSettings->get('shop_description', ''),
        ]);
    }

    /**
     * Buat order baru dan kembalikan Snap token.
     */
    public function createOrder(Request $request): JsonResponse
    {
        abort_unless($this->billingSettings->isEnabled(), 404);

        $request->validate([
            'plan_id' => 'required|exists:billing_plans,id',
        ]);

        $plan = BillingPlan::findOrFail($request->input('plan_id'));

        abort_unless($plan->is_active, 422, 'Plan ini tidak tersedia.');

        /** @var \Pterodactyl\Models\User $user */
        $user = Auth::user();

        // Buat order record
        $order = BillingOrder::create([
            'order_id'   => 'ORDER-' . strtoupper(Str::random(12)) . '-' . time(),
            'user_id'    => $user->id,
            'plan_id'    => $plan->id,
            'amount'     => $plan->price,
            'status'     => 'pending',
            'expires_at' => now()->addHours(24),
        ]);

        // Dapatkan snap token dari Midtrans
        try {
            $snapToken = $this->midtrans->createSnapToken($order);
            $order->update(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            $order->update(['status' => 'failed']);
            return response()->json(['error' => 'Gagal menghubungi payment gateway: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'snap_token' => $snapToken,
            'order_id'   => $order->order_id,
        ]);
    }

    /**
     * Riwayat order user.
     */
    public function orders(): View
    {
        $orders = BillingOrder::with('plan')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('billing.orders', ['orders' => $orders]);
    }
}
