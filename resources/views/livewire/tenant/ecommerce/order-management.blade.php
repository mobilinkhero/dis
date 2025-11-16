<div class="space-y-6">
    {{-- Header & Stats --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📋 Order Management</h1>
                <p class="text-gray-600 dark:text-gray-400">Track and manage all your WhatsApp orders</p>
            </div>

            <div class="flex space-x-3">
                <button 
                    wire:click="exportOrders" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                    📊 Export Orders
                </button>

                <a href="{{ tenant_route('tenant.ecommerce.setup') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    ⚙️ Settings
                </a>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total'] }}</div>
                <div class="text-sm text-blue-800 dark:text-blue-300">Total Orders</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</div>
                <div class="text-sm text-yellow-800 dark:text-yellow-300">Pending</div>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['confirmed'] }}</div>
                <div class="text-sm text-green-800 dark:text-green-300">Confirmed</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['delivered'] }}</div>
                <div class="text-sm text-purple-800 dark:text-purple-300">Delivered</div>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">₹{{ number_format($stats['total_revenue'], 2) }}</div>
                <div class="text-sm text-indigo-800 dark:text-indigo-300">Total Revenue</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <div>
                <input 
                    type="text" 
                    wire:model.debounce.300ms="search"
                    placeholder="🔍 Search orders..."
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
                <select wire:model="dateRange" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
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

            <div>
                @if(!empty($selectedOrders))
                    <select wire:model="bulkAction" wire:change="$emit('bulkActionSelected')" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Bulk Actions</option>
                        <option value="confirmed">Mark as Confirmed</option>
                        <option value="processing">Mark as Processing</option>
                        <option value="shipped">Mark as Shipped</option>
                        <option value="delivered">Mark as Delivered</option>
                    </select>
                @endif
            </div>
        </div>

        @if(!empty($selectedOrders))
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ count($selectedOrders) }} orders selected
                    </span>
                    <div class="flex space-x-2">
                        @foreach(['confirmed', 'processing', 'shipped', 'delivered'] as $status)
                            <button wire:click="bulkUpdateStatus('{{ $status }}')" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">
                                {{ ucfirst($status) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Orders Table --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" wire:model="selectAll" class="rounded border-gray-300 text-blue-600">
                    </th>
                    <th wire:click="sortBy('order_number')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        Order #
                        @if($sortBy === 'order_number')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Items</th>
                    <th wire:click="sortBy('total_amount')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        Total
                        @if($sortBy === 'total_amount')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th wire:click="sortBy('created_at')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                        Date
                        @if($sortBy === 'created_at')
                            <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                        @endif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                @forelse($orders as $order)
                    <tr class="{{ in_array($order->id, $selectedOrders) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        <td class="px-6 py-4">
                            <input type="checkbox" wire:model="selectedOrders" value="{{ $order->id }}" class="rounded border-gray-300 text-blue-600">
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->customer_name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $order->customer_phone }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-gray-300">
                                {{ $order->items->count() }} item(s)
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $order->items->sum('quantity') }} total qty
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $order->formatted_total }}</div>
                            @if($order->payment_status)
                                <div class="text-xs {{ $order->payment_status === 'paid' ? 'text-green-600' : 'text-yellow-600' }}">
                                    {{ ucfirst($order->payment_status) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                    'processing' => 'bg-purple-100 text-purple-800',
                                    'shipped' => 'bg-indigo-100 text-indigo-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $order->created_at->format('M d, Y') }}
                            <div class="text-xs">{{ $order->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <button wire:click="viewOrderDetails({{ $order->id }})" class="text-blue-600 hover:text-blue-900">View</button>
                            
                            @if($order->canBeCancelled())
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button @click="open = !open" class="text-gray-600 hover:text-gray-900">
                                        Status ▼
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10">
                                        @foreach(['confirmed', 'processing', 'shipped', 'delivered'] as $status)
                                            @if($order->status !== $status)
                                                <button 
                                                    wire:click="updateOrderStatus({{ $order->id }}, '{{ $status }}')"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    @click="open = false"
                                                >
                                                    Mark as {{ ucfirst($status) }}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="space-y-2">
                                <div class="text-4xl">📋</div>
                                <div>No orders found</div>
                                <div class="text-sm">Orders will appear here once customers start purchasing</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($orders->hasPages())
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-600">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Order Details Modal --}}
    @if($showOrderDetails && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }" x-show="show">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="show" x-transition></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full" x-show="show" x-transition>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Details</h3>
                            <button wire:click="closeOrderDetails" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        <div class="space-y-6">
                            {{-- Order Info --}}
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Order Information</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Order Number:</span>
                                        <span class="ml-2 font-medium">{{ $selectedOrder->order_number }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                        <span class="ml-2 font-medium">{{ ucfirst($selectedOrder->status) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Date:</span>
                                        <span class="ml-2">{{ $selectedOrder->created_at->format('M d, Y H:i') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Total:</span>
                                        <span class="ml-2 font-medium">{{ $selectedOrder->formatted_total }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Customer Info --}}
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Customer Information</h4>
                                <div class="space-y-2 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Name:</span>
                                        <span class="ml-2">{{ $selectedOrder->customer_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Phone:</span>
                                        <span class="ml-2">{{ $selectedOrder->customer_phone }}</span>
                                    </div>
                                    @if($selectedOrder->customer_email)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Email:</span>
                                            <span class="ml-2">{{ $selectedOrder->customer_email }}</span>
                                        </div>
                                    @endif
                                    @if($selectedOrder->delivery_address)
                                        <div>
                                            <span class="text-gray-500 dark:text-gray-400">Address:</span>
                                            <span class="ml-2">{{ $selectedOrder->delivery_address }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Order Items --}}
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Order Items</h4>
                                <div class="space-y-2">
                                    @foreach($selectedOrder->items as $item)
                                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-600">
                                            <div>
                                                <div class="font-medium">{{ $item->product_name }}</div>
                                                @if($item->product_sku)
                                                    <div class="text-xs text-gray-500">SKU: {{ $item->product_sku }}</div>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <div>{{ $item->quantity }} × ₹{{ number_format($item->price, 2) }}</div>
                                                <div class="font-medium">₹{{ number_format($item->total_amount, 2) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-between">
                        <button wire:click="sendTrackingUpdate({{ $selectedOrder->id }})" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            📱 Send Tracking Update
                        </button>
                        
                        <button wire:click="closeOrderDetails" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
