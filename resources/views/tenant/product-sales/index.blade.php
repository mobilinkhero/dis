{{-- Product Sales Management - Top-notch e-commerce interface --}}
@extends('tenant.layouts.app')

@section('title', 'Product Sales')

@push('styles')
<style>
    .product-sales-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }
    
    .glass-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    .gradient-bg {
        background: linear-gradient(45deg, #4f46e5, #7c3aed, #db2777);
        background-size: 300% 300%;
        animation: gradientShift 8s ease infinite;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
</style>
@endpush

@section('content')
<div class="product-sales-container">
    <div class="container mx-auto px-4 py-8">
        {{-- Header Section --}}
        <div class="text-center mb-12">
            <div class="glass-card p-8 mb-8">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-4">
                    🛍️ Product Sales Hub
                </h1>
                <p class="text-xl text-white/80 max-w-2xl mx-auto">
                    AI-powered e-commerce platform integrated with WhatsApp. Sell products, manage orders, and boost revenue with intelligent recommendations.
                </p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="glass-card p-6 text-center">
                <div class="text-3xl mb-2">📊</div>
                <h3 class="text-lg font-semibold text-white mb-1">Total Sales</h3>
                <p class="text-2xl font-bold text-green-300">$12,450</p>
                <p class="text-sm text-white/60">+15% this month</p>
            </div>
            
            <div class="glass-card p-6 text-center">
                <div class="text-3xl mb-2">🛒</div>
                <h3 class="text-lg font-semibold text-white mb-1">Orders</h3>
                <p class="text-2xl font-bold text-blue-300">156</p>
                <p class="text-sm text-white/60">+8 today</p>
            </div>
            
            <div class="glass-card p-6 text-center">
                <div class="text-3xl mb-2">👥</div>
                <h3 class="text-lg font-semibold text-white mb-1">Customers</h3>
                <p class="text-2xl font-bold text-purple-300">89</p>
                <p class="text-sm text-white/60">+12 new</p>
            </div>
            
            <div class="glass-card p-6 text-center">
                <div class="text-3xl mb-2">🤖</div>
                <h3 class="text-lg font-semibold text-white mb-1">AI Recommendations</h3>
                <p class="text-2xl font-bold text-yellow-300">94%</p>
                <p class="text-sm text-white/60">accuracy rate</p>
            </div>
        </div>

        {{-- Main Action Buttons --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            {{-- Product Catalog --}}
            <div class="glass-card p-8 feature-card transition-all duration-300 cursor-pointer" onclick="openProductCatalog()">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 gradient-bg rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Product Catalog</h3>
                    <p class="text-white/70 mb-4">Browse and manage your product inventory with AI-powered recommendations</p>
                    <button class="bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-lg transition-colors">
                        Open Catalog
                    </button>
                </div>
            </div>

            {{-- WhatsApp Integration --}}
            <div class="glass-card p-8 feature-card transition-all duration-300 cursor-pointer" onclick="openWhatsAppPanel()">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 gradient-bg rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.346"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">WhatsApp Sales</h3>
                    <p class="text-white/70 mb-4">Send product catalogs and process orders directly through WhatsApp</p>
                    <button class="bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-lg transition-colors">
                        Launch WhatsApp
                    </button>
                </div>
            </div>

            {{-- Order Management --}}
            <div class="glass-card p-8 feature-card transition-all duration-300 cursor-pointer" onclick="openOrderManagement()">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 gradient-bg rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Order Management</h3>
                    <p class="text-white/70 mb-4">Track, manage, and fulfill customer orders with real-time updates</p>
                    <button class="bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-lg transition-colors">
                        View Orders
                    </button>
                </div>
            </div>
        </div>

        {{-- WhatsApp Connections --}}
        @if($whatsappConnections->count() > 0)
        <div class="glass-card p-8 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.346"/>
                </svg>
                WhatsApp Connections
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($whatsappConnections as $connection)
                <div class="bg-white/10 rounded-xl p-6 border border-white/20">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">{{ $connection->name ?? 'WhatsApp Business' }}</h3>
                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">Connected</span>
                    </div>
                    
                    <p class="text-white/70 text-sm mb-4">
                        Phone: {{ $connection->phone_number }}<br>
                        Business ID: {{ Str::limit($connection->business_account_id, 15) }}
                    </p>
                    
                    <div class="flex space-x-2">
                        <button 
                            onclick="sendCatalogViaWhatsApp('{{ $connection->id }}')"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white text-sm py-2 px-3 rounded-lg transition-colors"
                        >
                            Send Catalog
                        </button>
                        <button 
                            onclick="viewWhatsAppChats('{{ $connection->id }}')"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm py-2 px-3 rounded-lg transition-colors"
                        >
                            View Chats
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Quick Actions --}}
        <div class="glass-card p-8 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6">Quick Actions</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <button onclick="showAIInsights()" class="bg-white/10 hover:bg-white/20 text-white p-4 rounded-lg transition-colors text-center">
                    <div class="text-2xl mb-2">🤖</div>
                    <span class="text-sm">AI Insights</span>
                </button>
                
                <button onclick="exportSalesReport()" class="bg-white/10 hover:bg-white/20 text-white p-4 rounded-lg transition-colors text-center">
                    <div class="text-2xl mb-2">📊</div>
                    <span class="text-sm">Sales Report</span>
                </button>
                
                <button onclick="manageInventory()" class="bg-white/10 hover:bg-white/20 text-white p-4 rounded-lg transition-colors text-center">
                    <div class="text-2xl mb-2">📦</div>
                    <span class="text-sm">Inventory</span>
                </button>
                
                <button onclick="customerAnalytics()" class="bg-white/10 hover:bg-white/20 text-white p-4 rounded-lg transition-colors text-center">
                    <div class="text-2xl mb-2">👥</div>
                    <span class="text-sm">Customers</span>
                </button>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="glass-card p-8">
            <h2 class="text-2xl font-bold text-white mb-6">Recent Activity</h2>
            
            <div class="space-y-4">
                <div class="flex items-center space-x-4 bg-white/5 p-4 rounded-lg">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium">New order #ORD-2024001</p>
                        <p class="text-white/60 text-sm">Customer: John Doe • Total: $149.99</p>
                    </div>
                    <span class="text-white/60 text-sm">2 min ago</span>
                </div>
                
                <div class="flex items-center space-x-4 bg-white/5 p-4 rounded-lg">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium">WhatsApp catalog sent</p>
                        <p class="text-white/60 text-sm">Sent to +1 234-567-8900 • 5 products</p>
                    </div>
                    <span class="text-white/60 text-sm">15 min ago</span>
                </div>
                
                <div class="flex items-center space-x-4 bg-white/5 p-4 rounded-lg">
                    <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-medium">AI recommendation generated</p>
                        <p class="text-white/60 text-sm">4 personalized products for customer Sarah Smith</p>
                    </div>
                    <span class="text-white/60 text-sm">1 hour ago</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Product Catalog Modal --}}
