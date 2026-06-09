<?php

namespace App\Console\Commands;

use Database\Seeders\TenantSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantCreate extends Command
{
    protected $signature = 'tenant:create
                            {domain   : The hostname for this tenant (e.g. shop1.example.com)}
                            {database : The MySQL database name to create and use}
                            {--password=password : Initial admin password}';

    protected $description = 'Create a new tenant: create database, run migrations, seed login user, register domain';

    public function handle(): int
    {
        $domain   = $this->argument('domain');
        $database = $this->argument('database');
        $password = $this->option('password');

        // ── 1. Create the database ────────────────────────────────────────────
        $this->info("Creating database `{$database}`…");
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // ── 2. Switch connection to the new database ──────────────────────────
        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        // ── 3. Run migrations ─────────────────────────────────────────────────
        $this->info('Running migrations…');
        $this->call('migrate', ['--force' => true]);

        // ── 4. Seed chart of accounts + admin user ────────────────────────────
        $this->info('Seeding initial data…');
        $seeder = new TenantSeeder();
        $seeder->setContainer(app())->setCommand($this)->run();

        // Override the seeded password if a custom one was given
        if ($password !== 'password') {
            \App\Models\User::where('email', 'admin@store.local')
                ->update(['password' => bcrypt($password)]);
        }

        // ── 5. Register domain in config/tenants.php ─────────────────────────
        $this->info('Registering domain in config/tenants.php…');
        $configPath = config_path('tenants.php');
        $contents   = file_get_contents($configPath);

        $entry = "    '{$domain}' => '{$database}',";

        if (str_contains($contents, $entry)) {
            $this->warn("Domain `{$domain}` is already registered.");
        } else {
            // Insert before the closing bracket
            $contents = str_replace("];\n", "{$entry}\n];\n", $contents);
            file_put_contents($configPath, $contents);
            $this->info("Added: {$entry}");
        }

        // ── 6. Done ───────────────────────────────────────────────────────────
        $this->newLine();
        $this->components->twoColumnDetail('<fg=green;options=bold>Tenant ready</>', $domain);
        $this->components->twoColumnDetail('Database', $database);
        $this->components->twoColumnDetail('Login', 'admin@store.local');
        $this->components->twoColumnDetail('Password', $password);
        $this->newLine();
        $this->warn('Remember to run: php artisan config:clear');

        return self::SUCCESS;
    }
}
