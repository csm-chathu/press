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
        }
        // Unknown host: fall through using the default DB_DATABASE from .env
        // (the default mysql connection is already set to DB_DATABASE)

        return $next($request);
    }
}