<div id="productCatalogModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeProductCatalog()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[95vh] overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h2 class="text-2xl font-bold text-gray-900">Product Catalog</h2>
                <button onclick="closeProductCatalog()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="productCatalogContent" class="h-[calc(95vh-120px)] overflow-auto">
                {{-- Vue.js Product Catalog will be mounted here --}}
            </div>
        </div>
    </div>
</div>

{{-- WhatsApp Panel Modal --}}
<div id="whatsappModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeWhatsAppPanel()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b bg-green-600 text-white">
                <h2 class="text-2xl font-bold flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.346"/>
                    </svg>
                    WhatsApp Sales Panel
                </h2>
                <button onclick="closeWhatsAppPanel()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                {{-- WhatsApp integration content will go here --}}
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🚧</div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">WhatsApp Sales Panel</h3>
                    <p class="text-gray-600">Send product catalogs, manage conversations, and process orders via WhatsApp.</p>
                    <p class="text-sm text-gray-500 mt-4">This feature is ready for integration with your WhatsApp flow builder.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Product Catalog functionality
let productCatalogApp = null;

function openProductCatalog() {
    document.getElementById('productCatalogModal').classList.remove('hidden');
    
    // Initialize Vue.js Product Catalog
    if (!productCatalogApp) {
        import('/js/components/ProductCatalog.vue').then(module => {
            const { createApp } = Vue;
            productCatalogApp = createApp({
                components: {
                    ProductCatalog: module.default
                },
                template: '<ProductCatalog />'
            });
            productCatalogApp.mount('#productCatalogContent');
        });
    }
}

function closeProductCatalog() {
    document.getElementById('productCatalogModal').classList.add('hidden');
}

function openWhatsAppPanel() {
    document.getElementById('whatsappModal').classList.remove('hidden');
}

function closeWhatsAppPanel() {
    document.getElementById('whatsappModal').classList.add('hidden');
}

function openOrderManagement() {
    // Navigate to order management page (placeholder for future implementation)
    alert('📦 Order Management: This feature will show customer orders, tracking, and fulfillment status. Coming soon!');
}

// WhatsApp Integration functions
function sendCatalogViaWhatsApp(connectionId) {
    const phone = prompt('Enter customer phone number:');
    if (!phone) return;
    
    fetch('{{ route("tenant.product-sales.send-catalog") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            phone: phone,
            connection_id: connectionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Product catalog sent successfully!');
        } else {
            alert('Failed to send catalog: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the catalog.');
    });
}

function viewWhatsAppChats(connectionId) {
    // Navigate to WhatsApp chats (using existing chat route)
    window.location.href = `{{ route("tenant.chat") }}?connection=${connectionId}`;
}

// Quick Action functions
function showAIInsights() {
    alert('🤖 AI Insights: Your top-performing products are Electronics (45% of sales) and Clothing (30% of sales). Consider promoting Home & Garden products for growth opportunity.');
}

function exportSalesReport() {
    alert('📊 Generating sales report... This feature will export your sales data to Excel format.');
}

function manageInventory() {
    alert('📦 Inventory Management: This will open your product inventory dashboard.');
}

function customerAnalytics() {
    alert('👥 Customer Analytics: View detailed insights about your customer behavior and preferences.');
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
    console.log('Product Sales Hub loaded successfully!');
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductCatalog();
        closeWhatsAppPanel();
    }
});
</script>
@endpush
