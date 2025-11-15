<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🚀 Upselling Campaigns</h1>
                <p class="text-gray-600 dark:text-gray-400">AI-powered upselling and cross-selling automation</p>
            </div>

            <div class="flex items-center space-x-3">
                <div class="flex items-center">
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:click="toggleUpselling"
                            {{ $isEnabled ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable Upselling</span>
                    </label>
                </div>

                <button 
                    wire:click="generateAiSuggestions" 
                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    wire:loading.attr="disabled"
                >
                    <div wire:loading.remove wire:target="generateAiSuggestions">
                        🤖 AI Suggestions
                    </div>
                    <div wire:loading wire:target="generateAiSuggestions">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        </svg>
                        Analyzing...
                    </div>
                </button>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total_orders'] }}</div>
                <div class="text-sm text-blue-800 dark:text-blue-300">Total Orders</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">₹{{ number_format($stats['avg_order_value'], 2) }}</div>
                <div class="text-sm text-green-800 dark:text-green-300">Avg Order Value</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['conversion_rate'], 1) }}%</div>
                <div class="text-sm text-purple-800 dark:text-purple-300">Conversion Rate</div>
            </div>
        </div>
    </div>

    {{-- AI Suggestions --}}
    @if(!empty($aiSuggestions))
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🤖 AI-Generated Upselling Strategies</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    Generated on {{ $aiSuggestions['generated_at'] }}
                </div>
                <div class="prose dark:prose-invert max-w-none text-sm">
                    {!! nl2br(e($aiSuggestions['suggestions'])) !!}
                </div>
            </div>
        </div>
    @endif

    {{-- Upselling Configuration --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Basic Settings --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚙️ Basic Settings</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Minimum Order Value for Upselling (₹)
                    </label>
                    <input 
                        type="number" 
                        wire:model="upsellingRules.minimum_order_value"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        min="0"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Default Discount Percentage (%)
                    </label>
                    <input 
                        type="number" 
                        wire:model="upsellingRules.discount_percentage"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        min="0"
                        max="100"
                    >
                </div>

                <button 
                    wire:click="saveUpsellingRules"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                    💾 Save Settings
                </button>
            </div>
        </div>

        {{-- Abandoned Cart Recovery --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🛒 Abandoned Cart Recovery</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            wire:model="upsellingRules.abandoned_cart_recovery.enabled"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        >
                        <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable Cart Recovery</span>
                    </label>
                </div>

                @if($upsellingRules['abandoned_cart_recovery']['enabled'] ?? false)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Reminder Schedule (hours after abandonment)
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($upsellingRules['abandoned_cart_recovery']['reminder_intervals'] ?? [1, 24, 72] as $index => $interval)
                                <input 
                                    type="number" 
                                    wire:model="upsellingRules.abandoned_cart_recovery.reminder_intervals.{{ $index }}"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                    min="1"
                                >
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Progressive Discounts (%)
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($upsellingRules['abandoned_cart_recovery']['discount_progression'] ?? [5, 10, 15] as $index => $discount)
                                <input 
                                    type="number" 
                                    wire:model="upsellingRules.abandoned_cart_recovery.discount_progression.{{ $index }}"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                    min="0"
                                    max="50"
                                >
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Increasing discounts for each reminder</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Product Recommendations --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎯 Product Recommendations</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Cross-sell Products --}}
            <div>
                <h3 class="font-medium text-gray-900 dark:text-white mb-3">Cross-sell Products</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    @foreach($products as $product)
                        <label class="flex items-center p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input 
                                type="checkbox" 
                                wire:model="upsellingRules.cross_sell_products"
                                value="{{ $product->id }}"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            >
                            <div class="ml-3 flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">₹{{ $product->formatted_price }} • Stock: {{ $product->stock_quantity }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Recent Orders for Analysis --}}
            <div>
                <h3 class="font-medium text-gray-900 dark:text-white mb-3">Recent Order Patterns</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    @foreach($recentOrders->take(5) as $order)
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</div>
                                <div class="text-sm text-gray-500">{{ $order->formatted_total }}</div>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                Items: {{ $order->items->pluck('product_name')->take(2)->implode(', ') }}
                                @if($order->items->count() > 2)
                                    + {{ $order->items->count() - 2 }} more
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Campaign Templates --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📱 Campaign Templates</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Bundle Offer Template --}}
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">🎁 Bundle Offer</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    "Hi {{customer_name}}! Complete your order with these matching items and save {{discount}}%!"
                </p>
                <button class="w-full bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm transition-colors">
                    Use Template
                </button>
            </div>

            {{-- Limited Time Offer --}}
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">⏰ Limited Time</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    "Only 2 hours left! Get {{product_name}} with {{discount}}% off. Don't miss out!"
                </p>
                <button class="w-full bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded text-sm transition-colors">
                    Use Template
                </button>
            </div>

            {{-- Personalized Recommendation --}}
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">👤 Personalized</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    "Based on your purchase of {{last_product}}, customers also love {{recommended_product}}!"
                </p>
                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded text-sm transition-colors">
                    Use Template
                </button>
            </div>
        </div>
    </div>

    {{-- Performance Metrics --}}
    @if($isEnabled)
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Performance Metrics</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">0</div>
                    <div class="text-sm text-blue-800 dark:text-blue-300">Campaigns Sent</div>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">0%</div>
                    <div class="text-sm text-green-800 dark:text-green-300">Response Rate</div>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">₹0</div>
                    <div class="text-sm text-purple-800 dark:text-purple-300">Additional Revenue</div>
                </div>
                <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">0</div>
                    <div class="text-sm text-yellow-800 dark:text-yellow-300">Recovered Carts</div>
                </div>
            </div>
        </div>
    @endif
</div>
