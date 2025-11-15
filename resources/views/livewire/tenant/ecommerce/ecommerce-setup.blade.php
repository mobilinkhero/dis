<div class="space-y-6">
    {{-- Header Section --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🛒 E-commerce Bot Setup</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Set up your WhatsApp e-commerce automation with Google Sheets integration</p>
            </div>
            
            @if($ecommerceBot && $ecommerceBot->isReady())
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                            <circle cx="4" cy="4" r="3"/>
                        </svg>
                        Active
                    </span>
                    <span class="text-sm text-gray-500">E-commerce bot is running</span>
                </div>
            @endif
        </div>

        {{-- Progress Steps --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                @php
                    $steps = [
                        'setup' => 'Setup',
                        'validate' => 'Validate', 
                        'configure' => 'Configure',
                        'complete' => 'Complete'
                    ];
                    $currentIndex = array_search($currentStep, array_keys($steps));
                @endphp

                @foreach($steps as $stepKey => $stepLabel)
                    @php
                        $stepIndex = array_search($stepKey, array_keys($steps));
                        $isActive = $currentStep === $stepKey;
                        $isCompleted = $stepIndex < $currentIndex;
                    @endphp
                    
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $isActive ? 'bg-blue-600 text-white' : ($isCompleted ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600') }}">
                            @if($isCompleted)
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                {{ $stepIndex + 1 }}
                            @endif
                        </div>
                        <span class="ml-2 text-sm font-medium {{ $isActive ? 'text-blue-600' : 'text-gray-500' }}">{{ $stepLabel }}</span>
                        
                        @if(!$loop->last)
                            <div class="ml-4 w-8 border-t border-gray-300"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Step 1: Initial Setup --}}
    @if($currentStep === 'setup')
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Connect Google Sheets</h2>
            

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Setup Form --}}
                <div class="space-y-6">
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model.live="isEnabled" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable E-commerce Bot</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($isEnabled)
                                ✅ E-commerce bot is enabled - configure your sheets below
                            @else
                                ℹ️ Check this box to enable e-commerce automation
                            @endif
                        </p>
                    </div>

                    @if($isEnabled)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                🛍️ Product Sheet URL <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="url" 
                                wire:model="productSheetsUrl"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                placeholder="https://docs.google.com/spreadsheets/d/your-sheet-id/edit#gid=0"
                            >
                            @error('productSheetsUrl')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                <strong>Required:</strong> Your Google Sheet with product information
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                📋 Order Sheet URL <span class="text-gray-400">(Optional)</span>
                            </label>
                            <input 
                                type="url" 
                                wire:model="orderSheetsUrl"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                placeholder="https://docs.google.com/spreadsheets/d/your-order-sheet-id/edit#gid=0"
                            >
                            @error('orderSheetsUrl')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                Orders will be automatically added to this sheet
                            </p>
                        </div>

                        <div class="flex space-x-3">
                            <button 
                                type="button"
                                wire:click="validateGoogleSheets"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                                wire:loading.attr="disabled"
                            >
                                <div wire:loading.remove wire:target="validateGoogleSheets">
                                    🔍 Validate Sheets
                                </div>
                                <div wire:loading wire:target="validateGoogleSheets">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Validating...
                                </div>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Instructions & Sample Data --}}
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">📋 Setup Instructions</h3>
                    
                    <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Step 1: Create Google Sheets</h4>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Create a new Google Sheet for products</li>
                                <li>Optionally create another sheet for orders</li>
                                <li>Add headers as shown in the sample structure below</li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Step 2: Make Sheets Public</h4>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Click "Share" button in your Google Sheet</li>
                                <li>Change to "Anyone with the link can view"</li>
                                <li>Copy the sharing link</li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 dark:text-white">Step 3: Paste Links Above</h4>
                            <p class="text-xs">Then click "Validate Sheets" to continue</p>
                        </div>
                    </div>

                    {{-- Sample Sheet Structure --}}
                    <div class="mt-6">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">💡 Sample Product Sheet Structure:</h4>
                        <div class="bg-white dark:bg-gray-800 rounded border overflow-hidden">
                            <table class="min-w-full text-xs">
                                <thead class="bg-gray-100 dark:bg-gray-600">
                                    <tr>
                                        @foreach($sampleSheets['products']['headers'] as $header)
                                            <th class="px-2 py-1 text-left font-medium text-gray-700 dark:text-gray-300">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_slice($sampleSheets['products']['sample_data'], 0, 1) as $row)
                                        <tr class="border-t dark:border-gray-600">
                                            @foreach($row as $cell)
                                                <td class="px-2 py-1 text-gray-600 dark:text-gray-400">{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Step 2: Validation Results --}}
    @if($currentStep === 'validate' || !empty($validationResults))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">✅ Validation Results</h2>
            
            <div class="space-y-4">
                @foreach($validationResults as $type => $result)
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-gray-900 dark:text-white capitalize">
                                {{ $type === 'products' ? '🛍️ Products Sheet' : '📋 Orders Sheet' }}
                            </h3>
                            @if($result['success'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✅ Valid
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ❌ Invalid
                                </span>
                            @endif
                        </div>

                        <p class="text-sm {{ $result['success'] ? 'text-green-600' : 'text-red-600' }}">
                            {{ $result['message'] }}
                        </p>

                        @if($result['success'] && isset($result['headers']))
                            <div class="mt-3">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Found {{ count($result['headers']) }} columns, {{ $result['row_count'] ?? 0 }} rows
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($result['headers'] as $header)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $header }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if(collect($validationResults)->every(fn($result) => $result['success']))
                <div class="mt-6 flex justify-between">
                    <button 
                        type="button"
                        wire:click="resetSetup"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        ← Back to Setup
                    </button>
                    
                    <button 
                        type="button"
                        wire:click="goToStep('configure')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        Continue to Configuration →
                    </button>
                </div>
            @else
                <div class="mt-6">
                    <button 
                        type="button"
                        wire:click="resetSetup"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        ← Back to Setup
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Step 3: Configuration --}}
    @if($currentStep === 'configure')
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚙️ Configuration</h2>
            
            <div class="space-y-6">
                {{-- Auto Sync Settings --}}
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h3 class="font-medium text-gray-900 dark:text-white mb-3">🔄 Sync Settings</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    wire:model="syncSettings.auto_sync_enabled"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                >
                                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Enable Auto Sync</span>
                            </label>
                        </div>

                        @if($syncSettings['auto_sync_enabled'] ?? false)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Sync Interval (minutes)
                                </label>
                                <select 
                                    wire:model="syncSettings.sync_interval_minutes"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="15">Every 15 minutes</option>
                                    <option value="30">Every 30 minutes</option>
                                    <option value="60">Every hour</option>
                                    <option value="180">Every 3 hours</option>
                                    <option value="720">Every 12 hours</option>
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Column Mapping (Advanced) --}}
                <div>
                    <button 
                        type="button"
                        wire:click="toggleAdvancedSettings"
                        class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-500"
                    >
                        @if($showAdvancedSettings)
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            Hide Advanced Settings
                        @else
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Show Advanced Settings
                        @endif
                    </button>

                    @if($showAdvancedSettings)
                        <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-3">🎯 Column Mapping</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Map your Google Sheets columns to product fields. Default mappings should work for most cases.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($syncSettings['product_columns'] as $field => $column)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 capitalize">
                                            {{ str_replace('_', ' ', $field) }}
                                            @if(in_array($field, ['name', 'price']))
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </label>
                                        <input 
                                            type="text" 
                                            wire:model="syncSettings.product_columns.{{ $field }}"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                            placeholder="Column name in your sheet"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-between pt-4">
                    <button 
                        type="button"
                        wire:click="goToStep('validate')"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        ← Back to Validation
                    </button>
                    
                    <button 
                        type="button"
                        wire:click="saveConfiguration"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        wire:loading.attr="disabled"
                    >
                        <div wire:loading.remove wire:target="saveConfiguration">
                            💾 Save Configuration
                        </div>
                        <div wire:loading wire:target="saveConfiguration">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </div>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Step 4: Complete --}}
    @if($currentStep === 'complete')
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">🎉 Setup Complete!</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Your e-commerce bot is now configured and ready to use. You can start syncing products and taking orders via WhatsApp.
                </p>

                <div class="space-y-4 max-w-md mx-auto">
                    <button 
                        type="button"
                        wire:click="performInitialSync"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md text-sm font-medium transition-colors"
                        wire:loading.attr="disabled"
                    >
                        <div wire:loading.remove wire:target="performInitialSync">
                            🔄 Sync Products Now
                        </div>
                        <div wire:loading wire:target="performInitialSync">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Syncing...
                        </div>
                    </button>

                    <div class="flex space-x-3">
                        <a 
                            href="{{ tenant_route('tenant.products.list') }}"
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center transition-colors"
                        >
                            📦 Manage Products
                        </a>
                        
                        <a 
                            href="{{ tenant_route('tenant.orders.list') }}"
                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center transition-colors"
                        >
                            📋 View Orders
                        </a>
                    </div>

                    <a 
                        href="{{ route('tenant.bot-flow_list') }}"
                        class="block w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium text-center transition-colors"
                    >
                        🔧 Create Sales Flows
                    </a>
                </div>

                {{-- Quick Stats --}}
                @if($ecommerceBot)
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $ecommerceBot->products()->count() }}
                            </div>
                            <div class="text-sm text-blue-800 dark:text-blue-300">Products Synced</div>
                        </div>
                        
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ $ecommerceBot->orders()->count() }}
                            </div>
                            <div class="text-sm text-green-800 dark:text-green-300">Orders Received</div>
                        </div>
                        
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                ₹{{ number_format($ecommerceBot->orders()->sum('total_amount'), 2) }}
                            </div>
                            <div class="text-sm text-purple-800 dark:text-purple-300">Total Sales</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
