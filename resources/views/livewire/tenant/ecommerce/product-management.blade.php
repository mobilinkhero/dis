<div class="space-y-6">
    {{-- Header & Stats --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📦 Product Management</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage your products and sync with Google Sheets</p>
            </div>

            <div class="flex space-x-3">
                <button 
                    wire:click="syncFromSheets" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    wire:loading.attr="disabled"
                    wire:target="syncFromSheets"
                >
                    <div wire:loading.remove wire:target="syncFromSheets">
                        🔄 Sync from Sheets
                    </div>
                    <div wire:loading wire:target="syncFromSheets">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        </svg>
                        Syncing...
                    </div>
                </button>

                <a href="{{ route('tenant.ecommerce.setup') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    ⚙️ Settings
                </a>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total'] }}</div>
                <div class="text-sm text-blue-800 dark:text-blue-300">Total Products</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['active'] }}</div>
                <div class="text-sm text-green-800 dark:text-green-300">Active Products</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['low_stock'] }}</div>
                <div class="text-sm text-yellow-800 dark:text-yellow-300">Low Stock</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['out_of_stock'] }}</div>
                <div class="text-sm text-red-800 dark:text-red-300">Out of Stock</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <input 
                    type="text" 
                    wire:model.debounce.300ms="search"
                    placeholder="🔍 Search products..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                >
            </div>
            
            <div>
                <select wire:model="statusFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model="categoryFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select wire:model="perPage" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </select>
            </div>
        </div>

        @if(!empty($selectedProducts))
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ count($selectedProducts) }} products selected
                    </span>
                    <div class="flex space-x-2">
                        <button wire:click="bulkUpdateStatus('active')" class="text-xs bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded">
                            Activate
                        </button>
                        <button wire:click="bulkUpdateStatus('archived')" class="text-xs bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded">
                            Archive
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Products Table --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" wire:model="selectAll" class="rounded border-gray-300 text-blue-600">
                    </th>
                    <th wire:click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        Product Name
                        @if($sortBy === 'name')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                    <th wire:click="sortBy('price')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        Price
                        @if($sortBy === 'price')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th wire:click="sortBy('stock_quantity')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        Stock
                        @if($sortBy === 'stock_quantity')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                @forelse($products as $product)
                    <tr class="{{ in_array($product->id, $selectedProducts) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        <td class="px-6 py-4">
                            <input type="checkbox" wire:model="selectedProducts" value="{{ $product->id }}" class="rounded border-gray-300 text-blue-600">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-10 w-10 rounded-lg object-cover mr-3">
                                @else
                                    <div class="h-10 w-10 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center mr-3">
                                        <span class="text-gray-400 text-xs">📦</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                    @if($product->sku)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ $product->sku }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $product->category ?? 'Uncategorized' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">₹{{ $product->formatted_price }}</div>
                            @if($product->compare_price && $product->discount_percentage)
                                <div class="text-xs text-green-600">{{ $product->discount_percentage }}% OFF</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium {{ $product->stock_quantity <= 5 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                {{ $product->stock_quantity }}
                            </div>
                            @if($product->isLowStock())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Low Stock
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'archived' => 'bg-red-100 text-red-800',
                                    'out_of_stock' => 'bg-yellow-100 text-yellow-800'
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$product->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <button wire:click="editProduct({{ $product->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button wire:click="deleteProduct({{ $product->id }})" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="space-y-2">
                                <div class="text-4xl">📦</div>
                                <div>No products found</div>
                                <div class="text-sm">Try syncing from Google Sheets or adjust your filters</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($products->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-600">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    {{-- Edit Product Modal --}}
    @if($editingProduct)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Product</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product Name</label>
                                <input type="text" wire:model="editForm.name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('editForm.name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price</label>
                                <input type="number" step="0.01" wire:model="editForm.price" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('editForm.price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Stock Quantity</label>
                                <input type="number" wire:model="editForm.stock_quantity" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('editForm.stock_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select wire:model="editForm.status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('editForm.status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3">
                        <button wire:click="cancelEdit" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Cancel
                        </button>
                        <button wire:click="updateProduct" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Update Product
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
