<?php

namespace JntExpress\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JntExpress\Support\Settings;

class SettingsController extends Controller
{
    public function edit(Settings $settings)
    {
        return view('jnt::settings', ['s' => $settings->all(), 'secrets' => Settings::SECRETS]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'title' => ['nullable', 'string', 'max:100'],
            'vipcode' => ['nullable', 'string', 'max:100'],
            'apikey' => ['nullable', 'string', 'max:255'],
            'signkey' => ['nullable', 'string', 'max:255'],
            'ratekey' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:10'],
            'service' => ['required', 'in:1,6'],
            'insurance' => ['nullable', 'boolean'],
            'markup' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['insurance'] = $request->boolean('insurance');
        $data['markup'] = $data['markup'] ?? 0;

        // password-style fields: blank means "keep the stored value"
        foreach (Settings::SECRETS as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            }
        }

        $settings->set($data);

        return back()->with('jnt.results', [['level' => 'success', 'text' => 'J&T settings saved.']]);
    }
}
