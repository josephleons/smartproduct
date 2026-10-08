<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanySettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $company = $request->user()->company;
        $this->authorize('update', $company);

        return view('settings.company', ['company' => $company]);
    }

    public function update(CompanySettingsRequest $request): RedirectResponse
    {
        $company = $request->user()->company;
        $this->authorize('update', $company);

        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->boolean('remove_logo') || $request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->hasFile('logo')
                ? $request->file('logo')->store("logos/{$company->id}", 'public')
                : null;
        }

        $company->update($data);

        return back()->with('success', __('Company profile saved.'));
    }
}
