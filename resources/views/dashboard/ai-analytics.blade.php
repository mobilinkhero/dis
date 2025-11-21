<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI E-Commerce Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .metric-card { transition: all 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="gradient-bg text-white p-6">
        <div class="container mx-auto">
            <h1 class="text-4xl font-bold flex items-center">
                <i data-lucide="brain" class="w-10 h-10 mr-4"></i>
                Ultra-Advanced AI E-Commerce Dashboard
            </h1>
            <p class="text-xl opacity-90 mt-2">Real-time intelligence and predictive analytics</p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-8">
        <!-- Key Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- AI Performance -->
            <div class="bg-white p-6 rounded-xl card-shadow metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">AI Performance</p>
                        <p class="text-3xl font-bold text-green-600">98.5%</p>
                        <p class="text-sm text-green-500">+2.3% vs yesterday</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i data-lucide="cpu" class="w-8 h-8 text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Customer Intelligence -->
            <div class="bg-white p-6 rounded-xl card-shadow metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Customer Insights</p>
                        <p class="text-3xl font-bold text-blue-600">2,847</p>
                        <p class="text-sm text-blue-500">Active profiles</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i data-lucide="users" class="w-8 h-8 text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Predictive Accuracy -->
            <div class="bg-white p-6 rounded-xl card-shadow metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Prediction Accuracy</p>
                        <p class="text-3xl font-bold text-purple-600">94.2%</p>
                        <p class="text-sm text-purple-500">ML confidence</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i data-lucide="trending-up" class="w-8 h-8 text-purple-600"></i>
                    </div>
                </div>
            </div>

            <!-- Revenue Impact -->
            <div class="bg-white p-6 rounded-xl card-shadow metric-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">AI Revenue Impact</p>
                        <p class="text-3xl font-bold text-orange-600">+$125K</p>
                        <p class="text-sm text-orange-500">This month</p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i data-lucide="dollar-sign" class="w-8 h-8 text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Real-time Customer Interactions -->
            <div class="bg-white p-6 rounded-xl card-shadow">
                <h3 class="text-xl font-bold mb-4 flex items-center">
                    <i data-lucide="activity" class="w-6 h-6 mr-2 text-blue-600"></i>
                    Real-time Customer Interactions
                </h3>
                <canvas id="interactionsChart" height="300"></canvas>
            </div>

            <!-- Sentiment Analysis -->
            <div class="bg-white p-6 rounded-xl card-shadow">
                <h3 class="text-xl font-bold mb-4 flex items-center">
                    <i data-lucide="heart" class="w-6 h-6 mr-2 text-red-600"></i>
                    Customer Sentiment Distribution
                </h3>
                <canvas id="sentimentChart" height="300"></canvas>
            </div>
        </div>

        <!-- AI Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Multi-Modal Processing -->
            <div class="bg-white p-6 rounded-xl card-shadow">
                <div class="flex items-center mb-4">
                    <i data-lucide="image" class="w-8 h-8 text-purple-600 mr-3"></i>
                    <h3 class="text-lg font-bold">Multi-Modal AI</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Images Processed</span>
                        <span class="font-bold text-purple-600">1,247</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Voice Messages</span>
                        <span class="font-bold text-purple-600">892</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Documents</span>
                        <span class="font-bold text-purple-600">156</span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-purple-50 rounded-lg">
                    <p class="text-sm text-purple-700">🔥 Visual search accuracy: 96.8%</p>
                </div>
            </div>

            <!-- Predictive Analytics -->
            <div class="bg-white p-6 rounded-xl card-shadow">
                <div class="flex items-center mb-4">
                    <i data-lucide="crystal-ball" class="w-8 h-8 text-green-600 mr-3"></i>
                    <h3 class="text-lg font-bold">Predictive Engine</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">CLV Predictions</span>
                        <span class="font-bold text-green-600">94.5% ACC</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Churn Risk</span>
                        <span class="font-bold text-red-600">47 At Risk</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Next Purchase</span>
                        <span class="font-bold text-blue-600">312 Soon</span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-green-50 rounded-lg">
                    <p class="text-sm text-green-700">🎯 Prevented 23 churns this week</p>
                </div>
            </div>

            <!-- Automation Status -->
            <div class="bg-white p-6 rounded-xl card-shadow">
                <div class="flex items-center mb-4">
                    <i data-lucide="zap" class="w-8 h-8 text-yellow-600 mr-3"></i>
                    <h3 class="text-lg font-bold">Automation Engine</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active Workflows</span>
                        <span class="font-bold text-yellow-600">127</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Triggers Today</span>
                        <span class="font-bold text-yellow-600">1,834</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Success Rate</span>
                        <span class="font-bold text-green-600">98.7%</span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-yellow-50 rounded-lg">
                    <p class="text-sm text-yellow-700">⚡ Saved 47 hours today</p>
                </div>
            </div>
        </div>

        <!-- Customer Intelligence Table -->
        <div class="bg-white rounded-xl card-shadow p-6">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <i data-lucide="user-check" class="w-6 h-6 mr-2 text-indigo-600"></i>
                High-Value Customer Intelligence
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Predicted CLV</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Churn Risk</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Purchase</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AI Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-purple-600 font-bold text-sm">VIP</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">Sarah Johnson</div>
                                        <div class="text-sm text-gray-500">+1234567890</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">VIP</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-green-600">$12,450</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Low (5%)</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">3-5 days</td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Premium Offer Sent</span>
                            </td>
                        </tr>
                        <!-- More rows... -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Real-time interactions chart
        const ctx1 = document.getElementById('interactionsChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                datasets: [{
                    label: 'Customer Interactions',
                    data: [45, 67, 123, 234, 189, 156],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Sentiment analysis chart
        const ctx2 = document.getElementById('sentimentChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Positive', 'Neutral', 'Negative'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#10B981', '#6B7280', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Auto-refresh data every 30 seconds
        setInterval(() => {
            // In a real implementation, this would fetch fresh data
            console.log('Refreshing dashboard data...');
        }, 30000);
    </script>
</body>
</html>
