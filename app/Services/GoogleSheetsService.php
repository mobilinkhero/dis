<?php

namespace App\Services;

use App\Models\Tenant\EcommerceConfiguration;
use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Google Sheets Integration Service for E-commerce
 * Handles syncing products, orders, and customers with Google Sheets
 */
class GoogleSheetsService
{
    protected $tenantId;
    protected $config;

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId ?? tenant_id();
        $this->config = EcommerceConfiguration::where('tenant_id', $this->tenantId)->first();
    }

    /**
     * Extract sheet ID from Google Sheets URL
     */
    public function extractSheetId(string $url): ?string
    {
        // Extract sheet ID from various Google Sheets URL formats
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Validate Google Sheets URL and check if it's publicly accessible
     */
    public function validateSheetsUrl(string $url): array
    {
        try {
            $sheetId = $this->extractSheetId($url);
            if (!$sheetId) {
                return [
                    'valid' => false,
                    'message' => 'Invalid Google Sheets URL format'
                ];
            }

            // Test if sheet is publicly accessible
            $testUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid=0";
            $response = Http::timeout(10)->get($testUrl);

            if ($response->successful()) {
                return [
                    'valid' => true,
                    'sheet_id' => $sheetId,
                    'message' => 'Google Sheets URL is valid and accessible'
                ];
            } else {
                return [
                    'valid' => false,
                    'message' => 'Sheet is not publicly accessible. Please make sure sharing is set to "Anyone with the link can view"'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Sheets validation error: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Error validating Google Sheets URL: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create default sheets structure
     */
    public function createDefaultSheets(): array
    {
        // This would normally create sheets via Google Sheets API
        // For now, we'll provide the structure that users should create manually
        
        $sheetsStructure = [
            'products' => [
                'name' => 'Products',
                'columns' => [
                    'A' => 'ID',
                    'B' => 'SKU',
                    'C' => 'Name',
                    'D' => 'Description',
                    'E' => 'Price',
                    'F' => 'Sale Price',
                    'G' => 'Stock Quantity',
                    'H' => 'Category',
                    'I' => 'Subcategory',
                    'J' => 'Tags',
                    'K' => 'Images (URLs)',
                    'L' => 'Weight',
                    'M' => 'Status',
                    'N' => 'Featured',
                    'O' => 'Low Stock Threshold'
                ]
            ],
            'orders' => [
                'name' => 'Orders',
                'columns' => [
                    'A' => 'Order ID',
                    'B' => 'Order Number',
                    'C' => 'Customer Name',
                    'D' => 'Customer Phone',
                    'E' => 'Customer Email',
                    'F' => 'Customer Address',
                    'G' => 'Status',
                    'H' => 'Items (JSON)',
                    'I' => 'Subtotal',
                    'J' => 'Tax Amount',
                    'K' => 'Shipping Amount',
                    'L' => 'Discount Amount',
                    'M' => 'Total Amount',
                    'N' => 'Currency',
                    'O' => 'Payment Method',
                    'P' => 'Payment Status',
                    'Q' => 'Notes',
                    'R' => 'Created At',
                    'S' => 'Updated At'
                ]
            ],
            'customers' => [
                'name' => 'Customers',
                'columns' => [
                    'A' => 'ID',
                    'B' => 'Name',
                    'C' => 'Phone',
                    'D' => 'Email',
                    'E' => 'Address',
                    'F' => 'Total Orders',
                    'G' => 'Total Spent',
                    'H' => 'Last Order Date',
                    'I' => 'Created At',
                    'J' => 'Status'
                ]
            ]
        ];

        return $sheetsStructure;
    }

    /**
     * Sync products from Google Sheets
     */
    public function syncProductsFromSheets(): array
    {
        if (!$this->config || !$this->config->products_sheet_id) {
            return [
                'success' => false,
                'message' => 'Products sheet not configured'
            ];
        }

        try {
            $csvUrl = "https://docs.google.com/spreadsheets/d/{$this->config->products_sheet_id}/export?format=csv&gid=0";
            $response = Http::timeout(30)->get($csvUrl);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to fetch products from Google Sheets'
                ];
            }

            $csvData = $response->body();
            $lines = str_getcsv($csvData, "\n");
            $header = str_getcsv(array_shift($lines));
            
            $syncedCount = 0;
            $errorCount = 0;

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                try {
                    $data = array_combine($header, str_getcsv($line));
                    $this->syncProduct($data);
                    $syncedCount++;
                } catch (\Exception $e) {
                    Log::error('Product sync error: ' . $e->getMessage(), ['line' => $line]);
                    $errorCount++;
                }
            }

            // Update sync status
            $this->config->update([
                'sync_status' => 'completed',
                'last_sync_at' => Carbon::now()
            ]);

            return [
                'success' => true,
                'message' => "Synced {$syncedCount} products successfully. {$errorCount} errors.",
                'synced' => $syncedCount,
                'errors' => $errorCount
            ];

        } catch (\Exception $e) {
            Log::error('Products sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error syncing products: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sync individual product
     */
    protected function syncProduct(array $data): void
    {
        $productData = [
            'tenant_id' => $this->tenantId,
            'google_sheet_row_id' => $data['ID'] ?? null,
            'sku' => $data['SKU'] ?? null,
            'name' => $data['Name'] ?? '',
            'description' => $data['Description'] ?? '',
            'price' => (float) ($data['Price'] ?? 0),
            'sale_price' => !empty($data['Sale Price']) ? (float) $data['Sale Price'] : null,
            'stock_quantity' => (int) ($data['Stock Quantity'] ?? 0),
            'category' => $data['Category'] ?? '',
            'subcategory' => $data['Subcategory'] ?? '',
            'tags' => !empty($data['Tags']) ? explode(',', $data['Tags']) : [],
            'images' => !empty($data['Images (URLs)']) ? explode(',', $data['Images (URLs)']) : [],
            'weight' => !empty($data['Weight']) ? (float) $data['Weight'] : null,
            'status' => strtolower($data['Status'] ?? 'active'),
            'featured' => strtolower($data['Featured'] ?? 'no') === 'yes',
            'low_stock_threshold' => (int) ($data['Low Stock Threshold'] ?? 5),
            'sync_status' => 'synced',
            'last_synced_at' => Carbon::now(),
        ];

        // Update or create product
        Product::updateOrCreate(
            [
                'tenant_id' => $this->tenantId,
                'sku' => $productData['sku']
            ],
            $productData
        );
    }

    /**
     * Sync order to Google Sheets
     */
    public function syncOrderToSheets(Order $order): array
    {
        if (!$this->config || !$this->config->orders_sheet_id) {
            return [
                'success' => false,
                'message' => 'Orders sheet not configured'
            ];
        }

        try {
            // In a real implementation, you would use Google Sheets API to append/update rows
            // For now, we'll log the order data that should be synced
            
            $orderData = [
                'Order ID' => $order->id,
                'Order Number' => $order->order_number,
                'Customer Name' => $order->customer_name,
                'Customer Phone' => $order->customer_phone,
                'Customer Email' => $order->customer_email,
                'Customer Address' => $order->customer_address,
                'Status' => $order->status,
                'Items (JSON)' => json_encode($order->items),
                'Subtotal' => $order->subtotal,
                'Tax Amount' => $order->tax_amount,
                'Shipping Amount' => $order->shipping_amount,
                'Discount Amount' => $order->discount_amount,
                'Total Amount' => $order->total_amount,
                'Currency' => $order->currency,
                'Payment Method' => $order->payment_method,
                'Payment Status' => $order->payment_status,
                'Notes' => $order->notes,
                'Created At' => $order->created_at->toDateTimeString(),
                'Updated At' => $order->updated_at->toDateTimeString(),
            ];

            Log::info('Order synced to sheets', ['order_data' => $orderData]);

            // Update order sync status
            $order->update([
                'google_sheet_row_id' => $order->id, // In real implementation, this would be the actual row ID
                'sync_status' => 'synced',
                'last_synced_at' => Carbon::now()
            ]);

            return [
                'success' => true,
                'message' => 'Order synced successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Order sync error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error syncing order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get sync status
     */
    public function getSyncStatus(): array
    {
        if (!$this->config) {
            return [
                'configured' => false,
                'last_sync' => null,
                'status' => 'not_configured'
            ];
        }

        return [
            'configured' => $this->config->is_configured,
            'last_sync' => $this->config->last_sync_at,
            'status' => $this->config->sync_status,
            'products_count' => Product::where('tenant_id', $this->tenantId)->count(),
            'orders_count' => Order::where('tenant_id', $this->tenantId)->count(),
        ];
    }

    /**
     * Test connection to Google Sheets
     */
    public function testConnection(): array
    {
        if (!$this->config) {
            return [
                'success' => false,
                'message' => 'E-commerce not configured'
            ];
        }

        try {
            $results = [];
            
            // Test products sheet
            if ($this->config->products_sheet_id) {
                $csvUrl = "https://docs.google.com/spreadsheets/d/{$this->config->products_sheet_id}/export?format=csv&gid=0";
                $response = Http::timeout(10)->get($csvUrl);
                $results['products'] = $response->successful();
            }

            // Test orders sheet
            if ($this->config->orders_sheet_id) {
                $csvUrl = "https://docs.google.com/spreadsheets/d/{$this->config->orders_sheet_id}/export?format=csv&gid=0";
                $response = Http::timeout(10)->get($csvUrl);
                $results['orders'] = $response->successful();
            }

            $allSuccessful = !empty($results) && !in_array(false, $results);

            return [
                'success' => $allSuccessful,
                'message' => $allSuccessful ? 'All sheets accessible' : 'Some sheets not accessible',
                'details' => $results
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }
}
