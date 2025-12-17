<?php

namespace App\Console\Commands;

use App\Models\ApiEndpoint;
use App\Services\ApiCollectorService;
use Illuminate\Console\Command;

class CollectApiData extends Command
{
    protected $signature = 'api:collect
                            {--endpoint= : Collect from specific endpoint ID}
                            {--all : Collect from all active endpoints}
                            {--verbose : Show verbose output}';

    protected $description = 'Collect data from API endpoints and store responses in database';

    public function handle(ApiCollectorService $service): int
    {
        $service->setVerbose($this->option('verbose'));

        if ($endpointId = $this->option('endpoint')) {
            $endpoint = ApiEndpoint::find($endpointId);

            if (!$endpoint) {
                $this->error("Endpoint with ID {$endpointId} not found.");
                return self::FAILURE;
            }

            $this->info("Collecting data from endpoint: {$endpoint->name}");
            $response = $service->collectFromEndpoint($endpoint);

            if ($response->is_successful) {
                $this->info("Success! Status: {$response->status_code}, Time: {$response->response_time}s");
            } else {
                $this->error("Failed: {$response->error_message}");
            }

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $this->info('Collecting data from all active endpoints...');

            $responses = $service->collectFromAllActiveEndpoints();

            $successful = collect($responses)->filter(fn($r) => $r && $r->is_successful)->count();
            $failed = collect($responses)->filter(fn($r) => $r && !$r->is_successful)->count();

            $this->table(
                ['Total', 'Successful', 'Failed'],
                [[count($responses), $successful, $failed]]
            );

            return self::SUCCESS;
        }

        $this->info('Usage:');
        $this->line('  php artisan api:collect --all              # Collect from all active endpoints');
        $this->line('  php artisan api:collect --endpoint=1       # Collect from endpoint with ID 1');
        $this->line('  php artisan api:collect --all --verbose    # With verbose output');

        return self::SUCCESS;
    }
}
