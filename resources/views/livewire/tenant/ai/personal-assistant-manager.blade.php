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

    <!-- AI Assistants Grid -->
    @if(!$showCreateForm)
    <div class="mt-6">
        @if($assistants && $assistants->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($assistants as $assistant)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                <!-- Card Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <x-heroicon-s-cpu-chip class="w-6 h-6 text-white" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                {{ $assistant->name }}
                            </h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $assistant->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $assistant->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Toggle Switch -->
                    <button 
                        wire:click="toggleAssistant({{ $assistant->id }})"
                        type="button"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $assistant->is_active ? 'bg-blue-600' : 'bg-gray-200' }}"
                    >
                        <span class="sr-only">{{ $assistant->is_active ? 'Deactivate' : 'Activate' }} assistant</span>
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $assistant->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                    </button>
                </div>

                <!-- Description -->
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">
                    {{ $assistant->description ?: 'AI assistant designed to help with various tasks and answer questions.' }}
                </p>

                <!-- Stats -->
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <div class="flex items-center space-x-4">
                        <span>{{ $availableModels[$assistant->model] ?? $assistant->model }}</span>
                        @if($assistant->hasUploadedFiles())
                        <span>{{ $assistant->getFileCount() }} files</span>
                        @endif
                    </div>
                </div>

                <!-- Use Case Tags -->
                @if($assistant->use_case_tags && count($assistant->use_case_tags) > 0)
                <div class="flex flex-wrap gap-1 mb-4">
                    @foreach(array_slice($assistant->getUseCaseBadges(), 0, 2) as $badge)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $badge }}
                    </span>
                    @endforeach
                    @if(count($assistant->getUseCaseBadges()) > 2)
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600">
                        +{{ count($assistant->getUseCaseBadges()) - 2 }}
                    </span>
                    @endif
                </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <button wire:click="editSpecificAssistant({{ $assistant->id }})" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Edit
                        </button>
                        <button wire:click="deleteSpecificAssistant({{ $assistant->id }})" wire:confirm="Delete '{{ $assistant->name }}' assistant?" class="text-sm text-red-600 hover:text-red-800 font-medium">
                            Delete
                        </button>
                    </div>
                    <div class="text-xs text-gray-500">
                        Created {{ $assistant->created_at->diffForHumans() }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <x-heroicon-o-cpu-chip class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No AI Assistants</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Get started by creating your first AI assistant.
            </p>
        </div>
        @endif
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
