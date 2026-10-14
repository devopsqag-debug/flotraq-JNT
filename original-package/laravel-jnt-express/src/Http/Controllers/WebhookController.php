<?php

namespace JntExpress\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JntExpress\Services\WebhookProcessor;

class WebhookController extends Controller
{
    public function __invoke(Request $request, WebhookProcessor $processor): JsonResponse
    {
        $biz = $request->input('bizContent');
        $data = is_array($biz) ? $biz : json_decode((string) $biz, true);

        $awb = is_array($data) ? trim((string) ($data['billCode'] ?? '')) : '';

        if ($awb === '') {
            return response()->json(WebhookProcessor::fail('Invalid Request!'));
        }

        $scan = $data['details'][0]['scanTypeCode'] ?? null;

        return response()->json($processor->process($awb, $scan));
    }
}
