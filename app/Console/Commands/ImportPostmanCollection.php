<?php

namespace App\Console\Commands;

use App\Services\PostmanCollectionService;
use Illuminate\Console\Command;

class ImportPostmanCollection extends Command
{
    protected $signature = 'postman:import
                            {--file= : Path to Postman collection JSON file}
                            {--url= : URL to Postman collection JSON}
                            {--verbose : Show verbose output}';

    protected $description = 'Import a Postman collection and create API endpoints';

    public function handle(PostmanCollectionService $service): int
    {
        $service->setVerbose($this->option('verbose'));

        if ($file = $this->option('file')) {
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                return self::FAILURE;
            }

            $this->info("Importing Postman collection from file: {$file}");

            try {
                $collection = $service->importFromFile($file);
                $this->displayResults($collection);
                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Import failed: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        if ($url = $this->option('url')) {
            $this->info("Importing Postman collection from URL: {$url}");

            try {
                $collection = $service->importFromUrl($url);
                $this->displayResults($collection);
                return self::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Import failed: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info('Usage:');
        $this->line('  php artisan postman:import --file=/path/to/collection.json');
        $this->line('  php artisan postman:import --url=https://example.com/collection.json');
        $this->line('  php artisan postman:import --file=/path/to/collection.json --verbose');

        return self::SUCCESS;
    }

    protected function displayResults($collection): void
    {
        $this->info('');
        $this->info("Collection imported successfully!");
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $collection->id],
                ['Name', $collection->name],
                ['Total Items', $collection->items()->count()],
                ['Folders', $collection->folders()->count()],
                ['Requests', $collection->requests()->count()],
            ]
        );
    }
}
