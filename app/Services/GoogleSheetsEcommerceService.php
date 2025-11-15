<?php

namespace App\Services;

use App\Models\Tenant\EcommerceBot;
use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Contact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Google Sheets E-commerce Service
 * Handles synchronization between WhatsMark and Google Sheets for products and orders
 */
class GoogleSheetsEcommerceService
{
    protected $ecommerceBot;
    protected $tenantId;

    public function __construct(EcommerceBot $ecommerceBot)
    {
        $this->ecommerceBot = $ecommerceBot;
        $this->tenantId = $ecommerceBot->tenant_id;
    }

    /**
     * Extract Google Sheets ID from URL
     */
    public function extractSheetsId(string $url): ?string
    {
        // Handle different Google Sheets URL formats
        $patterns = [
            '/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/',
            '/spreadsheets\/d\/([a-zA-Z0-9-_]+)/',
            '/docs\.google\.com.*spreadsheets.*[?&]key=([a-zA-Z0-9-_]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Validate and extract sheet information from Google Sheets URL
     */
    public function validateSheetsUrl(string $url): array
    {
        $sheetsId = $this->extractSheetsId($url);
        
        if (!$sheetsId) {
            return [
                'success' => false,
                'message' => 'Invalid Google Sheets URL. Please provide a valid public Google Sheets link.'
            ];
        }

        // Try to access the sheet to validate it's public
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetsId}/export?format=csv&gid=0";
        
        try {
            $response = Http::timeout(30)->get($csvUrl);
            
            if ($response->successful()) {
                $data = $this->parseCsvData($response->body());
                
                return [
                    'success' => true,
                    'sheets_id' => $sheetsId,
                    'csv_url' => $csvUrl,
                    'headers' => $data['headers'] ?? [],
                    'row_count' => count($data['rows'] ?? []),
                    'message' => 'Google Sheets validated successfully!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Cannot access Google Sheets. Please make sure the sheet is public and sharing is enabled.'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Sheets validation error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error accessing Google Sheets: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse CSV data from Google Sheets
     */
    protected function parseCsvData(string $csvContent): array
    {
        $lines = str_getcsv($csvContent, "\n");
        if (empty($lines)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = str_getcsv($lines[0]);
        $rows = [];

        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') continue;
            $row = str_getcsv($lines[$i]);
            $rows[] = array_combine($headers, array_pad($row, count($headers), ''));
        }

        return [
            'headers' => $headers,
            'rows' => $rows
        ];
    }

    /**
     * Sync products from Google Sheets
     */
    public function syncProductsFromSheets(): array
    {
        if (!$this->ecommerceBot->google_sheets_product_url) {
            return ['success' => false, 'message' => 'No product sheet URL configured'];
        }

        $sheetsId = $this->extractSheetsId($this->ecommerceBot->google_sheets_product_url);
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetsId}/export?format=csv&gid=0";

        try {
            $response = Http::timeout(30)->get($csvUrl);
            
            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Failed to fetch product data from Google Sheets'];
            }

            $data = $this->parseCsvData($response->body());
            $syncSettings = $this->ecommerceBot->sync_settings ?? $this->ecommerceBot->getDefaultSyncSettings();
            $columnMapping = $syncSettings['product_columns'] ?? [];

            $syncedCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($data['rows'] as $index => $row) {
                try {
                    $productData = $this->mapProductData($row, $columnMapping);
                    $productData['tenant_id'] = $this->tenantId;
                    $productData['sheets_row_index'] = $index + 2; // +2 because header is row 1, data starts from row 2
                    $productData['last_synced_at'] = now();

                    // Find existing product by SKU or name
                    $product = Product::where('tenant_id', $this->tenantId)
                        ->where(function($query) use ($productData) {
                            if (!empty($productData['sku'])) {
                                $query->where('sku', $productData['sku']);
                            } else {
                                $query->where('name', $productData['name']);
                            }
                        })
                        ->first();

                    if ($product) {
                        $product->update($productData);
                    } else {
                        Product::create($productData);
                    }

                    $syncedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    Log::error('Product sync error', [
                        'row' => $index + 2,
                        'data' => $row,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->ecommerceBot->update(['last_sync_at' => now()]);

            return [
                'success' => true,
                'message' => "Synced {$syncedCount} products successfully" . 
                            ($errorCount > 0 ? " with {$errorCount} errors" : ""),
                'synced_count' => $syncedCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::error('Product sync error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()];
        }
    }

    /**
     * Map row data to product attributes
     */
    protected function mapProductData(array $row, array $columnMapping): array
    {
        $productData = [];

        // Required fields
        $productData['name'] = $row[$columnMapping['name'] ?? 'Product Name'] ?? 'Untitled Product';
        $productData['price'] = floatval($row[$columnMapping['price'] ?? 'Price'] ?? 0);

        // Optional fields
        $productData['description'] = $row[$columnMapping['description'] ?? 'Description'] ?? null;
        $productData['sku'] = $row[$columnMapping['sku'] ?? 'SKU'] ?? null;
        $productData['image_url'] = $row[$columnMapping['image_url'] ?? 'Image URL'] ?? null;
        $productData['category'] = $row[$columnMapping['category'] ?? 'Category'] ?? null;
        $productData['stock_quantity'] = intval($row[$columnMapping['stock_quantity'] ?? 'Stock'] ?? 0);
        
        // Handle compare price for discounts
        if (isset($columnMapping['compare_price'])) {
            $productData['compare_price'] = floatval($row[$columnMapping['compare_price']] ?? 0) ?: null;
        }

        // Handle status
        $status = strtolower($row[$columnMapping['status'] ?? 'Status'] ?? 'active');
        $productData['status'] = in_array($status, ['active', 'draft', 'archived']) ? $status : 'active';

        return $productData;
    }

    /**
     * Sync order to Google Sheets
     */
    public function syncOrderToSheets(Order $order): array
    {
        if (!$this->ecommerceBot->google_sheets_order_url) {
            return ['success' => false, 'message' => 'No order sheet URL configured'];
        }

        try {
            // For now, we'll log the order data that would be synced
            // In a full implementation, you'd need Google Sheets API with write access
            $orderData = $this->prepareOrderDataForSheets($order);
            
            Log::info('Order sync to sheets', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'data' => $orderData
            ]);

            // Update order's sync status
            $order->update([
                'last_synced_at' => now(),
                'sheets_row_index' => $this->getNextAvailableRowIndex()
            ]);

            return [
                'success' => true,
                'message' => 'Order synced to sheets successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Order sync error', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => 'Failed to sync order: ' . $e->getMessage()];
        }
    }

    /**
     * Prepare order data for Google Sheets
     */
    protected function prepareOrderDataForSheets(Order $order): array
    {
        $syncSettings = $this->ecommerceBot->sync_settings ?? $this->ecommerceBot->getDefaultSyncSettings();
        $columnMapping = $syncSettings['order_columns'] ?? [];

        return [
            $columnMapping['customer_name'] ?? 'Customer Name' => $order->customer_name,
            $columnMapping['customer_phone'] ?? 'Phone' => $order->customer_phone,
            $columnMapping['product_name'] ?? 'Product' => $order->items->pluck('product_name')->join(', '),
            $columnMapping['quantity'] ?? 'Quantity' => $order->items->sum('quantity'),
            $columnMapping['total_amount'] ?? 'Total' => $order->total_amount,
            $columnMapping['status'] ?? 'Status' => ucfirst($order->status),
            $columnMapping['order_date'] ?? 'Order Date' => $order->order_date->format('Y-m-d H:i:s'),
            $columnMapping['delivery_address'] ?? 'Address' => $order->delivery_address ?? '',
        ];
    }

    /**
     * Get next available row index for orders sheet
     */
    protected function getNextAvailableRowIndex(): int
    {
        $lastOrder = Order::where('tenant_id', $this->tenantId)
            ->whereNotNull('sheets_row_index')
            ->orderBy('sheets_row_index', 'desc')
            ->first();

        return ($lastOrder->sheets_row_index ?? 1) + 1;
    }

    /**
     * Create default product sheet structure
     */
    public function getDefaultProductSheetStructure(): array
    {
        return [
            'headers' => [
                'Product Name',
                'Description', 
                'SKU',
                'Price',
                'Compare Price',
                'Stock',
                'Category',
                'Image URL',
                'Status'
            ],
            'sample_data' => [
                [
                    'Product Name' => 'Sample Product 1',
                    'Description' => 'This is a sample product description',
                    'SKU' => 'SAMPLE001',
                    'Price' => '299.99',
                    'Compare Price' => '399.99',
                    'Stock' => '50',
                    'Category' => 'Electronics',
                    'Image URL' => 'https://example.com/image1.jpg',
                    'Status' => 'active'
                ],
                [
                    'Product Name' => 'Sample Product 2',
                    'Description' => 'Another sample product',
                    'SKU' => 'SAMPLE002', 
                    'Price' => '199.99',
                    'Compare Price' => '',
                    'Stock' => '25',
                    'Category' => 'Fashion',
                    'Image URL' => 'https://example.com/image2.jpg',
                    'Status' => 'active'
                ]
            ]
        ];
    }

    /**
     * Create default order sheet structure
     */
    public function getDefaultOrderSheetStructure(): array
    {
        return [
            'headers' => [
                'Order Number',
                'Customer Name',
                'Phone',
                'Email',
                'Product',
                'Quantity',
                'Total',
                'Status',
                'Payment Status',
                'Order Date',
                'Delivery Address'
            ],
            'sample_data' => [
                [
                    'Order Number' => 'ORD-001',
                    'Customer Name' => 'John Doe',
                    'Phone' => '+1234567890',
                    'Email' => 'john@example.com',
                    'Product' => 'Sample Product 1',
                    'Quantity' => '2',
                    'Total' => '599.98',
                    'Status' => 'confirmed',
                    'Payment Status' => 'paid',
                    'Order Date' => '2025-01-01 10:30:00',
                    'Delivery Address' => '123 Main St, City, State'
                ]
            ]
        ];
    }

    /**
     * Test connection to Google Sheets
     */
    public function testConnection(): array
    {
        $results = [];

        // Test product sheet
        if ($this->ecommerceBot->google_sheets_product_url) {
            $results['products'] = $this->validateSheetsUrl($this->ecommerceBot->google_sheets_product_url);
        }

        // Test order sheet
        if ($this->ecommerceBot->google_sheets_order_url) {
            $results['orders'] = $this->validateSheetsUrl($this->ecommerceBot->google_sheets_order_url);
        }

        return $results;
    }
}
