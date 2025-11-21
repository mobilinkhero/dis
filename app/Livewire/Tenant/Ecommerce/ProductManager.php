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
        $query = Product::where('tenant_id', tenant_id());

        // Apply search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply category filter
        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'low_stock') {
                $query->whereRaw('stock_quantity <= low_stock_threshold');
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        $products = $query->paginate(20);

        // Get categories for filter
        $categories = Product::where('tenant_id', tenant_id())
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        // Get stats
        $stats = [
            'total' => Product::where('tenant_id', tenant_id())->count(),
            'active' => Product::where('tenant_id', tenant_id())->where('status', 'active')->count(),
            'low_stock' => Product::where('tenant_id', tenant_id())->whereRaw('stock_quantity <= low_stock_threshold')->count(),
            'featured' => Product::where('tenant_id', tenant_id())->where('featured', true)->count(),
        ];

        return view('livewire.tenant.ecommerce.product-manager', [
            'products' => $products,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }
}
