<div x-data="productSalesData()" x-init="initCharts()">
    <x-slot:title>
        {{ t('product_sales') }}
    </x-slot:title>

    {{-- Header Section --}}
    <div class="mb-6">
        <x-breadcrumb :items="[
                ['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')],
                ['label' => t('product_sales')]
            ]" />
        
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
            <div>
                <x-settings-heading class="font-display">
                    <x-heroicon-o-shopping-cart class="w-8 h-8 mr-3 text-primary-600" />
                    {{ t('product_sales_dashboard') }}
                </x-settings-heading>
                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ t('manage_orders_track_sales_analyze_performance') }}</p>
            </div>
            
            {{-- Period Selector --}}
            <div class="flex items-center space-x-4">
                <select wire:model="selectedPeriod" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                    <option value="7">{{ t('last_7_days') }}</option>
                    <option value="30">{{ t('last_30_days') }}</option>
                    <option value="90">{{ t('last_90_days') }}</option>
                    <option value="365">{{ t('last_year') }}</option>
                </select>
                
                <button @click="refreshData()" class="btn-primary">
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                    {{ t('refresh') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Analytics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Orders --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('total_orders') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($totalOrders) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-shopping-bag class="w-6 h-6 text-blue-600 dark:text-blue-300" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-600 dark:text-green-400 flex items-center">
                    <x-heroicon-o-arrow-trending-up class="w-4 h-4 mr-1" />
                    +12%
                </span>
                <span class="text-gray-600 dark:text-gray-400 ml-2">{{ t('vs_previous_period') }}</span>
            </div>
        </div>

        {{-- Total Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('total_revenue') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-green-600 dark:text-green-300" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-600 dark:text-green-400 flex items-center">
                    <x-heroicon-o-arrow-trending-up class="w-4 h-4 mr-1" />
                    +8.2%
                </span>
                <span class="text-gray-600 dark:text-gray-400 ml-2">{{ t('vs_previous_period') }}</span>
            </div>
        </div>

        {{-- Conversion Rate --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('conversion_rate') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $conversionRate }}%</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <x-heroicon-o-chart-pie class="w-6 h-6 text-purple-600 dark:text-purple-300" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-600 dark:text-green-400 flex items-center">
                    <x-heroicon-o-arrow-trending-up class="w-4 h-4 mr-1" />
                    +3.1%
                </span>
                <span class="text-gray-600 dark:text-gray-400 ml-2">{{ t('vs_previous_period') }}</span>
            </div>
        </div>

        {{-- Average Order Value --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('avg_order_value') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">${{ number_format($averageOrderValue, 2) }}</p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-full">
                    <x-heroicon-o-calculator class="w-6 h-6 text-orange-600 dark:text-orange-300" />
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-green-600 dark:text-green-400 flex items-center">
                    <x-heroicon-o-arrow-trending-up class="w-4 h-4 mr-1" />
                    +5.7%
                </span>
                <span class="text-gray-600 dark:text-gray-400 ml-2">{{ t('vs_previous_period') }}</span>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Sales Chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ t('sales_overview') }}</h3>
            <canvas id="salesChart" width="400" height="200"></canvas>
        </div>

        {{-- Top Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ t('top_products') }}</h3>
            <div class="space-y-4">
                @forelse($topProducts as $product)
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product['product_name'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $product['total_quantity'] }} {{ t('sold') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($product['total_revenue'], 2) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <x-heroicon-o-shopping-cart class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">{{ t('no_sales_data_available') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Orders Management Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        {{-- Header --}}
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('recent_orders') }}</h3>
                
                {{-- Search and Filters --}}
                <div class="flex flex-col lg:flex-row gap-4 w-full lg:w-auto">
                    <div class="relative">
                        <input type="text" 
                               wire:model.live.debounce.500ms="searchTerm"
                               placeholder="{{ t('search_orders') }}"
                               class="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm w-full lg:w-64">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" />
                    </div>
                    
                    <select wire:model.live="selectedStatus" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm">
                        <option value="">{{ t('all_statuses') }}</option>
                        <option value="pending">{{ t('pending') }}</option>
                        <option value="confirmed">{{ t('confirmed') }}</option>
                        <option value="processing">{{ t('processing') }}</option>
                        <option value="shipped">{{ t('shipped') }}</option>
                        <option value="delivered">{{ t('delivered') }}</option>
                        <option value="cancelled">{{ t('cancelled') }}</option>
                    </select>

                    <button wire:click="exportOrders" class="btn-secondary">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                        {{ t('export') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Orders Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('order') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('customer') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('amount') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('date') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ t('actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $order->contact->name ?? t('unknown') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $order->contact->phone ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($order->total_amount, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($order->status === 'delivered') bg-green-100 text-green-800 
                                    @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                    @elseif($order->status === 'processing') bg-blue-100 text-blue-800
                                    @elseif($order->status === 'shipped') bg-purple-100 text-purple-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ t($order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $order->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button wire:click="viewOrder({{ $order->id }})" 
                                            class="text-primary-600 hover:text-primary-900 dark:text-primary-400">
                                        <x-heroicon-o-eye class="w-4 h-4" />
                                    </button>
                                    @if($order->status !== 'cancelled' && $order->status !== 'delivered')
                                        <div class="relative" x-data="{ open: false }">
                                            <button @click="open = !open" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                <x-heroicon-o-ellipsis-vertical class="w-4 h-4" />
                                            </button>
                                            <div x-show="open" @click.away="open = false" 
                                                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10">
                                                <div class="py-1">
                                                    @foreach(['confirmed', 'processing', 'shipped', 'delivered'] as $status)
                                                        @if($order->status !== $status)
                                                            <button wire:click="updateOrderStatus({{ $order->id }}, '{{ $status }}')"
                                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                                {{ t('mark_as_' . $status) }}
                                                            </button>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <x-heroicon-o-shopping-cart class="w-12 h-12 text-gray-400 mb-4" />
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ t('no_orders_found') }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('orders_will_appear_here_when_customers_place_them') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Order Details Modal --}}
    @if($showOrderModal && $selectedOrder)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ t('order_details') }} - {{ $selectedOrder->order_number }}
                        </h3>
                        <button wire:click="closeOrderModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-heroicon-o-x-mark class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="space-y-6">
                        {{-- Customer Info --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ t('customer_information') }}</h4>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-sm"><span class="font-medium">{{ t('name') }}:</span> {{ $selectedOrder->contact->name ?? t('unknown') }}</p>
                                <p class="text-sm"><span class="font-medium">{{ t('phone') }}:</span> {{ $selectedOrder->contact->phone ?? t('unknown') }}</p>
                                @if($selectedOrder->shipping_address)
                                    <p class="text-sm"><span class="font-medium">{{ t('address') }}:</span> {{ $selectedOrder->shipping_address }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Order Items --}}
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ t('order_items') }}</h4>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                @foreach(json_decode($selectedOrder->items, true) as $item)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600 last:border-b-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['product_name'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('qty') }}: {{ $item['quantity'] }} × ${{ number_format($item['unit_price'], 2) }}</p>
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item['subtotal'], 2) }}</p>
                                    </div>
                                @endforeach
                                <div class="pt-2 border-t border-gray-300 dark:border-gray-600">
                                    <div class="flex justify-between items-center">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ t('total') }}:</p>
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($selectedOrder->total_amount, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Order Status & Notes --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ t('order_status') }}</h4>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full 
                                    @if($selectedOrder->status === 'delivered') bg-green-100 text-green-800 
                                    @elseif($selectedOrder->status === 'cancelled') bg-red-100 text-red-800
                                    @elseif($selectedOrder->status === 'processing') bg-blue-100 text-blue-800
                                    @elseif($selectedOrder->status === 'shipped') bg-purple-100 text-purple-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ t($selectedOrder->status) }}
                                </span>
                            </div>
                            @if($selectedOrder->notes)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ t('notes') }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedOrder->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button wire:click="closeOrderModal" class="btn-secondary">
                            {{ t('close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- JavaScript for Charts --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function productSalesData() {
            return {
                salesChart: null,
                
                initCharts() {
                    this.createSalesChart();
                },
                
                createSalesChart() {
                    const ctx = document.getElementById('salesChart');
                    if (ctx && !this.salesChart) {
                        const salesData = @json($salesChart);
                        
                        this.salesChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: salesData.map(item => item.date),
                                datasets: [{
                                    label: 'Revenue ($)',
                                    data: salesData.map(item => item.revenue),
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4
                                }, {
                                    label: 'Orders',
                                    data: salesData.map(item => item.orders),
                                    borderColor: 'rgb(34, 197, 94)',
                                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4,
                                    yAxisID: 'y1'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    }
                                },
                                scales: {
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                    },
                                    y1: {
                                        type: 'linear',
                                        display: true,
                                        position: 'right',
                                        grid: {
                                            drawOnChartArea: false,
                                        },
                                    },
                                }
                            }
                        });
                    }
                },
                
                refreshData() {
                    @this.call('refreshData');
                    if (this.salesChart) {
                        this.salesChart.destroy();
                        this.salesChart = null;
                        setTimeout(() => this.createSalesChart(), 100);
                    }
                }
            }
        }
    </script>
    @endpush
</div>
