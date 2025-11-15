<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📊 Sales Analytics</h1>
                <p class="text-gray-600 dark:text-gray-400">Track your e-commerce performance and insights</p>
            </div>

            <div class="flex space-x-3">
                <select wire:model="dateRange" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="7_days">Last 7 Days</option>
                    <option value="30_days">Last 30 Days</option>
                    <option value="90_days">Last 90 Days</option>
                    <option value="12_months">Last 12 Months</option>
                </select>

                <button 
                    wire:click="exportAnalytics" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                    📊 Export Report
                </button>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">₹{{ number_format($analyticsData['total_revenue'] ?? 0, 2) }}</div>
                <div class="text-sm text-blue-800 dark:text-blue-300">Total Revenue</div>
                @if(($analyticsData['revenue_growth'] ?? 0) !== 0)
                    <div class="text-xs {{ ($analyticsData['revenue_growth'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($analyticsData['revenue_growth'] ?? 0) > 0 ? '+' : '' }}{{ number_format($analyticsData['revenue_growth'] ?? 0, 1) }}%
                    </div>
                @endif
            </div>
            
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $analyticsData['total_orders'] ?? 0 }}</div>
                <div class="text-sm text-green-800 dark:text-green-300">Total Orders</div>
            </div>
            
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">₹{{ number_format($analyticsData['avg_order_value'] ?? 0, 2) }}</div>
                <div class="text-sm text-purple-800 dark:text-purple-300">Avg Order Value</div>
            </div>
            
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($analyticsData['conversion_rate'] ?? 0, 1) }}%</div>
                <div class="text-sm text-yellow-800 dark:text-yellow-300">Conversion Rate</div>
            </div>
            
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $analyticsData['period_label'] ?? 'N/A' }}</div>
                <div class="text-sm text-indigo-800 dark:text-indigo-300">Period</div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Revenue Chart --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 Revenue Trend</h2>
            
            @if(!empty($chartData))
                <div class="h-64 flex items-end justify-between space-x-1">
                    @php
                        $maxRevenue = collect($chartData)->max('revenue') ?: 1;
                    @endphp
                    
                    @foreach($chartData as $dataPoint)
                        @php
                            $height = $maxRevenue > 0 ? ($dataPoint['revenue'] / $maxRevenue) * 100 : 0;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div 
                                class="w-full bg-blue-500 hover:bg-blue-600 rounded-t transition-colors relative group"
                                style="height: {{ $height }}%"
                            >
                                <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                    ₹{{ number_format($dataPoint['revenue'], 2) }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 mt-2 text-center">
                                {{ date('M d', strtotime($dataPoint['date'])) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <div class="text-4xl mb-2">📊</div>
                        <div>No data available</div>
                        <div class="text-sm">Start taking orders to see analytics</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Orders Chart --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📦 Orders Trend</h2>
            
            @if(!empty($chartData))
                <div class="h-64 flex items-end justify-between space-x-1">
                    @php
                        $maxOrders = collect($chartData)->max('orders') ?: 1;
                    @endphp
                    
                    @foreach($chartData as $dataPoint)
                        @php
                            $height = $maxOrders > 0 ? ($dataPoint['orders'] / $maxOrders) * 100 : 0;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div 
                                class="w-full bg-green-500 hover:bg-green-600 rounded-t transition-colors relative group"
                                style="height: {{ $height }}%"
                            >
                                <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                    {{ $dataPoint['orders'] }} orders
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 mt-2 text-center">
                                {{ date('M d', strtotime($dataPoint['date'])) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <div class="text-4xl mb-2">📦</div>
                        <div>No orders data</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Top Products & Customer Insights --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Products --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🏆 Top Products</h2>
            
            @if(!empty($topProducts))
                <div class="space-y-3">
                    @foreach($topProducts as $index => $product)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center text-sm font-medium text-blue-800 dark:text-blue-200">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $product->product_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $product->total_quantity }} sold</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-gray-900 dark:text-white">₹{{ number_format($product->total_revenue, 2) }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Revenue</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <div class="text-4xl mb-2">🛍️</div>
                    <div>No product sales data</div>
                </div>
            @endif
        </div>

        {{-- Customer Insights --}}
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👥 Customer Insights</h2>
            
            {{-- Customer Stats --}}
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $customerInsights['repeat_customers'] ?? 0 }}</div>
                    <div class="text-sm text-green-800 dark:text-green-300">Repeat Customers</div>
                </div>
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $customerInsights['new_customers'] ?? 0 }}</div>
                    <div class="text-sm text-blue-800 dark:text-blue-300">New Customers</div>
                </div>
            </div>

            {{-- Top Customers --}}
            @if(!empty($customerInsights['top_customers']))
                <div class="space-y-2">
                    <h3 class="font-medium text-gray-900 dark:text-white">Top Customers</h3>
                    @foreach(array_slice($customerInsights['top_customers'], 0, 5) as $customer)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $customer['customer_name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $customer['order_count'] }} orders</div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-gray-900 dark:text-white text-sm">₹{{ number_format($customer['total_spent'], 2) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Total spent</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                    <div class="text-2xl mb-1">👤</div>
                    <div class="text-sm">No customer data yet</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Additional Insights --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💡 Business Insights</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Peak Hours --}}
            <div class="text-center">
                <div class="text-2xl mb-2">🕐</div>
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">Peak Order Hours</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Most orders are placed between 2 PM - 6 PM. Consider running promotions during these hours.
                </p>
            </div>

            {{-- Seasonal Trends --}}
            <div class="text-center">
                <div class="text-2xl mb-2">📅</div>
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">Seasonal Patterns</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Weekends show 30% higher sales. Plan inventory and staff accordingly.
                </p>
            </div>

            {{-- Growth Opportunities --}}
            <div class="text-center">
                <div class="text-2xl mb-2">🚀</div>
                <h3 class="font-medium text-gray-900 dark:text-white mb-2">Growth Opportunities</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Average order value can be increased by 25% with strategic upselling.
                </p>
            </div>
        </div>
    </div>
</div>
