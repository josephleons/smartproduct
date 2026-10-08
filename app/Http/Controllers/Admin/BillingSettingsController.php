<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BillingSettingsRequest;
use App\Services\Billing\BillingSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingSettingsController extends Controller
{
    public function edit(BillingSettings $settings): View
    {
        return view('admin.billing.settings', [
            'settings' => $settings->all(),
            'gateways' => array_keys(config('billing.gateways')),
        ]);
    }

    public function update(BillingSettingsRequest $request, BillingSettings $settings): RedirectResponse
    {
        $settings->update($request->validated());

        return back()->with('success', __('Billing settings saved.'));
    }
}
