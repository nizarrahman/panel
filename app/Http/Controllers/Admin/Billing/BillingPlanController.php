<?php

namespace Pterodactyl\Http\Controllers\Admin\Billing;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Models\BillingPlan;
use Pterodactyl\Http\Controllers\Controller;
use Prologue\Alerts\AlertsMessageBag;

class BillingPlanController extends Controller
{
    public function __construct(private AlertsMessageBag $alert)
    {
    }

    public function index(): View
    {
        return view('admin.billing.plans.index', [
            'plans' => BillingPlan::orderBy('sort_order')->orderBy('price')->get(),
        ]);
    }

    public function create(): View
    {
        $eggs = \Pterodactyl\Models\Egg::with('nest', 'variables')->orderBy('name')->get();
        $locations = \Pterodactyl\Models\Location::orderBy('short')->get();
        return view('admin.billing.plans.edit', ['plan' => null, 'eggs' => $eggs, 'locations' => $locations]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'description'   => 'nullable|string',
            'price'         => 'required|integer|min:0',
            'memory'        => 'required|integer|min:128',
            'cpu'           => 'required|integer|min:1',
            'disk'          => 'required|integer|min:512',
            'databases'     => 'required|integer|min:0',
            'backups'       => 'required|integer|min:0',
            'allocations'   => 'required|integer|min:1',
            'egg_id'        => 'nullable|exists:eggs,id',
            'startup'       => 'nullable|string',
            'image'         => 'nullable|string|max:191',
            'environment'   => 'nullable|array',
            'location_ids'  => 'nullable|array',
            'swap'          => 'required|integer|min:-1',
            'io'            => 'required|integer|between:10,1000',
            'duration_days' => 'required|integer|min:1',
            'sort_order'    => 'required|integer|min:0',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        BillingPlan::create($data);

        $this->alert->success('Plan berhasil dibuat.')->flash();

        return redirect()->route('admin.billing.plans');
    }

    public function edit(BillingPlan $plan): View
    {
        $eggs = \Pterodactyl\Models\Egg::with('nest', 'variables')->orderBy('name')->get();
        $locations = \Pterodactyl\Models\Location::orderBy('short')->get();
        return view('admin.billing.plans.edit', ['plan' => $plan, 'eggs' => $eggs, 'locations' => $locations]);
    }

    public function update(Request $request, BillingPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:191',
            'description'   => 'nullable|string',
            'price'         => 'required|integer|min:0',
            'memory'        => 'required|integer|min:128',
            'cpu'           => 'required|integer|min:1',
            'disk'          => 'required|integer|min:512',
            'databases'     => 'required|integer|min:0',
            'backups'       => 'required|integer|min:0',
            'allocations'   => 'required|integer|min:1',
            'egg_id'        => 'nullable|exists:eggs,id',
            'startup'       => 'nullable|string',
            'image'         => 'nullable|string|max:191',
            'environment'   => 'nullable|array',
            'location_ids'  => 'nullable|array',
            'swap'          => 'required|integer|min:-1',
            'io'            => 'required|integer|between:10,1000',
            'duration_days' => 'required|integer|min:1',
            'sort_order'    => 'required|integer|min:0',
            'is_active'     => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', false);

        $plan->update($data);

        $this->alert->success('Plan berhasil diperbarui.')->flash();

        return redirect()->route('admin.billing.plans');
    }

    public function destroy(BillingPlan $plan): RedirectResponse
    {
        $plan->delete();
        $this->alert->success('Plan berhasil dihapus.')->flash();

        return redirect()->route('admin.billing.plans');
    }
}
