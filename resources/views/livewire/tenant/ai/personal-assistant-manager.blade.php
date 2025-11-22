<!-- resources/views/livewire/tenant/ai/personal-assistant-manager.blade.php -->
<div>
    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">AI Assistant</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Manage your personal AI assistant to help with FAQs, product enquiries, onboarding, and more.
            </p>
        </div>
        
        <div class="mt-4 sm:mt-0">
            @if(!$assistant)
            <button wire:click="createAssistant" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <x-heroicon-m-plus class="-ml-1 mr-2 h-5 w-5" />
                Create New Assistant
            </button>
            @else
            <button wire:click="createAssistant" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <x-heroicon-m-plus class="-ml-1 mr-2 h-5 w-5" />
                Create New Assistant
            </button>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
    <div class="mt-4 bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md">
        {{ session('success') }}
    </div>
    @endif

    @if (session()->has('error'))
    <div class="mt-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
        {{ session('error') }}
    </div>
    @endif

    @if (session()->has('file-upload-success'))
    <div class="mt-4 bg-blue-50 border border-blue-200 text-blue-600 px-4 py-3 rounded-md">
        {{ session('file-upload-success') }}
    </div>
    @endif

    <!-- No Assistant State -->
    @if(!$assistant && !$showCreateForm)
    <div class="mt-8 text-center py-12">
        <x-heroicon-o-cpu-chip class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Personal Assistant Yet</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Create your first AI assistant to help with document analysis, customer support, and more.
        </p>
        <div class="mt-6">
            <button wire:click="createAssistant" type="button" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                <x-heroicon-m-plus class="-ml-1 mr-2 h-5 w-5" />
                Create Assistant
            </button>
        </div>
    </div>
    @endif

    <!-- AI Assistant Card -->
    @if(!$showCreateForm && $assistants && $assistants->count() > 0)
    @foreach($assistants as $assistant)
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 max-w-md">
        <!-- Header with Icon and Title -->
        <div class="flex items-start space-x-3 mb-4">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <x-heroicon-s-sparkles class="w-6 h-6 text-purple-600" />
                </div>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $assistant->name }}</h3>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="text-xs font-medium text-green-600">Active</span>
                            <span class="text-xs text-gray-500">{{ $availableModels[$assistant->model] ?? 'gpt-4o-mini' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500">Active</span>
                        <button 
                            wire:click="toggleAssistant({{ $assistant->id }})"
                            type="button"
                            class="relative inline-flex h-6 w-11 items-center rounded-full bg-purple-600 transition-colors"
                        >
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white translate-x-6"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description -->
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            {{ $assistant->description ?: 'An intelligent virtual assistant designed to streamline your workflows, provide real-time insights,...' }}
        </p>

        <!-- Document Count -->
        <div class="flex items-center space-x-2 mb-6">
            <x-heroicon-o-document class="w-4 h-4 text-gray-400" />
            <span class="text-sm text-gray-600">{{ $assistant->hasUploadedFiles() ? $assistant->getFileCount() : 1 }} document</span>
        </div>

        <!-- Expandable Sections -->
        <div class="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
            <!-- OpenAI Integration -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <x-heroicon-s-cube class="w-5 h-5 text-blue-600" />
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">OpenAI Integration</h4>
                            <p class="text-xs text-gray-500">AI Assistant Status & Sync Information</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Items -->
            <div class="space-y-3">
                <!-- Sync Status -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Sync Status</span>
                    </div>
                    <span class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-1 rounded">0% Synced</span>
                </div>

                <!-- AI Assistant -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">AI Assistant</span>
                    </div>
                    <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded">Created</span>
                </div>

                <!-- Knowledge Base -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Knowledge Base</span>
                    </div>
                    <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded">Pending</span>
                </div>
            </div>

            <!-- Documents Status -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Documents Status</span>
                    <span class="text-xs text-gray-500">1 total</span>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded px-3 py-2">
                    <span class="text-xs font-medium text-yellow-700 dark:text-yellow-400">1 Pending</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-3 mt-6">
            <button class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center justify-center">
                <x-heroicon-s-chat-bubble-left-right class="w-4 h-4 mr-2" />
                Chat
            </button>
            <button class="flex-1 bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
                Sync Now
            </button>
            <button class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <x-heroicon-s-cog-6-tooth class="w-5 h-5 text-gray-600 dark:text-gray-400" />
            </button>
            <button wire:click="deleteSpecificAssistant({{ $assistant->id }})" wire:confirm="Delete assistant?" class="p-2 bg-red-100 dark:bg-red-900/20 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/40 transition-colors">
                <x-heroicon-s-trash class="w-5 h-5 text-red-600 dark:text-red-400" />
            </button>
        </div>
    </div>
    @endforeach
    @elseif(!$showCreateForm)
    <div class="text-center py-12">
        <x-heroicon-o-cpu-chip class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No AI Assistants</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Get started by creating your first AI assistant.
        </p>
    </div>
    @endif

    <!-- Create/Edit Form -->
    @if($showCreateForm)
    <div class="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                {{ $assistant ? 'Edit Assistant' : 'New Personal Assistant' }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                Configure your AI assistant for document analysis, customer support, and automation.
            </p>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:px-6">
            <form wire:submit.prevent="saveAssistant" class="space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assistant Name *</label>
                        <input wire:model="name" type="text" id="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="e.g., SmartFlow AI">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300">AI Model *</label>
                        <select wire:model="model" id="model" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            @foreach($availableModels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea wire:model="description" id="description" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Brief description of what this assistant helps with"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Use Cases -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Use Cases</label>
                    <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach($useCaseOptions as $value => $label)
                        <label class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input wire:model="use_case_tags" type="checkbox" value="{{ $value }}" class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                            </div>
                            <div class="ml-3 text-sm">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('use_case_tags') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Advanced Settings -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="temperature" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Temperature (Creativity): {{ $temperature }}
                        </label>
                        <input wire:model.live="temperature" type="range" id="temperature" min="0" max="2" step="0.1" class="mt-1 block w-full">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Focused (0)</span>
                            <span>Balanced (1)</span>
                            <span>Creative (2)</span>
                        </div>
                        @error('temperature') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="max_tokens" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Response Length</label>
                        <select wire:model="max_tokens" id="max_tokens" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                            <option value="500">Short (500 tokens)</option>
                            <option value="1000">Medium (1000 tokens)</option>
                            <option value="2000">Long (2000 tokens)</option>
                            <option value="4000">Very Long (4000 tokens)</option>
                        </select>
                        @error('max_tokens') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- System Instructions -->
                <div>
                    <label for="system_instructions" class="block text-sm font-medium text-gray-700 dark:text-gray-300">System Instructions *</label>
                    <textarea wire:model="system_instructions" id="system_instructions" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Define how the assistant should behave, its role, and guidelines..."></textarea>
                    <p class="mt-1 text-xs text-gray-500">These instructions guide how your AI assistant responds to queries.</p>
                    @error('system_instructions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- File Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Files for AI Analysis</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md dark:border-gray-600">
                        <div class="space-y-1 text-center">
                            <x-heroicon-o-cloud-arrow-up class="mx-auto h-12 w-12 text-gray-400" />
                            <div class="flex text-sm text-gray-600">
                                <label for="file-upload" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                    <span>Upload files</span>
                                    <input wire:model="files" id="file-upload" name="file-upload" type="file" class="sr-only" multiple accept=".txt,.md,.csv,.json">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">
                                TXT, MD, CSV, JSON up to 5MB each
                            </p>
                        </div>
                    </div>
                    
                    @if(count($files) > 0)
                    <div class="mt-2 space-y-1">
                        @foreach($files as $file)
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            📁 {{ $file->getClientOriginalName() }} ({{ number_format($file->getSize()) }} bytes)
                        </div>
                        @endforeach
                    </div>
                    @endif
                    
                    @error('files') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('files.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Guidelines -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                    <div class="flex">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-400" />
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Guidelines for Best Results</h3>
                            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Define the assistant's role and expertise area</li>
                                    <li>Specify the tone and communication style</li>
                                    <li>Include any specific guidelines or limitations</li>
                                    <li>Upload relevant documents for context and knowledge</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3">
                    <button wire:click="cancelForm" type="button" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        {{ $assistant ? 'Update Assistant' : 'Create Assistant' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
