<?php

namespace App\Console\Commands;

use App\Services\WildberriesApiService;
use Illuminate\Console\Command;

class CollectWildberriesData extends Command
{
    protected $signature = 'wb:collect
                            {--entity= : Specific entity to collect (sales, orders, stocks, incomes)}
                            {--all : Collect from all entities}
                            {--date-from= : Start date (Y-m-d format)}
                            {--date-to= : End date (Y-m-d format)}
                            {--verbose : Show verbose output}';

    protected $description = 'Collect data from Wildberries API and store in database';

    public function handle(WildberriesApiService $service): int
    {
        $service->setVerbose($this->option('verbose'));

        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        if ($entity = $this->option('entity')) {
            return $this->collectEntity($service, $entity, $dateFrom, $dateTo);
        }

        if ($this->option('all')) {
            return $this->collectAll($service, $dateFrom, $dateTo);
        }

        $this->showUsage();
        return self::SUCCESS;
    }

    protected function collectEntity(WildberriesApiService $service, string $entity, ?string $dateFrom, ?string $dateTo): int
    {
        $this->info("Collecting {$entity} data...");

        $result = match (strtolower($entity)) {
            'sales' => $service->collectSales($dateFrom, $dateTo),
            'orders' => $service->collectOrders($dateFrom, $dateTo),
            'stocks' => $service->collectStocks(),
            'incomes' => $service->collectIncomes($dateFrom, $dateTo),
            default => null,
        };

        if ($result === null) {
            $this->error("Unknown entity: {$entity}");
            $this->line('Available entities: sales, orders, stocks, incomes');
            return self::FAILURE;
        }

        $this->displayResult($entity, $result);
        return self::SUCCESS;
    }

    protected function collectAll(WildberriesApiService $service, ?string $dateFrom, ?string $dateTo): int
    {
        $this->info('Collecting data from all entities...');
        $this->newLine();

        $results = $service->collectAll($dateFrom, $dateTo);

        foreach ($results as $entity => $result) {
            $this->displayResult($entity, $result);
            $this->newLine();
        }

        $this->info('Collection complete!');
        return self::SUCCESS;
    }

    protected function displayResult(string $entity, array $result): void
    {
        $this->info(ucfirst($entity) . ':');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Collected', $result['total_collected']],
                ['Created', $result['total_created']],
                ['Updated', $result['total_updated']],
                ['Pages Processed', $result['total_pages']],
                ['Errors', count($result['errors'])],
            ]
        );

        if (!empty($result['errors'])) {
            $this->warn('Errors:');
            foreach ($result['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }
    }

    protected function showUsage(): void
    {
        $this->info('Usage:');
        $this->line('  php artisan wb:collect --all                           # Collect from all entities');
        $this->line('  php artisan wb:collect --entity=sales                  # Collect only sales');
        $this->line('  php artisan wb:collect --entity=orders                 # Collect only orders');
        $this->line('  php artisan wb:collect --entity=stocks                 # Collect only stocks (current day only)');
        $this->line('  php artisan wb:collect --entity=incomes                # Collect only incomes');
        $this->line('  php artisan wb:collect --all --date-from=2024-01-01    # With custom start date');
        $this->line('  php artisan wb:collect --all --verbose                 # With verbose output');
        $this->newLine();
        $this->info('Available entities:');
        $this->line('  sales   - Sales data (requires dateFrom, dateTo)');
        $this->line('  orders  - Orders data (requires dateFrom, dateTo)');
        $this->line('  stocks  - Stock/inventory data (current day only)');
        $this->line('  incomes - Income data (requires dateFrom, dateTo)');
    }
}
