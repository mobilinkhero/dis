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
        
        <div class="flex gap-4">
            <button wire:click="syncWithGoogleSheets" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                🔄 Sync with Google Sheets
            </button>
            
            <a href="{{ tenant_route('tenant.ecommerce.dashboard') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                📊 View Dashboard
            </a>
            
            <a href="{{ tenant_route('tenant.ecommerce.products') }}" 
               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                🛍️ Manage Products
            </a>
        </div>
    </div>
</div>
