<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifySupabaseWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Option 1: Verify using a shared secret token
        $expectedToken = config('services.supabase.webhook_secret');
        $providedToken = $request->header('X-Webhook-Secret') ?? $request->header('Authorization');

        if ($providedToken) {
            $providedToken = str_replace('Bearer ', '', $providedToken);
        }

        if (!$expectedToken || !hash_equals($expectedToken, $providedToken ?? '')) {
            Log::warning('Invalid webhook authentication', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all()
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Option 2: Verify by IP whitelist (additional security)
        $allowedIPs = config('services.supabase.allowed_webhook_ips', []);
        if (!empty($allowedIPs) && !in_array($request->ip(), $allowedIPs)) {
            Log::warning('Webhook from unauthorized IP', [
                'ip' => $request->ip(),
                'allowed_ips' => $allowedIPs
            ]);

            return response()->json(['error' => 'IP not allowed'], 403);
        }

        // Option 3: Verify webhook source header
        $expectedSource = 'supabase-stripe';
        $webhookSource = $request->header('X-Webhook-Source');

        if ($webhookSource !== $expectedSource) {
            Log::warning('Invalid webhook source', [
                'expected' => $expectedSource,
                'received' => $webhookSource,
                'ip' => $request->ip()
            ]);

            return response()->json(['error' => 'Invalid source'], 400);
        }

        return $next($request);
    }
}
