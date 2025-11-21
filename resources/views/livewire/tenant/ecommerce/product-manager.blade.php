<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Product Management') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Manage your product catalog and inventory
                </p>
            </div>
            
            <div class="flex gap-3">
                <button wire:click="syncProducts" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    🔄 Sync Products
                </button>
                <button wire:click="createProduct" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    ➕ Add Product
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Products</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-800/50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Products</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-800/50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Low Stock</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['low_stock'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-800/50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Featured</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['featured'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-800/50 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input type="text" 
                       wire:model.debounce.300ms="search"
                       placeholder="Search products..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category</label>
                <select wire:model="categoryFilter" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model="statusFilter" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="draft">Draft</option>
                    <option value="low_stock">Low Stock</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                <select wire:model="sortBy" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="name">Name</option>
                    <option value="created_at">Date Created</option>
                    <option value="price">Price</option>
                    <option value="stock_quantity">Stock</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($products as $product)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <!-- Product Image -->
                <div class="relative h-64 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 overflow-hidden">
                    @if(isset($product->image_url) && !empty($product->image_url))
                        <img src="{{ $product->image_url }}" 
                             alt="{{ $product->title ?? 'Product' }}"
                             class="w-full h-full object-cover"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 200 200%22%3E%3Crect fill=%22%23f3f4f6%22 width=%22200%22 height=%22200%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%239ca3af%22 font-size=%2220%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Status Badge -->
                    @if(isset($product->status))
                        <div class="absolute top-3 right-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold shadow-lg
                                {{ $product->status === 'Active' ? 'bg-green-500 text-white' : '' }}
                                {{ $product->status === 'Inactive' ? 'bg-red-500 text-white' : '' }}
                                {{ $product->status === 'Draft' ? 'bg-gray-500 text-white' : '' }}">
                                {{ $product->status }}
                            </span>
                        </div>
                    @endif
                    
                    <!-- Creative Grade -->
                    @if(isset($product->creative_grade) && !empty($product->creative_grade))
                        <div class="absolute top-3 left-3">
                            <span class="px-2 py-1 bg-purple-500 text-white rounded-full text-xs font-bold shadow-lg">
                                Grade {{ $product->creative_grade }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Product Details -->
                <div class="p-5">
                    <!-- Product ID -->
                    @if(isset($product->product_id))
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                            ID: {{ $product->product_id }}
                        </div>
                    @endif
                    
                    <!-- Product Title -->
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 min-h-[3.5rem]">
                        {{ $product->title ?? 'Untitled Product' }}
                    </h3>
                    
                    <!-- Product Type -->
                    @if(isset($product->product_type) && !empty($product->product_type))
                        <div class="mb-3">
                            <span class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded-md text-xs font-medium">
                                {{ $product->product_type }}
                            </span>
                        </div>
                    @endif
                    
                    <!-- Colors & Sizes -->
                    <div class="flex items-center gap-3 mb-3 text-sm">
                        @if(isset($product->colors) && !empty($product->colors))
                            <div class="flex items-center gap-1">
                                <span class="text-gray-600 dark:text-gray-400">🎨</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $product->colors }}</span>
                            </div>
                        @endif
                        
                        @if(isset($product->sizes) && !empty($product->sizes))
                            <div class="flex items-center gap-1">
                                <span class="text-gray-600 dark:text-gray-400">📏</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $product->sizes }}</span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Price -->
                    <div class="mb-3">
                        @if(isset($product->selling_price) && !empty($product->selling_price))
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($product->selling_price, 2) }}
                                </span>
                                
                                @if(isset($product->purchase_price_) && !empty($product->purchase_price_) && $product->purchase_price_ > 0)
                                    <span class="text-sm text-gray-500 line-through">
                                        ${{ number_format($product->purchase_price_, 2) }}
                                    </span>
                                @endif
                                
                                @if(isset($product->price_cut_shown) && $product->price_cut_shown === 'TRUE')
                                    <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200 rounded text-xs font-bold">
                                        SALE
                                    </span>
                                @endif
                            </div>
                        @endif
                        
                        @if(isset($product->advance_amount) && $product->advance_amount > 0)
                            <div class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                                💰 Advance: ${{ number_format($product->advance_amount, 2) }}
                            </div>
                        @endif
                    </div>
                    
                    <!-- Stock Info -->
                    <div class="mb-3">
                        @if(isset($product->quantity_type))
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium 
                                    {{ $product->quantity_type === 'In Stock' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $product->quantity_type }}
                                </span>
                                @if(isset($product->quantity_int))
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        ({{ $product->quantity_int }} units)
                                    </span>
                                @endif
                            </div>
                        @elseif(isset($product->quantity))
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Stock: {{ $product->quantity }} units
                            </div>
                        @endif
                    </div>
                    
                    <!-- Tags -->
                    @if(isset($product->tags) && !empty($product->tags))
                        <div class="flex flex-wrap gap-1 mb-3">
                            @php
                                $tags = is_string($product->tags) ? explode(',', $product->tags) : [];
                            @endphp
                            @foreach(array_slice($tags, 0, 3) as $tag)
                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs">
                                    #{{ trim($tag) }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    
                    <!-- Slider Group -->
                    @if(isset($product->slider_group) && !empty($product->slider_group))
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            📁 {{ $product->slider_group }}
                        </div>
                    @endif
                    
                    <!-- Actions -->
                    <div class="flex gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        @if(isset($product->video_url) && $product->video_url !== '[URL]' && !empty($product->video_url))
                            <a href="{{ $product->video_url }}" target="_blank" 
                               class="flex-1 px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-center text-sm font-medium">
                                ▶️ Video
                            </a>
                        @endif
                        
                        <button class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                            View Details
                        </button>
                    </div>
                    
                    <!-- Expiry Warning -->
                    @if(isset($product->expiry_at_urgent) && !empty($product->expiry_at_urgent))
                        <div class="mt-3 px-2 py-1 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded text-xs text-red-800 dark:text-red-200">
                            ⏰ Expires: {{ $product->expiry_at_urgent }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No products found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Sync your Google Sheets to import products</p>
                    <button wire:click="syncProducts" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                        🔄 Sync Products Now
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif

    <!-- Product Modal -->
    @if($showProductModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="saveProduct">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                {{ $editingProduct ? 'Edit Product' : 'Add New Product' }}
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name</label>
                                    <input type="text" wire:model="productForm.name" 
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    @error('productForm.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                    <textarea wire:model="productForm.description" rows="3"
                                              class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price ($)</label>
                                        <input type="number" step="0.01" wire:model="productForm.price" 
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                        @error('productForm.price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sale Price ($)</label>
                                        <input type="number" step="0.01" wire:model="productForm.sale_price" 
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock Quantity</label>
                                        <input type="number" wire:model="productForm.stock_quantity" 
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                        @error('productForm.stock_quantity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                        <input type="text" wire:model="productForm.category" 
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                        <select wire:model="productForm.status" 
                                                class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="draft">Draft</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="flex items-center mt-6">
                                            <input type="checkbox" wire:model="productForm.featured" 
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured Product</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                {{ $editingProduct ? 'Update' : 'Create' }} Product
                            </button>
                            <button type="button" wire:click="closeModal"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
