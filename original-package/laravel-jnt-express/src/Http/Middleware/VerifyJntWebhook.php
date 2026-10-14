<?php

namespace JntExpress\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JntExpress\Services\WebhookProcessor;

class VerifyJntWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $account = $request->header('apiAccount');

        if (!$account) {
            return response()->json(WebhookProcessor::fail('Invalid Account!'));
        }

        $expected = config('jnt.webhook.api_account');
        if ($expected && !hash_equals((string) $expected, (string) $account)) {
            return response()->json(WebhookProcessor::fail('Invalid Account!'));
        }

        return $next($request);
    }
}
