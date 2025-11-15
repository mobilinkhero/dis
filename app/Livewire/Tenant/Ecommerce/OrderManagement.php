<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\Order;
use App\Models\Tenant\EcommerceBot;
use App\Services\GoogleSheetsEcommerceService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class OrderManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $dateRange = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;

    // Order details
    public $selectedOrder = null;
    public $showOrderDetails = false;
    
    // Bulk actions
    public $selectedOrders = [];
    public $selectAll = false;
    public $bulkAction = '';

    // Order editing
    public $editingOrder = null;
    public $editForm = [];

    public function mount()
    {
        if (!checkPermission('tenant.orders.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedOrders = $this->getFilteredOrders()->pluck('id')->toArray();
        } else {
            $this->selectedOrders = [];
        }
    }

    public function viewOrderDetails($orderId)
    {
        $this->selectedOrder = Order::with(['items.product', 'contact'])
            ->findOrFail($orderId);
        $this->showOrderDetails = true;
    }

    public function closeOrderDetails()
    {
        $this->selectedOrder = null;
        $this->showOrderDetails = false;
    }

    public function updateOrderStatus($orderId, $status)
    {
        try {
            $order = Order::findOrFail($orderId);
            $order->updateStatus($status);
            
            // Sync to Google Sheets if configured
            $ecommerceBot = EcommerceBot::where('tenant_id', tenant_id())->first();
            if ($ecommerceBot && $ecommerceBot->google_sheets_order_url) {
                $sheetsService = new GoogleSheetsEcommerceService($ecommerceBot);
                $sheetsService->syncOrderToSheets($order);
            }

            $this->notify(['type' => 'success', 'message' => 'Order status updated successfully']);
            
            // Refresh order details if open
            if ($this->selectedOrder && $this->selectedOrder->id === $orderId) {
                $this->selectedOrder = $order->fresh();
            }
            
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Failed to update order status']);
        }
    }

    public function bulkUpdateStatus($status)
    {
        if (empty($this->selectedOrders)) {
            $this->notify(['type' => 'danger', 'message' => 'No orders selected']);
            return;
        }

        try {
            DB::transaction(function () use ($status) {
                Order::whereIn('id', $this->selectedOrders)
                    ->each(function ($order) use ($status) {
                        $order->updateStatus($status);
                    });
            });
            
            $count = count($this->selectedOrders);
            $this->notify(['type' => 'success', 'message' => "Updated {$count} orders to {$status}"]);
            $this->selectedOrders = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Bulk update failed']);
        }
    }

    public function sendTrackingUpdate($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            if ($order->contact && $order->contact->phone) {
                $message = $order->getTrackingMessage();
                
                // Send WhatsApp message (you'll need to implement this based on your WhatsApp service)
                // $whatsappService = new WhatsAppService();
                // $whatsappService->sendMessage($order->contact->phone, $message);
                
                $this->notify(['type' => 'success', 'message' => 'Tracking update sent to customer']);
            } else {
                $this->notify(['type' => 'warning', 'message' => 'No phone number found for customer']);
            }
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Failed to send tracking update']);
        }
    }

    public function exportOrders()
    {
        // Implement CSV export functionality
        $orders = $this->getFilteredOrders()->get();
        
        $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Order Number',
                'Customer Name',
                'Phone',
                'Email',
                'Total Amount',
                'Status',
                'Payment Status',
                'Order Date',
                'Items'
            ]);

            // CSV Data
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->customer_email,
                    $order->total_amount,
                    $order->status,
                    $order->payment_status,
                    $order->order_date->format('Y-m-d H:i:s'),
                    $order->items->pluck('product_name')->implode(', ')
                ]);
            }
            
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedDateRange()
    {
        $this->resetPage();
    }

    private function getFilteredOrders()
    {
        $query = Order::with(['items', 'contact'])
            ->where('tenant_id', tenant_id());

        // Search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $this->search . '%')
                  ->orWhere('customer_email', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        // Date range filter
        if ($this->dateRange) {
            switch ($this->dateRange) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        return $query->orderBy($this->sortBy, $this->sortDirection);
    }

    public function render()
    {
        $orders = $this->getFilteredOrders()->paginate($this->perPage);
        
        $stats = [
            'total' => Order::where('tenant_id', tenant_id())->count(),
            'pending' => Order::where('tenant_id', tenant_id())->where('status', 'pending')->count(),
            'confirmed' => Order::where('tenant_id', tenant_id())->where('status', 'confirmed')->count(),
            'delivered' => Order::where('tenant_id', tenant_id())->where('status', 'delivered')->count(),
            'total_revenue' => Order::where('tenant_id', tenant_id())->sum('total_amount'),
        ];

        return view('livewire.tenant.ecommerce.order-management', [
            'orders' => $orders,
            'stats' => $stats,
            'statuses' => Order::getStatuses(),
            'paymentStatuses' => Order::getPaymentStatuses(),
        ]);
    }
}
