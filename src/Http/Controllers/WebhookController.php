<?php

namespace ESolution\GoSend\Http\Controllers;

use ESolution\GoSend\Jobs\ProcessWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $tokenHeader = config('gosend.webhook.token_header', 'X-Callback-Token');
        $expectedToken = config('gosend.webhook.token');

        $receivedToken = $request->header($tokenHeader);

        if (! $expectedToken || $receivedToken !== $expectedToken) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        ProcessWebhook::dispatch($payload);

        return response()->json(['message' => 'ok']);
    }
}
