<?php

namespace JntExpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JntExpress\Services\RateService;
use JntExpress\Support\Settings;

/**
 * Checkout shipping-rate endpoint.
 *
 * POST /jnt/rates  {postcode, weight, unit?, country?}  or  {postcode, items:[{weight,quantity}], unit?}
 */
class RateController extends Controller
{
    public function __invoke(Request $request, RateService $rates, Settings $settings): JsonResponse
    {
        $data = $request->validate([
            'postcode' => ['required', 'string', 'max:10'],
            'country' => ['nullable', 'string', 'size:2'],
            'unit' => ['nullable', 'in:kg,g,lb,oz'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.weight' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $unit = $data['unit'] ?? 'kg';
        $kg = isset($data['items'])
            ? $rates->cartWeightKg($data['items'], $unit)
            : \JntExpress\Support\Weight::toKg((float) ($data['weight'] ?? 0), $unit);

        $cost = $rates->quote($kg, $data['postcode'], $data['country'] ?? 'MY');

        if ($cost === null) {
            return response()->json(['available' => false, 'message' => 'J&T rate unavailable'], 422);
        }

        return response()->json([
            'available' => true,
            'id' => 'jnt',
            'label' => $settings->get('title', 'J&T Express'),
            'cost' => $cost,
            'currency' => 'MYR',
            'weight_kg' => round(max($kg, 0.01), 3),
        ]);
    }
}
