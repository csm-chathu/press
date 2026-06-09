<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Database\Seeders\TenantSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    public function create(Request $request)
    {
        // Master-key guard
        $masterKey = config('app.tenant_master_key');
        if (!$masterKey || $request->header('X-Tenant-Key') !== $masterKey) {
            abort(403, 'Invalid or missing X-Tenant-Key header.');
        }

        $data = $request->validate([
            'domain'   => 'required|string',
            'database' => 'required|string|regex:/^[a-zA-Z0-9_]+$/',
            'password' => 'nullable|string|min:6',
        ]);

        $domain   = $data['domain'];
        $database = $data['database'];
        $password = $data['password'] ?? 'password';

        // 1. Create database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 2. Switch connection
        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        // 3. Run migrations
        Artisan::call('migrate', ['--force' => true]);

        // 4. Seed minimal data
        $seeder = new TenantSeeder();
        $seeder->setContainer(app())->run();

        // Override admin password
        \App\Models\User::where('email', 'admin@store.local')
            ->update(['password' => bcrypt($password)]);

        // 5. Register domain in config/tenants.php
        $configPath = config_path('tenants.php');
        $contents   = file_get_contents($configPath);
        $entry      = "    '{$domain}' => '{$database}',";

        $alreadyRegistered = str_contains($contents, "'{$domain}'");
        if (!$alreadyRegistered) {
            $contents = str_replace("];\n", "{$entry}\n];\n", $contents);
            file_put_contents($configPath, $contents);
        }

        // 6. Clear config cache so new domain is picked up immediately
        Artisan::call('config:clear');

        return response()->json([
            'message'            => 'Tenant created successfully.',
            'domain'             => $domain,
            'database'           => $database,
            'login_email'        => 'admin@store.local',
            'login_password'     => $password,
            'already_registered' => $alreadyRegistered,
        ], 201);
    }
}
