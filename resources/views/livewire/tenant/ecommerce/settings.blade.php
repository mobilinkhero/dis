<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('E-commerce Settings') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configure your store settings and automation
                </p>
            </div>
            
            <div class="flex gap-3">
                <button wire:click="resetToDefaults" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                    Reset to Defaults
                </button>
                <button wire:click="saveSettings" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Save Settings
                </button>
            </div>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Basic Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Store Settings</h3>
            
            <div class="space-y-4">
                <!-- Currency -->
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Currency
                    </label>
                    <select wire:model="settings.currency" 
                            id="currency"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        @foreach($availableCurrencies as $code => $name)
                            <option value="{{ $code }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('settings.currency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Tax Rate -->
                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tax Rate (%)
                    </label>
                    <input type="number" 
                           wire:model="settings.tax_rate"
                           id="tax_rate"
                           step="0.01"
                           min="0"
                           max="100"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                           placeholder="0.00">
                    @error('settings.tax_rate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Payment Methods -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Payment Methods
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($availablePaymentMethods as $method => $label)
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" 
                                       wire:model="settings.payment_methods"
                                       value="{{ $method }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('settings.payment_methods') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- AI & Automation Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">AI & Automation</h3>
            
            <div class="space-y-4">
                <!-- AI Recommendations -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">AI Product Recommendations</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Suggest related products to customers</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="settings.ai_recommendations_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Upselling Settings -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Smart Upselling</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Suggest additional products during checkout</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="settings.upselling_settings.enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    
                    @if($settings['upselling_settings']['enabled'])
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minimum Order Value ($)</label>
                                <input type="number" 
                                       wire:model="settings.upselling_settings.threshold_amount"
                                       step="0.01"
                                       min="0"
                                       class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Abandoned Cart Settings -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Abandoned Cart Recovery</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Automatically remind customers about their cart</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="settings.abandoned_cart_settings.enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    
                    @if($settings['abandoned_cart_settings']['enabled'])
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Delay (hours)</label>
                                <input type="number" 
                                       wire:model="settings.abandoned_cart_settings.delay_hours"
                                       min="1"
                                       max="168"
                                       class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Reminder Message</label>
                                <textarea wire:model="settings.abandoned_cart_settings.message"
                                          rows="2"
                                          class="w-full px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded focus:ring-1 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                          placeholder="Enter reminder message..."></textarea>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Message Templates -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Message Templates</h3>
            
            <div class="space-y-4">
                <!-- Order Confirmation Message -->
                <div>
                    <label for="order_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Order Confirmation Message
                    </label>
                    <textarea wire:model="settings.order_confirmation_message"
                              id="order_message"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                              placeholder="Message sent when order is confirmed..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Available variables: {order_number}, {total_amount}, {customer_name}</p>
                    @error('settings.order_confirmation_message') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Payment Confirmation Message -->
                <div>
                    <label for="payment_message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Payment Confirmation Message
                    </label>
                    <textarea wire:model="settings.payment_confirmation_message"
                              id="payment_message"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                              placeholder="Message sent when payment is received..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Available variables: {order_number}, {payment_amount}, {customer_name}</p>
                    @error('settings.payment_confirmation_message') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- Shipping Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Shipping Settings</h3>
            
            <div class="space-y-4">
                <!-- Enable Shipping -->
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable Shipping Charges</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Add shipping costs to orders</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="settings.shipping_settings.enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                @if($settings['shipping_settings']['enabled'])
                    <div class="space-y-3 pl-4 border-l-2 border-blue-200 dark:border-blue-800">
                        <!-- Default Shipping Cost -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Default Shipping Cost ($)
                            </label>
                            <input type="number" 
                                   wire:model="settings.shipping_settings.default_shipping_cost"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        </div>

                        <!-- Free Shipping Threshold -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Free Shipping Threshold ($)
                            </label>
                            <input type="number" 
                                   wire:model="settings.shipping_settings.free_shipping_threshold"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="0 = No free shipping">
                            <p class="text-xs text-gray-500 mt-1">Orders above this amount get free shipping</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
        
        <div class="flex gap-3 flex-wrap mb-4">
            <button wire:click="syncSheets" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                🔧 Check & Create Sheets
            </button>
            
            <button wire:click="syncWithGoogleSheets" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                🔄 Sync Data with Sheets
            </button>
            
            <a href="{{ tenant_route('tenant.ecommerce.dashboard') }}" 
               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                📊 View Dashboard
            </a>
            
            <a href="{{ tenant_route('tenant.ecommerce.products') }}" 
               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                🛍️ Manage Products
            </a>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">📋 Sheet Sync Guide</h4>
            <div class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
                <p><strong>🔧 Check & Create Sheets:</strong> Automatically creates these sheets in your Google Sheets if they don't exist:</p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li><strong>Products</strong> - ID, Name, SKU, Description, Price, Category, Stock, etc.</li>
                    <li><strong>Orders</strong> - Order Number, Customer Info, Items, Payment Status, etc.</li>
                    <li><strong>Customers</strong> - Phone, Name, Email, Order History, etc.</li>
                </ul>
                <p><strong>🔄 Sync Data:</strong> Imports existing data from your Google Sheets into the system.</p>
                <p class="text-xs opacity-75">💡 Tip: Run "Check & Create Sheets" first to ensure your Google Sheet has the correct structure!</p>
            </div>
        </div>
    </div>

    <!-- Apps Script Modal -->
    @if($showScriptModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeScriptModal"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    📄 Google Apps Script - Create E-commerce Sheets
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Follow these steps to create the required sheets in your Google Spreadsheet
                                </p>
                            </div>
                            <button wire:click="closeScriptModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Instructions -->
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">📋 Step-by-Step Instructions:</h4>
                            <ol class="text-sm text-blue-800 dark:text-blue-300 space-y-2">
                                <li><strong>1.</strong> Open your Google Sheet: <a href="{{ $config->google_sheets_url ?? '#' }}" target="_blank" class="underline hover:no-underline">{{ $config->google_sheets_url ?? 'N/A' }}</a></li>
                                <li><strong>2.</strong> Click on <strong>Extensions</strong> → <strong>Apps Script</strong></li>
                                <li><strong>3.</strong> Delete any existing code in the editor</li>
                                <li><strong>4.</strong> Copy and paste the code below</li>
                                <li><strong>5.</strong> Click <strong>Save</strong> (💾 icon)</li>
                                <li><strong>6.</strong> Click <strong>Run</strong> (▶️ icon) to execute the script</li>
                                <li><strong>7.</strong> Grant permissions when prompted</li>
                                <li><strong>8.</strong> Check your Google Sheet - new tabs should be created!</li>
                            </ol>
                        </div>

                        <!-- Generated Apps Script Code -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Generated Apps Script Code:
                                </label>
                                <button onclick="navigator.clipboard.writeText(document.getElementById('appsScriptCode').value)" 
                                        class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                    📋 Copy Code
                                </button>
                            </div>
                            <textarea id="appsScriptCode" 
                                      readonly 
                                      class="w-full h-64 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-sm bg-gray-50 dark:bg-gray-700 dark:text-white"
                                      style="font-family: 'Courier New', monospace;">{{ $generatedScript }}</textarea>
                        </div>

                        <!-- What will be created -->
                        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                            <h4 class="text-sm font-semibold text-green-900 dark:text-green-200 mb-2">✅ What this script creates:</h4>
                            <div class="text-sm text-green-800 dark:text-green-300 space-y-1">
                                <div><strong>📦 Products Sheet:</strong> ID, Name, SKU, Description, Price, Category, Stock, etc. (13 columns)</div>
                                <div><strong>📋 Orders Sheet:</strong> Order Number, Customer Info, Items, Payment Status, etc. (16 columns)</div>
                                <div><strong>👥 Customers Sheet:</strong> Phone, Name, Email, Order History, etc. (9 columns)</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closeScriptModal" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Got it!
                        </button>
                        <a href="{{ $config->google_sheets_url ?? '#' }}" 
                           target="_blank"
                           class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-gray-300 dark:border-gray-500 dark:hover:bg-gray-700">
                            🔗 Open Google Sheet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
