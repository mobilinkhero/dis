<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                    🤖 AI Bot Chat Tester
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Test your e-commerce AI bot responses in real-time
                </p>
            </div>
            <div class="flex gap-2">
                <button 
                    wire:click="toggleDebug" 
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors"
                >
                    {{ $showDebugInfo ? '🔍 Hide Debug' : '🔍 Show Debug' }}
                </button>
                <button 
                    wire:click="clearChat" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
                >
                    🔄 Reset Chat
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chat Interface (Main Column) -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                <!-- Chat Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-2xl">
                            🤖
                        </div>
                        <div class="text-white">
                            <h3 class="font-semibold text-lg">AI Shopping Assistant</h3>
                            <p class="text-sm text-green-100">
                                {{ $config && $config->ai_powered_mode ? '● Online (AI Powered)' : '● Testing Mode' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages Container -->
                <div 
                    class="h-[500px] overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900 space-y-4"
                    id="chat-messages"
                    x-data
                    x-init="$el.scrollTop = $el.scrollHeight"
                    wire:poll.keep-alive
                >
                    @foreach($messages as $index => $msg)
                        @if($msg['role'] === 'bot')
                            <!-- Bot Message -->
                            <div class="flex items-start gap-3" wire:key="msg-{{ $index }}">
                                <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white text-lg flex-shrink-0">
                                    🤖
                                </div>
                                <div class="flex-1 max-w-[80%]">
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-none shadow-md p-4 border border-gray-200 dark:border-gray-700">
                                        <div class="prose dark:prose-invert max-w-none text-sm">
                                            {!! nl2br(e($msg['message'])) !!}
                                        </div>
                                        
                                        @if(!empty($msg['buttons']))
                                            <div class="mt-3 space-y-2">
                                                @foreach($msg['buttons'] as $button)
                                                    <button class="w-full px-4 py-2 bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg text-sm font-medium transition-colors border border-green-200 dark:border-green-800">
                                                        {{ $button['text'] ?? $button['title'] ?? 'Button' }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs text-gray-500">{{ $msg['time'] }}</span>
                                            @if($showDebugInfo && $msg['debug'])
                                                <button 
                                                    onclick="document.getElementById('debug-{{ $index }}').classList.toggle('hidden')"
                                                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    🔍 Debug
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    @if($showDebugInfo && $msg['debug'])
                                        <div id="debug-{{ $index }}" class="mt-2 p-3 bg-gray-800 text-gray-100 rounded-lg text-xs font-mono hidden">
                                            <div class="font-semibold mb-2 text-yellow-400">Debug Information:</div>
                                            <pre class="whitespace-pre-wrap">{{ json_encode($msg['debug'], JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <!-- User Message -->
                            <div class="flex items-start gap-3 justify-end" wire:key="msg-{{ $index }}">
                                <div class="flex-1 max-w-[80%] flex justify-end">
                                    <div class="bg-green-600 text-white rounded-2xl rounded-tr-none shadow-md p-4">
                                        <div class="text-sm">
                                            {{ $msg['message'] }}
                                        </div>
                                        <div class="text-xs text-green-100 mt-1 text-right">
                                            {{ $msg['time'] }}
                                        </div>
                                    </div>
                                </div>
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white text-lg flex-shrink-0">
                                    👤
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if($isTyping)
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white text-lg flex-shrink-0">
                                🤖
                            </div>
                            <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-none shadow-md p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex gap-1">
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s"></span>
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Chat Input -->
                <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4">
                    <form wire:submit.prevent="sendMessage" class="flex gap-2">
                        <input 
                            type="text" 
                            wire:model="currentMessage"
                            placeholder="Type your message..." 
                            class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 dark:text-gray-100"
                            wire:loading.attr="disabled"
                            autofocus
                        >
                        <button 
                            type="submit"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Send 📤</span>
                            <span wire:loading>
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar: Quick Tests & Info -->
        <div class="space-y-6">
            <!-- Configuration Status -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    ⚙️ Configuration Status
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">E-commerce Setup</span>
                        @if($config && $config->is_configured)
                            <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100 text-xs rounded-full">✅ Active</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100 text-xs rounded-full">❌ Not Setup</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">AI Mode</span>
                        @if($config && $config->ai_powered_mode)
                            <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100 text-xs rounded-full">✅ Enabled</span>
                        @else
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100 text-xs rounded-full">⚠️ Disabled</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">OpenAI Key</span>
                        @if($config && !empty($config->openai_api_key))
                            <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100 text-xs rounded-full">✅ Set</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100 text-xs rounded-full">❌ Missing</span>
                        @endif
                    </div>
                    @if($config)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">AI Model</span>
                            <span class="text-xs text-gray-700 dark:text-gray-300 font-mono">{{ $config->openai_model ?? 'N/A' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Test Scenarios -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                    ⚡ Quick Test Scenarios
                </h3>
                <div class="space-y-2">
                    @foreach($quickTests as $message => $label)
                        <button 
                            wire:click="sendQuickTest('{{ $message }}')"
                            class="w-full text-left px-4 py-3 bg-gray-50 hover:bg-gray-100 dark:bg-gray-900 dark:hover:bg-gray-700 rounded-lg transition-colors border border-gray-200 dark:border-gray-700 group"
                        >
                            <div class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $label }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300">{{ $message }}</div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Tips -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl shadow-lg p-6 border border-blue-200 dark:border-blue-800">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                    💡 Testing Tips
                </h3>
                <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 dark:text-blue-400">•</span>
                        <span>Try different types of customer requests</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 dark:text-blue-400">•</span>
                        <span>Test product searches and browsing</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 dark:text-blue-400">•</span>
                        <span>Check order placement flow</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 dark:text-blue-400">•</span>
                        <span>Enable debug mode to see AI actions</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 dark:text-blue-400">•</span>
                        <span>Test support and tracking queries</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Auto-scroll script -->
    <script>
        document.addEventListener('livewire:update', () => {
            const chatContainer = document.getElementById('chat-messages');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        });
    </script>
</div>
