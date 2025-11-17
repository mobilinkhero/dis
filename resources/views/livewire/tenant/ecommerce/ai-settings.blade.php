<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        🤖 AI Assistant Settings
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Configure ChatGPT integration for your e-commerce bot</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ tenant_route('tenant.ecommerce.settings') }}" 
                       class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition border">
                        ← Back to Settings
                    </a>
                    <button wire:click="resetToDefaults" 
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                        Reset Defaults
                    </button>
                    <button wire:click="save" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        💾 Save Settings
                    </button>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save" class="space-y-6 p-6">
            
            {{-- AI Enable Toggle --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-lg font-semibold text-gray-900 dark:text-white">Enable AI Assistant</label>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Turn on AI-powered e-commerce assistance</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="ai_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            @if($ai_enabled)
            {{-- API Configuration --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Provider Selection --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">AI Provider</label>
                    <select wire:model.live="ai_provider" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="openai">🤖 OpenAI (ChatGPT)</option>
                        <option value="claude">🧠 Anthropic (Claude)</option>
                        <option value="gemini">💎 Google (Gemini)</option>
                    </select>
                </div>

                {{-- Model Selection --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">AI Model</label>
                    <select wire:model.live="ai_model" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @foreach($availableModels[$ai_provider] ?? [] as $model)
                        <option value="{{ $model['id'] }}">{{ $model['name'] }}</option>
                        @endforeach
                    </select>
                    @if(isset($availableModels[$ai_provider]))
                        @foreach($availableModels[$ai_provider] as $model)
                            @if($model['id'] === $ai_model)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $model['description'] }}</p>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- API Key --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                <div class="flex space-x-3">
                    <input type="password" wire:model="ai_api_key" 
                           placeholder="sk-..." 
                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <button type="button" wire:click="testApiKey" 
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="testApiKey">🧪 Test</span>
                        <span wire:loading wire:target="testApiKey">Testing...</span>
                    </button>
                </div>
                @if($testResult === 'success')
                    <p class="text-sm text-green-600 mt-1">✅ API key is working!</p>
                @elseif($testResult === 'error')
                    <p class="text-sm text-red-600 mt-1">❌ API key test failed</p>
                @endif
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Get your API key from: 
                    <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-500 hover:text-blue-600">OpenAI Platform</a>
                </p>
            </div>

            {{-- AI Parameters --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Temperature ({{ $ai_temperature }})</label>
                    <input type="range" wire:model.live="ai_temperature" min="0" max="1" step="0.1" 
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                        <span>Focused</span>
                        <span>Creative</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Max Tokens</label>
                    <input type="number" wire:model="ai_max_tokens" min="100" max="4000" 
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Response length limit</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Timeout (seconds)</label>
                    <input type="number" wire:model="ai_response_timeout" min="5" max="120" 
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">API response timeout</p>
                </div>
            </div>

            {{-- System Prompt --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">AI System Prompt</label>
                <textarea wire:model="ai_system_prompt" rows="8" 
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                          placeholder="Instructions for how the AI should behave..."></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Define how your AI assistant should behave and respond to customers</p>
            </div>

            {{-- AI Features --}}
            <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">AI Features</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" wire:model="ai_product_recommendations" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Product Recommendations</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">AI suggests relevant products</p>
                        </div>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" wire:model="ai_order_processing" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Order Processing</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">AI handles order creation and tracking</p>
                        </div>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" wire:model="ai_customer_support" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Customer Support</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">AI provides customer assistance</p>
                        </div>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" wire:model="ai_inventory_alerts" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Inventory Alerts</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">AI monitors stock levels</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Fallback Settings --}}
            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Fallback Settings</h3>
                
                <label class="flex items-center space-x-3 cursor-pointer mb-4">
                    <input type="checkbox" wire:model="ai_fallback_to_manual" 
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Enable Fallback Message</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Show message when AI is unavailable</p>
                    </div>
                </label>

                @if($ai_fallback_to_manual)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fallback Message</label>
                    <textarea wire:model="ai_fallback_message" rows="3" 
                              class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                              placeholder="Message to show when AI is unavailable..."></textarea>
                </div>
                @endif
            </div>
            @endif

            {{-- Usage Statistics (if available) --}}
            @if($ai_enabled)
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">📊 AI Usage Stats</h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Total Requests:</span>
                        <span class="font-semibold text-gray-900 dark:text-white ml-2">Coming Soon</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Success Rate:</span>
                        <span class="font-semibold text-green-600 ml-2">Coming Soon</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Last Used:</span>
                        <span class="font-semibold text-gray-900 dark:text-white ml-2">Coming Soon</span>
                    </div>
                </div>
            </div>
            @endif

        </form>
    </div>
</div>
