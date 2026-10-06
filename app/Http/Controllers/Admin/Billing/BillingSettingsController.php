<?php

namespace Pterodactyl\Http\Controllers\Admin\Billing;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Billing\BillingSettingsService;
use Prologue\Alerts\AlertsMessageBag;

class BillingSettingsController extends Controller
{
    public function __construct(
        private BillingSettingsService $billingSettings,
        private AlertsMessageBag $alert,
    ) {
    }

    public function index(): View
    {
        return view('admin.billing.settings', [
            'settings' => $this->billingSettings->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'midtrans_server_key' => 'nullable|string',
            'midtrans_client_key' => 'nullable|string',
        ]);

        $this->billingSettings->set('billing_enabled', $request->boolean('billing_enabled') ? '1' : '0');
        $this->billingSettings->set('midtrans_server_key', $request->input('midtrans_server_key', ''));
        $this->billingSettings->set('midtrans_client_key', $request->input('midtrans_client_key', ''));
        $this->billingSettings->set('midtrans_production', $request->boolean('midtrans_production') ? '1' : '0');
        $this->billingSettings->set('shop_title', $request->input('shop_title', 'Shop'));
        $this->billingSettings->set('shop_description', $request->input('shop_description', ''));

        $this->alert->success('Pengaturan billing berhasil disimpan.')->flash();

        return redirect()->route('admin.billing.settings');
    }
}
