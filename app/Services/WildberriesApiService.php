<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Income;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WildberriesApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $limit;
    protected bool $verbose = false;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wildberries.base_url', 'http://109.73.206.144:6969'), '/');
        $this->apiKey = config('services.wildberries.api_key', 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie');
        $this->limit = config('services.wildberries.limit', 500);
    }

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * Collect sales data from API
     *
     * @param string|null $dateFrom Start date (Y-m-d format)
     * @param string|null $dateTo End date (Y-m-d format)
     * @return array Statistics about collected data
     */
    public function collectSales(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? Carbon::now()->subMonth()->format('Y-m-d');
        $dateTo = $dateTo ?? Carbon::now()->format('Y-m-d');

        $this->log("Collecting sales from {$dateFrom} to {$dateTo}");

        return $this->collectPaginatedData('/api/sales', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], function (array $item) {
            return Sale::updateOrCreate(
                ['sale_id' => $item['sale_id']],
                $this->mapSaleData($item)
            );
        });
    }

    /**
     * Collect orders data from API
     *
     * @param string|null $dateFrom Start date (Y-m-d format)
     * @param string|null $dateTo End date (Y-m-d format)
     * @return array Statistics about collected data
     */
    public function collectOrders(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? Carbon::now()->subMonth()->format('Y-m-d');
        $dateTo = $dateTo ?? Carbon::now()->format('Y-m-d');

        $this->log("Collecting orders from {$dateFrom} to {$dateTo}");

        return $this->collectPaginatedData('/api/orders', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], function (array $item) {
            return Order::updateOrCreate(
                [
                    'g_number' => $item['g_number'],
                    'date' => $item['date'],
                ],
                $this->mapOrderData($item)
            );
        });
    }

    /**
     * Collect stocks data from API (only current day)
     *
     * @return array Statistics about collected data
     */
    public function collectStocks(): array
    {
        $today = Carbon::now()->format('Y-m-d');

        $this->log("Collecting stocks for {$today}");

        return $this->collectPaginatedData('/api/stocks', [
            'dateFrom' => $today,
            'dateTo' => $today,
        ], function (array $item) {
            return Stock::updateOrCreate(
                [
                    'date' => $item['date'],
                    'barcode' => $item['barcode'],
                    'warehouse_name' => $item['warehouse_name'],
                ],
                $this->mapStockData($item)
            );
        });
    }

    /**
     * Collect incomes data from API
     *
     * @param string|null $dateFrom Start date (Y-m-d format)
     * @param string|null $dateTo End date (Y-m-d format)
     * @return array Statistics about collected data
     */
    public function collectIncomes(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? Carbon::now()->subMonth()->format('Y-m-d');
        $dateTo = $dateTo ?? Carbon::now()->format('Y-m-d');

        $this->log("Collecting incomes from {$dateFrom} to {$dateTo}");

        return $this->collectPaginatedData('/api/incomes', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], function (array $item) {
            return Income::updateOrCreate(
                [
                    'income_id' => $item['income_id'],
                    'barcode' => $item['barcode'],
                    'warehouse_name' => $item['warehouse_name'],
                ],
                $this->mapIncomeData($item)
            );
        });
    }

    /**
     * Collect all data from all endpoints
     *
     * @param string|null $dateFrom Start date (Y-m-d format)
     * @param string|null $dateTo End date (Y-m-d format)
     * @return array Statistics about collected data for all entities
     */
    public function collectAll(?string $dateFrom = null, ?string $dateTo = null): array
    {
        return [
            'sales' => $this->collectSales($dateFrom, $dateTo),
            'orders' => $this->collectOrders($dateFrom, $dateTo),
            'stocks' => $this->collectStocks(),
            'incomes' => $this->collectIncomes($dateFrom, $dateTo),
        ];
    }

    /**
     * Generic method to collect paginated data from API
     *
     * @param string $endpoint API endpoint path
     * @param array $params Query parameters
     * @param callable $saveCallback Callback to save each item
     * @return array Statistics
     */
    protected function collectPaginatedData(string $endpoint, array $params, callable $saveCallback): array
    {
        $page = 1;
        $totalCollected = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $errors = [];

        do {
            $this->log("Fetching page {$page} from {$endpoint}");

            $response = $this->makeRequest($endpoint, array_merge($params, [
                'page' => $page,
                'limit' => $this->limit,
            ]));

            if (!$response['success']) {
                $errors[] = "Page {$page}: " . $response['error'];
                $this->log("Error on page {$page}: " . $response['error'], 'error');
                break;
            }

            $data = $response['data']['data'] ?? [];
            $meta = $response['data']['meta'] ?? [];

            foreach ($data as $item) {
                try {
                    $model = $saveCallback($item);
                    $totalCollected++;

                    if ($model->wasRecentlyCreated) {
                        $totalCreated++;
                    } else {
                        $totalUpdated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Item error: " . $e->getMessage();
                    $this->log("Error saving item: " . $e->getMessage(), 'error');
                }
            }

            $this->log("Page {$page}: collected " . count($data) . " items");

            $currentPage = $meta['current_page'] ?? $page;
            $lastPage = $meta['last_page'] ?? $page;

            $page++;
        } while ($currentPage < $lastPage);

        $this->log("Collection complete: {$totalCollected} items ({$totalCreated} created, {$totalUpdated} updated)");

        return [
            'total_collected' => $totalCollected,
            'total_created' => $totalCreated,
            'total_updated' => $totalUpdated,
            'total_pages' => $page - 1,
            'errors' => $errors,
        ];
    }

    /**
     * Make HTTP request to API
     *
     * @param string $endpoint API endpoint path
     * @param array $params Query parameters
     * @return array Response with success flag and data/error
     */
    protected function makeRequest(string $endpoint, array $params = []): array
    {
        $params['key'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint;

        try {
            $response = Http::timeout(60)->get($url, $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: " . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Map API sale data to model attributes
     */
    protected function mapSaleData(array $item): array
    {
        return [
            'g_number' => $item['g_number'] ?? null,
            'date' => $item['date'] ?? null,
            'last_change_date' => $item['last_change_date'] ?? null,
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'total_price' => $item['total_price'] ?? null,
            'discount_percent' => $item['discount_percent'] ?? null,
            'is_supply' => $item['is_supply'] ?? null,
            'is_realization' => $item['is_realization'] ?? null,
            'promo_code_discount' => $item['promo_code_discount'] ?? null,
            'warehouse_name' => $item['warehouse_name'] ?? null,
            'country_name' => $item['country_name'] ?? null,
            'oblast_okrug_name' => $item['oblast_okrug_name'] ?? null,
            'region_name' => $item['region_name'] ?? null,
            'income_id' => $item['income_id'] ?? null,
            'sale_id' => $item['sale_id'] ?? null,
            'odid' => $item['odid'] ?? null,
            'spp' => $item['spp'] ?? null,
            'for_pay' => $item['for_pay'] ?? null,
            'finished_price' => $item['finished_price'] ?? null,
            'price_with_disc' => $item['price_with_disc'] ?? null,
            'nm_id' => $item['nm_id'] ?? null,
            'subject' => $item['subject'] ?? null,
            'category' => $item['category'] ?? null,
            'brand' => $item['brand'] ?? null,
            'is_storno' => $item['is_storno'] ?? null,
        ];
    }

    /**
     * Map API order data to model attributes
     */
    protected function mapOrderData(array $item): array
    {
        return [
            'g_number' => $item['g_number'] ?? null,
            'date' => $item['date'] ?? null,
            'last_change_date' => $item['last_change_date'] ?? null,
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'total_price' => $item['total_price'] ?? null,
            'discount_percent' => $item['discount_percent'] ?? null,
            'warehouse_name' => $item['warehouse_name'] ?? null,
            'oblast' => $item['oblast'] ?? null,
            'income_id' => $item['income_id'] ?? null,
            'odid' => $item['odid'] ?? null,
            'nm_id' => $item['nm_id'] ?? null,
            'subject' => $item['subject'] ?? null,
            'category' => $item['category'] ?? null,
            'brand' => $item['brand'] ?? null,
            'is_cancel' => $item['is_cancel'] ?? false,
            'cancel_dt' => $item['cancel_dt'] ?? null,
        ];
    }

    /**
     * Map API stock data to model attributes
     */
    protected function mapStockData(array $item): array
    {
        return [
            'date' => $item['date'] ?? null,
            'last_change_date' => $item['last_change_date'] ?? null,
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'quantity' => $item['quantity'] ?? 0,
            'is_supply' => $item['is_supply'] ?? null,
            'is_realization' => $item['is_realization'] ?? null,
            'quantity_full' => $item['quantity_full'] ?? null,
            'warehouse_name' => $item['warehouse_name'] ?? null,
            'in_way_to_client' => $item['in_way_to_client'] ?? null,
            'in_way_from_client' => $item['in_way_from_client'] ?? null,
            'nm_id' => $item['nm_id'] ?? null,
            'subject' => $item['subject'] ?? null,
            'category' => $item['category'] ?? null,
            'brand' => $item['brand'] ?? null,
            'sc_code' => $item['sc_code'] ?? null,
            'price' => $item['price'] ?? null,
            'discount' => $item['discount'] ?? null,
        ];
    }

    /**
     * Map API income data to model attributes
     */
    protected function mapIncomeData(array $item): array
    {
        return [
            'income_id' => $item['income_id'] ?? null,
            'number' => $item['number'] ?? null,
            'date' => $item['date'] ?? null,
            'last_change_date' => $item['last_change_date'] ?? null,
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'quantity' => $item['quantity'] ?? 0,
            'total_price' => $item['total_price'] ?? null,
            'date_close' => $item['date_close'] ?? null,
            'warehouse_name' => $item['warehouse_name'] ?? null,
            'nm_id' => $item['nm_id'] ?? null,
        ];
    }

    /**
     * Log message
     */
    protected function log(string $message, string $level = 'info'): void
    {
        if ($this->verbose) {
            echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
        }

        Log::$level("[WildberriesApi] {$message}");
    }
}
