<?php

namespace JntExpress\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use JntExpress\Models\JntShipment;
use JntExpress\Services\ShipmentService;
use JntExpress\Support\Settings;

class ShipmentController extends Controller
{
    public function index(Request $request, Settings $settings)
    {
        $shipments = JntShipment::query()
            ->with('shippable')
            ->search($request->query('q'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('jnt::shipments.index', [
            'shipments' => $shipments,
            'insuranceAllowed' => $settings->insuranceAllowed(),
            'statuses' => \JntExpress\Enums\ShipmentStatus::cases(),
        ]);
    }

    public function show(JntShipment $shipment)
    {
        return view('jnt::shipments.show', ['shipment' => $shipment->load('events', 'shippable')]);
    }

    /** "Order to J&T" / "Order to J&T with Insurance" */
    public function store(Request $request, ShipmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'orders' => ['required'],
            'insurance' => ['nullable', 'boolean'],
        ]);

        $ids = is_array($data['orders'])
            ? $data['orders']
            : preg_split('/[\s,]+/', (string) $data['orders'], -1, PREG_SPLIT_NO_EMPTY);

        try {
            $results = $service->create($ids, $request->boolean('insurance'));
        } catch (\DomainException $e) {
            return back()->with('jnt.results', [['level' => 'error', 'text' => $e->getMessage()]]);
        }

        $messages = $results->map(fn ($r) => [
            'level' => $r['success'] ? 'success' : 'warning',
            'text' => '#' . $r['key'] . ' ' . $r['message'],
        ])->all();

        $redirect = back()->with('jnt.results', $messages);

        $created = $results->where('success', true)->pluck('shipment.id')->all();
        if ($created && config('jnt.auto_print')) {
            // original plugin immediately downloaded the thermal label for newly created orders
            $redirect->with('jnt.print_url', route(config('jnt.routes.name') . 'shipments.print.bulk', ['ids' => $created, 'format' => 'thermal']));
        }

        return $redirect;
    }

    public function cancel(Request $request, ShipmentService $service): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        try {
            $results = $service->cancel($data['ids']);
        } catch (\DomainException $e) {
            return back()->with('jnt.results', [['level' => 'error', 'text' => $e->getMessage()]]);
        }

        return back()->with('jnt.results', $results->map(fn ($r) => [
            'level' => $r['success'] ? 'success' : 'warning',
            'text' => '#' . $r['key'] . ' ' . $r['message'],
        ])->all());
    }

    public function printOne(JntShipment $shipment, string $format, ShipmentService $service)
    {
        return $this->pdf($service, [$shipment], $format);
    }

    public function printBulk(Request $request, ShipmentService $service)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'format' => ['nullable', 'in:consignment,thermal,a4'],
        ]);

        return $this->pdf($service, $data['ids'], $data['format'] ?? 'consignment');
    }

    protected function pdf(ShipmentService $service, array $shipments, string $format)
    {
        abort_unless(in_array($format, ['consignment', 'thermal', 'a4'], true), 404);

        try {
            $pdf = $service->print($shipments, $format);
        } catch (\Throwable $e) {
            return back()->with('jnt.results', [['level' => 'error', 'text' => $e->getMessage()]]);
        }

        $name = 'jnt-' . $format . '-' . now()->format('YmdHis') . '.pdf';

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($pdf),
            'Content-Disposition' => config('jnt.print_disposition', 'attachment') . '; filename="' . $name . '"',
        ]);
    }
}
