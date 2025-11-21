<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\Product;
use App\Models\Tenant\EcommerceConfiguration;
use App\Services\GoogleSheetsService;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = 'all';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    
    public $showProductModal = false;
    public $editingProduct = null;
    public $productForm = [
        'name' => '',
        'description' => '',
        'price' => '',
        'sale_price' => '',
        'stock_quantity' => '',
        'category' => '',
        'subcategory' => '',
        'sku' => '',
        'status' => 'active',
        'featured' => false,
        'low_stock_threshold' => 5,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'page' => ['except' => 1],
    ];

    protected $rules = [
        'productForm.name' => 'required|string|max:255',
        'productForm.description' => 'nullable|string',
        'productForm.price' => 'required|numeric|min:0',
        'productForm.sale_price' => 'nullable|numeric|min:0',
        'productForm.stock_quantity' => 'required|integer|min:0',
        'productForm.category' => 'nullable|string|max:100',
        'productForm.subcategory' => 'nullable|string|max:100',
        'productForm.sku' => 'nullable|string|max:100',
        'productForm.status' => 'required|in:active,inactive,draft',
        'productForm.featured' => 'boolean',
        'productForm.low_stock_threshold' => 'required|integer|min:0',
    ];

    public function mount()
    {
        if (!checkPermission('tenant.ecommerce.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
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

    public function createProduct()
    {
        $this->resetProductForm();
        $this->editingProduct = null;
        $this->showProductModal = true;
    }

    public function editProduct($productId)
    {
        $product = Product::where('tenant_id', tenant_id())->findOrFail($productId);
        $this->editingProduct = $product;
        $this->productForm = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'stock_quantity' => $product->stock_quantity,
            'category' => $product->category,
            'subcategory' => $product->subcategory,
            'sku' => $product->sku,
            'status' => $product->status,
            'featured' => $product->featured,
            'low_stock_threshold' => $product->low_stock_threshold,
        ];
        $this->showProductModal = true;
    }

    public function saveProduct()
    {
        $this->validate();

        try {
            $productData = $this->productForm;
            $productData['tenant_id'] = tenant_id();
            
            if (!$productData['sku']) {
                $productData['sku'] = 'PRD-' . strtoupper(substr(md5(uniqid()), 0, 8));
            }

            if ($this->editingProduct) {
                $this->editingProduct->update($productData);
                $this->notify(['type' => 'success', 'message' => 'Product updated successfully']);
            } else {
                Product::create($productData);
                $this->notify(['type' => 'success', 'message' => 'Product created successfully']);
            }

            $this->showProductModal = false;
            $this->resetProductForm();
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Error saving product: ' . $e->getMessage()]);
        }
    }

    public function deleteProduct($productId)
    {
        try {
            $product = Product::where('tenant_id', tenant_id())->findOrFail($productId);
            $product->delete();
            $this->notify(['type' => 'success', 'message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Error deleting product: ' . $e->getMessage()]);
        }
    }

    public function toggleFeatured($productId)
    {
        try {
            $product = Product::where('tenant_id', tenant_id())->findOrFail($productId);
            $product->update(['featured' => !$product->featured]);
            $this->notify(['type' => 'success', 'message' => 'Product updated successfully']);
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Error updating product: ' . $e->getMessage()]);
        }
    }

    public function adjustStock($productId, $adjustment)
    {
        try {
            $product = Product::where('tenant_id', tenant_id())->findOrFail($productId);
            $newStock = max(0, $product->stock_quantity + $adjustment);
            $product->update(['stock_quantity' => $newStock]);
            $this->notify(['type' => 'success', 'message' => 'Stock updated successfully']);
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Error updating stock: ' . $e->getMessage()]);
        }
    }

    public function syncProducts()
    {
        try {
            $sheetsService = new GoogleSheetsService();
            $result = $sheetsService->syncProductsFromSheets();
            
            if ($result['success']) {
                $this->notify(['type' => 'success', 'message' => $result['message']]);
            } else {
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    public function resetProductForm()
    {
        $this->productForm = [
            'name' => '',
            'description' => '',
            'price' => '',
            'sale_price' => '',
            'stock_quantity' => '',
            'category' => '',
            'subcategory' => '',
            'sku' => '',
            'status' => 'active',
            'featured' => false,
            'low_stock_threshold' => 5,
        ];
    }

    public function closeModal()
    {
        $this->showProductModal = false;
        $this->resetProductForm();
        $this->editingProduct = null;
    }

    public function render()
    {
        $tenantId = tenant_id();
        $tableService = new \App\Services\DynamicTenantTableService();
        $tableName = $tableService->getTenantTableName($tenantId);
        
        // Check if table exists
        if (!\Schema::hasTable($tableName)) {
            return view('livewire.tenant.ecommerce.product-manager', [
                'products' => collect(),
                'categories' => collect(),
                'stats' => [
                    'total' => 0,
                    'active' => 0,
                    'low_stock' => 0,
                    'featured' => 0,
                ],
                'tableExists' => false,
            ]);
        }

        $query = \DB::table($tableName);

        // Apply search filter
        if ($this->search) {
            $query->where(function($q) {
                // Search in common fields
                if (\Schema::hasColumn($q->from, 'title')) {
                    $q->orWhere('title', 'like', '%' . $this->search . '%');
                }
                if (\Schema::hasColumn($q->from, 'product_id')) {
                    $q->orWhere('product_id', 'like', '%' . $this->search . '%');
                }
                if (\Schema::hasColumn($q->from, 'product_type')) {
                    $q->orWhere('product_type', 'like', '%' . $this->search . '%');
                }
            });
        }

        // Apply status filter
        if ($this->statusFilter !== 'all' && \Schema::hasColumn($tableName, 'status')) {
            $statusMap = [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
            ];
            if (isset($statusMap[$this->statusFilter])) {
                $query->where('status', $statusMap[$this->statusFilter]);
            }
        }

        // Apply sorting
        $sortColumn = $this->sortBy;
        if ($sortColumn === 'name' && \Schema::hasColumn($tableName, 'title')) {
            $sortColumn = 'title';
        }
        if (\Schema::hasColumn($tableName, $sortColumn)) {
            $query->orderBy($sortColumn, $this->sortDirection);
        }

        $products = $query->paginate(20);

        // Get categories if available
        $categories = collect();
        if (\Schema::hasColumn($tableName, 'product_type')) {
            $categories = \DB::table($tableName)
                ->whereNotNull('product_type')
                ->where('product_type', '!=', '')
                ->distinct()
                ->pluck('product_type');
        }

        // Get stats
        $total = \DB::table($tableName)->count();
        $active = 0;
        if (\Schema::hasColumn($tableName, 'status')) {
            $active = \DB::table($tableName)->where('status', 'Active')->count();
        }

        $stats = [
            'total' => $total,
            'active' => $active,
            'low_stock' => 0,
            'featured' => 0,
        ];

        return view('livewire.tenant.ecommerce.product-manager', [
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
            'tableExists' => true,
        ]);
    }
}
