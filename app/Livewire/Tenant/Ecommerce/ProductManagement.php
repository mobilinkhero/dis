<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\Product;
use App\Models\Tenant\EcommerceBot;
use App\Services\GoogleSheetsEcommerceService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ProductManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $categoryFilter = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    public $perPage = 25;

    // Bulk actions
    public $selectedProducts = [];
    public $selectAll = false;
    public $bulkAction = '';

    // Sync status
    public $lastSyncAt;
    public $isSyncing = false;
    public $syncProgress = [];

    // Product editing
    public $editingProduct = null;
    public $editForm = [];

    public function mount()
    {
        if (!checkPermission('tenant.products.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $ecommerceBot = EcommerceBot::where('tenant_id', tenant_id())->first();
        $this->lastSyncAt = $ecommerceBot?->last_sync_at;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedProducts = $this->getFilteredProducts()->pluck('id')->toArray();
        } else {
            $this->selectedProducts = [];
        }
    }

    public function syncFromSheets()
    {
        if (!checkPermission('tenant.products.sync')) {
            $this->notify(['type' => 'danger', 'message' => 'Access denied']);
            return;
        }

        $ecommerceBot = EcommerceBot::where('tenant_id', tenant_id())->first();
        
        if (!$ecommerceBot || !$ecommerceBot->isReady()) {
            $this->notify(['type' => 'danger', 'message' => 'E-commerce bot is not configured. Please set it up first.']);
            return;
        }

        $this->isSyncing = true;
        $this->syncProgress = ['status' => 'starting', 'message' => 'Initializing sync...'];

        try {
            $sheetsService = new GoogleSheetsEcommerceService($ecommerceBot);
            $result = $sheetsService->syncProductsFromSheets();

            if ($result['success']) {
                $this->lastSyncAt = now();
                $this->syncProgress = [
                    'status' => 'success',
                    'message' => $result['message'],
                    'synced_count' => $result['synced_count'] ?? 0,
                    'error_count' => $result['error_count'] ?? 0
                ];
                $this->notify(['type' => 'success', 'message' => $result['message']]);
            } else {
                $this->syncProgress = ['status' => 'error', 'message' => $result['message']];
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->syncProgress = ['status' => 'error', 'message' => 'Sync failed: ' . $e->getMessage()];
            $this->notify(['type' => 'danger', 'message' => 'Sync failed: ' . $e->getMessage()]);
        } finally {
            $this->isSyncing = false;
        }

        $this->resetPage();
    }

    public function editProduct($productId)
    {
        $this->editingProduct = Product::findOrFail($productId);
        $this->editForm = [
            'name' => $this->editingProduct->name,
            'description' => $this->editingProduct->description,
            'price' => $this->editingProduct->price,
            'compare_price' => $this->editingProduct->compare_price,
            'stock_quantity' => $this->editingProduct->stock_quantity,
            'category' => $this->editingProduct->category,
            'status' => $this->editingProduct->status,
        ];
    }

    public function updateProduct()
    {
        $this->validate([
            'editForm.name' => 'required|string|max:255',
            'editForm.price' => 'required|numeric|min:0',
            'editForm.stock_quantity' => 'required|integer|min:0',
            'editForm.status' => 'required|in:active,draft,archived,out_of_stock',
        ]);

        try {
            $this->editingProduct->update($this->editForm);
            $this->notify(['type' => 'success', 'message' => 'Product updated successfully']);
            $this->cancelEdit();
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Failed to update product']);
        }
    }

    public function cancelEdit()
    {
        $this->editingProduct = null;
        $this->editForm = [];
    }

    public function updateStock($productId, $quantity)
    {
        try {
            $product = Product::findOrFail($productId);
            $product->updateStock($quantity, false);
            $this->notify(['type' => 'success', 'message' => 'Stock updated successfully']);
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Failed to update stock']);
        }
    }

    public function bulkUpdateStatus($status)
    {
        if (empty($this->selectedProducts)) {
            $this->notify(['type' => 'danger', 'message' => 'No products selected']);
            return;
        }

        try {
            Product::whereIn('id', $this->selectedProducts)
                ->update(['status' => $status]);
                
            $count = count($this->selectedProducts);
            $this->notify(['type' => 'success', 'message' => "Updated {$count} products to {$status}"]);
            $this->selectedProducts = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Bulk update failed']);
        }
    }

    public function deleteProduct($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            // Check if product has orders
            if ($product->orderItems()->exists()) {
                $this->notify(['type' => 'danger', 'message' => 'Cannot delete product with existing orders']);
                return;
            }

            $product->delete();
            $this->notify(['type' => 'success', 'message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Failed to delete product']);
        }
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
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

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    private function getFilteredProducts()
    {
        return Product::where('tenant_id', tenant_id())
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->categoryFilter, function($query) {
                $query->where('category', $this->categoryFilter);
            })
            ->orderBy($this->sortBy, $this->sortDirection);
    }

    public function render()
    {
        $products = $this->getFilteredProducts()->paginate($this->perPage);
        
        $categories = Product::where('tenant_id', tenant_id())
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        $stats = [
            'total' => Product::where('tenant_id', tenant_id())->count(),
            'active' => Product::where('tenant_id', tenant_id())->where('status', 'active')->count(),
            'low_stock' => Product::where('tenant_id', tenant_id())->where('stock_quantity', '<=', 5)->count(),
            'out_of_stock' => Product::where('tenant_id', tenant_id())->where('stock_quantity', 0)->count(),
        ];

        return view('livewire.tenant.ecommerce.product-management', [
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
            'statuses' => Product::getStatuses(),
        ]);
    }
}
