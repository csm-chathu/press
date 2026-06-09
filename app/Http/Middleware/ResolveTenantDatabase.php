<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $host    = strtolower($request->getHost()); // strips port
        $tenants = config('tenants', []);

        if (isset($tenants[$host])) {
            $database = $tenants[$host];

            // Switch the active database for this request
            config(['database.connections.mysql.database' => $database]);
            DB::purge('mysql');
        } else {
            // Unknown domain — reject with a clear error rather than
            // silently serving another tenant's data
            abort(404, "No database configured for host: {$host}");
        }

        return $next($request);
    }
}
