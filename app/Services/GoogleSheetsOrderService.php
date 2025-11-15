<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GoogleSheetsOrderService
{
    protected $spreadsheetId;
    protected $apiKey;
    protected $range;

    public function __construct()
    {
        $this->spreadsheetId = config('services.google_sheets.spreadsheet_id');
        $this->apiKey = config('services.google_sheets.api_key');
        $this->range = config('services.google_sheets.orders_range', 'Orders!A:Z');
    }

    /**
     * Add a new order to Google Sheets
     */
    public function addOrder(array $orderData): bool
    {
        try {
            $values = $this->formatOrderForSheet($orderData);
            
            $response = Http::post("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$this->range}:append", [
                'key' => $this->apiKey,
                'valueInputOption' => 'RAW',
                'values' => [$values]
            ]);

            if ($response->successful()) {
                Log::info('Order added to Google Sheets', ['order_id' => $orderData['order_number']]);
                return true;
            } else {
                Log::error('Failed to add order to Google Sheets', [
                    'response' => $response->json(),
                    'order_id' => $orderData['order_number']
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Google Sheets API Error', [
                'error' => $e->getMessage(),
                'order_id' => $orderData['order_number'] ?? 'unknown'
            ]);
            return false;
        }
    }

    /**
     * Update order status in Google Sheets
     */
    public function updateOrderStatus(string $orderNumber, string $status): bool
    {
        try {
            // Find the order row first
            $rowIndex = $this->findOrderRow($orderNumber);
            
            if ($rowIndex === null) {
                Log::warning('Order not found in Google Sheets', ['order_number' => $orderNumber]);
                return false;
            }

            // Update the status column (assuming column H is status)
            $range = "Orders!H{$rowIndex}";
            
            $response = Http::put("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$range}", [
                'key' => $this->apiKey,
                'valueInputOption' => 'RAW',
                'values' => [[$status]]
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to update order status in Google Sheets', [
                'error' => $e->getMessage(),
                'order_number' => $orderNumber
            ]);
            return false;
        }
    }

    /**
     * Get orders from Google Sheets
     */
    public function getOrders(): array
    {
        try {
            $response = Http::get("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$this->range}", [
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->parseOrdersFromSheet($data['values'] ?? []);
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Failed to get orders from Google Sheets', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get product inventory from Google Sheets
     */
    public function getProductInventory(): array
    {
        try {
            $productsRange = config('services.google_sheets.products_range', 'Products!A:Z');
            
            $response = Http::get("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$productsRange}", [
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->parseProductsFromSheet($data['values'] ?? []);
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Failed to get products from Google Sheets', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Update product inventory in Google Sheets
     */
    public function updateProductInventory(int $productId, int $newStock): bool
    {
        try {
            $rowIndex = $this->findProductRow($productId);
            
            if ($rowIndex === null) {
                return false;
            }

            // Assuming column E is stock quantity
            $range = "Products!E{$rowIndex}";
            
            $response = Http::put("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$range}", [
                'key' => $this->apiKey,
                'valueInputOption' => 'RAW',
                'values' => [[$newStock]]
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to update product inventory', [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            return false;
        }
    }

    /**
     * Add customer to Google Sheets
     */
    public function addCustomer(array $customerData): bool
    {
        try {
            $customersRange = config('services.google_sheets.customers_range', 'Customers!A:Z');
            $values = $this->formatCustomerForSheet($customerData);
            
            $response = Http::post("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$customersRange}:append", [
                'key' => $this->apiKey,
                'valueInputOption' => 'RAW',
                'values' => [$values]
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to add customer to Google Sheets', [
                'error' => $e->getMessage(),
                'customer' => $customerData['name'] ?? 'unknown'
            ]);
            return false;
        }
    }

    /**
     * Generate sales report from Google Sheets data
     */
    public function generateSalesReport(string $startDate, string $endDate): array
    {
        try {
            $orders = $this->getOrders();
            
            $filteredOrders = array_filter($orders, function ($order) use ($startDate, $endDate) {
                $orderDate = strtotime($order['date']);
                $start = strtotime($startDate);
                $end = strtotime($endDate);
                
                return $orderDate >= $start && $orderDate <= $end;
            });

            return [
                'total_orders' => count($filteredOrders),
                'total_revenue' => array_sum(array_column($filteredOrders, 'total')),
                'average_order_value' => count($filteredOrders) > 0 ? array_sum(array_column($filteredOrders, 'total')) / count($filteredOrders) : 0,
                'orders' => $filteredOrders
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate sales report', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Format order data for Google Sheets
     */
    protected function formatOrderForSheet(array $orderData): array
    {
        return [
            $orderData['order_number'],
            $orderData['customer_name'],
            $orderData['customer_phone'],
            $orderData['customer_email'] ?? '',
            json_encode($orderData['items']),
            $orderData['subtotal'],
            $orderData['tax'],
            $orderData['total'],
            $orderData['status'],
            $orderData['payment_method'],
            $orderData['payment_status'] ?? 'pending',
            now()->format('Y-m-d H:i:s'),
            $orderData['notes'] ?? ''
        ];
    }

    /**
     * Format customer data for Google Sheets
     */
    protected function formatCustomerForSheet(array $customerData): array
    {
        return [
            $customerData['name'],
            $customerData['phone'],
            $customerData['email'] ?? '',
            $customerData['address'] ?? '',
            $customerData['total_orders'] ?? 0,
            $customerData['total_spent'] ?? 0,
            $customerData['last_order_date'] ?? '',
            now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Parse orders from Google Sheets data
     */
    protected function parseOrdersFromSheet(array $rows): array
    {
        $orders = [];
        $headers = array_shift($rows); // Remove header row
        
        foreach ($rows as $row) {
            if (count($row) < 8) continue; // Skip incomplete rows
            
            $orders[] = [
                'order_number' => $row[0] ?? '',
                'customer_name' => $row[1] ?? '',
                'customer_phone' => $row[2] ?? '',
                'customer_email' => $row[3] ?? '',
                'items' => json_decode($row[4] ?? '[]', true),
                'subtotal' => floatval($row[5] ?? 0),
                'tax' => floatval($row[6] ?? 0),
                'total' => floatval($row[7] ?? 0),
                'status' => $row[8] ?? 'pending',
                'payment_method' => $row[9] ?? '',
                'payment_status' => $row[10] ?? 'pending',
                'date' => $row[11] ?? '',
                'notes' => $row[12] ?? ''
            ];
        }
        
        return $orders;
    }

    /**
     * Parse products from Google Sheets data
     */
    protected function parseProductsFromSheet(array $rows): array
    {
        $products = [];
        $headers = array_shift($rows); // Remove header row
        
        foreach ($rows as $row) {
            if (count($row) < 5) continue; // Skip incomplete rows
            
            $products[] = [
                'id' => intval($row[0] ?? 0),
                'name' => $row[1] ?? '',
                'description' => $row[2] ?? '',
                'price' => floatval($row[3] ?? 0),
                'stock' => intval($row[4] ?? 0),
                'category' => $row[5] ?? '',
                'image_url' => $row[6] ?? '',
                'status' => $row[7] ?? 'active'
            ];
        }
        
        return $products;
    }

    /**
     * Find order row index in Google Sheets
     */
    protected function findOrderRow(string $orderNumber): ?int
    {
        try {
            $response = Http::get("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$this->range}", [
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rows = $data['values'] ?? [];
                
                foreach ($rows as $index => $row) {
                    if (isset($row[0]) && $row[0] === $orderNumber) {
                        return $index + 1; // Sheet rows are 1-indexed
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to find order row', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Find product row index in Google Sheets
     */
    protected function findProductRow(int $productId): ?int
    {
        try {
            $productsRange = config('services.google_sheets.products_range', 'Products!A:Z');
            
            $response = Http::get("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$productsRange}", [
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rows = $data['values'] ?? [];
                
                foreach ($rows as $index => $row) {
                    if (isset($row[0]) && intval($row[0]) === $productId) {
                        return $index + 1; // Sheet rows are 1-indexed
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to find product row', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create initial sheet structure
     */
    public function createInitialSheetStructure(): bool
    {
        try {
            // Create Orders sheet header
            $ordersHeader = [
                'Order Number', 'Customer Name', 'Customer Phone', 'Customer Email',
                'Items', 'Subtotal', 'Tax', 'Total', 'Status', 'Payment Method',
                'Payment Status', 'Date', 'Notes'
            ];

            // Create Products sheet header
            $productsHeader = [
                'ID', 'Name', 'Description', 'Price', 'Stock', 'Category', 'Image URL', 'Status'
            ];

            // Create Customers sheet header
            $customersHeader = [
                'Name', 'Phone', 'Email', 'Address', 'Total Orders', 'Total Spent', 'Last Order Date', 'Registration Date'
            ];

            // Add headers to respective sheets
            $this->addSheetHeader('Orders!A1:M1', $ordersHeader);
            $this->addSheetHeader('Products!A1:H1', $productsHeader);
            $this->addSheetHeader('Customers!A1:H1', $customersHeader);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to create initial sheet structure', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Add header row to sheet
     */
    protected function addSheetHeader(string $range, array $headers): bool
    {
        try {
            $response = Http::put("https://sheets.googleapis.com/v4/spreadsheets/{$this->spreadsheetId}/values/{$range}", [
                'key' => $this->apiKey,
                'valueInputOption' => 'RAW',
                'values' => [$headers]
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Failed to add sheet header', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
