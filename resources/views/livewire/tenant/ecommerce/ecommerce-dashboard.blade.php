<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🛒 E-commerce Dashboard</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    @if($isConfigured)
                        Manage your WhatsApp e-commerce automation and monitor your sales performance.
                    @else
                        Set up automated product management and order processing via WhatsApp.
                    @endif
                </p>
            </div>
        </div>
    </div>

    @if($isConfigured)
        {{-- Configured Dashboard --}}
        <div class="space-y-6">
            {{-- Configuration Status --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">✅ E-commerce Bot Active</h2>
                        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                            <div>📊 Product Sheet: Connected</div>
                            <div>📋 Order Sheet: {{ $ecommerceBot->google_sheets_order_url ? 'Connected' : 'Not Connected (Optional)' }}</div>
                            <div>🔄 Last Sync: {{ $ecommerceBot->last_sync_at ? $ecommerceBot->last_sync_at->diffForHumans() : 'Never' }}</div>
                            <div>⚙️ Auto-Sync: {{ $ecommerceBot->sync_settings['auto_sync_enabled'] ?? false ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button 
                            wire:click="syncProducts"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            wire:loading.attr="disabled"
                        >
                            <div wire:loading.remove wire:target="syncProducts">
                                🔄 Sync Now
                            </div>
                            <div wire:loading wire:target="syncProducts">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Syncing...
                            </div>
                        </button>

                        <a href="{{ tenant_route('tenant.ecommerce.setup') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            ⚙️ Settings
                        </a>

                        <button 
                            wire:click="showDisconnectDialog"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            🔌 Disconnect
                        </button>
                    </div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Products Stats --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="text-2xl">📦</div>
                        </div>
                        <div class="ml-3">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['products_total'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Products</div>
                            <div class="text-xs text-green-600">{{ $stats['products_active'] ?? 0 }} active</div>
                        </div>
                    </div>
                </div>

                {{-- Orders Stats --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="text-2xl">📋</div>
                        </div>
                        <div class="ml-3">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['orders_total'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Orders</div>
                            <div class="text-xs text-orange-600">{{ $stats['orders_pending'] ?? 0 }} pending</div>
                        </div>
                    </div>
                </div>

                {{-- Today's Orders --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="text-2xl">🚀</div>
                        </div>
                        <div class="ml-3">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['orders_today'] ?? 0 }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Today's Orders</div>
                            @if(($stats['revenue_today'] ?? 0) > 0)
                                <div class="text-xs text-green-600">${{ number_format($stats['revenue_today'], 2) }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Revenue Stats --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="text-2xl">💰</div>
                        </div>
                        <div class="ml-3">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($stats['revenue_total'] ?? 0, 0) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</div>
                            @if(($stats['products_low_stock'] ?? 0) > 0)
                                <div class="text-xs text-red-600">{{ $stats['products_low_stock'] }} low stock</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="{{ tenant_route('tenant.products.list') }}" class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                        <div class="text-2xl mr-3">📦</div>
                        <div>
                            <div class="font-medium text-blue-900 dark:text-blue-100">Manage Products</div>
                            <div class="text-sm text-blue-600 dark:text-blue-400">View & edit inventory</div>
                        </div>
                    </a>

                    <a href="{{ tenant_route('tenant.orders.list') }}" class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                        <div class="text-2xl mr-3">📋</div>
                        <div>
                            <div class="font-medium text-purple-900 dark:text-purple-100">View Orders</div>
                            <div class="text-sm text-purple-600 dark:text-purple-400">Process & track orders</div>
                        </div>
                    </a>

                    <a href="{{ tenant_route('tenant.ecommerce.analytics') }}" class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors">
                        <div class="text-2xl mr-3">📈</div>
                        <div>
                            <div class="font-medium text-green-900 dark:text-green-100">Sales Analytics</div>
                            <div class="text-sm text-green-600 dark:text-green-400">View reports & insights</div>
                        </div>
                    </a>

                    <a href="{{ tenant_route('tenant.ecommerce.upselling') }}" class="flex items-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors">
                        <div class="text-2xl mr-3">🎯</div>
                        <div>
                            <div class="font-medium text-orange-900 dark:text-orange-100">Upsell Campaigns</div>
                            <div class="text-sm text-orange-600 dark:text-orange-400">Boost sales with AI</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

    @else
        {{-- Not Configured - Setup Guide --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">🚀 Setup E-commerce Bot</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-2xl mx-auto">
                    Transform your WhatsApp into a powerful e-commerce platform. Connect your Google Sheets to automatically sync products, process orders, and manage your business seamlessly.
                </p>

                {{-- Features --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 text-left">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">📊 Product Management</h3>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                            <li>• Sync products from Google Sheets</li>
                            <li>• Real-time inventory tracking</li>
                            <li>• Automated stock alerts</li>
                            <li>• Product image management</li>
                        </ul>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <h3 class="font-semibold text-green-900 dark:text-green-100 mb-2">📋 Order Processing</h3>
                        <ul class="text-sm text-green-700 dark:text-green-300 space-y-1">
                            <li>• WhatsApp order automation</li>
                            <li>• Payment tracking</li>
                            <li>• Delivery management</li>
                            <li>• Customer communication</li>
                        </ul>
                    </div>

                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                        <h3 class="font-semibold text-purple-900 dark:text-purple-100 mb-2">🎯 Smart Upselling</h3>
                        <ul class="text-sm text-purple-700 dark:text-purple-300 space-y-1">
                            <li>• AI-powered recommendations</li>
                            <li>• Automated marketing campaigns</li>
                            <li>• Abandoned cart recovery</li>
                            <li>• Customer behavior analysis</li>
                        </ul>
                    </div>

                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                        <h3 class="font-semibold text-orange-900 dark:text-orange-100 mb-2">📈 Analytics & Insights</h3>
                        <ul class="text-sm text-orange-700 dark:text-orange-300 space-y-1">
                            <li>• Sales performance reports</li>
                            <li>• Customer insights dashboard</li>
                            <li>• Revenue tracking</li>
                            <li>• Export capabilities</li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-4">
                    <a 
                        href="{{ tenant_route('tenant.ecommerce.setup') }}" 
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                    >
                        🚀 Start Setup Now
                    </a>
                    
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Setup takes just 2 minutes. You'll need a Google Sheets document with your product catalog.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Disconnect Confirmation Modal --}}
    @if($showDisconnectConfirm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Disconnect E-commerce Bot?</h3>
                    </div>
                </div>
                
                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        This will disconnect your Google Sheets and disable e-commerce automation. Your existing products and orders data will be preserved and you can reconnect later.
                    </p>
                </div>
                
                <div class="flex space-x-3">
                    <button 
                        wire:click="disconnectEcommerce"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        Yes, Disconnect
                    </button>
                    <button 
                        wire:click="cancelDisconnect"
                        class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
