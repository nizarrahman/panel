<?php

namespace Pterodactyl\Http\Controllers\Admin\Billing;

use Illuminate\View\View;
use Pterodactyl\Models\BillingOrder;
use Pterodactyl\Http\Controllers\Controller;

class BillingOrderController extends Controller
{
    public function index(): View
    {
        $orders = BillingOrder::with(['user', 'plan'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.billing.orders', ['orders' => $orders]);
    }
}
