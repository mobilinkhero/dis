<!-- resources/views/livewire/tenant/ai/personal-assistant-manager.blade.php -->
<div>
    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">AI Assistant</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Create your personal AI assistant to help with FAQs, product enquiries, onboarding, and more.
            </p>
        </div>
        
        @if(!$assistant)
        <div class="mt-4 sm:mt-0">
            <button wire:click="createAssistant" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <x-heroicon-m-plus class="-ml-1 mr-2 h-5 w-5" />
                Create New Assistant
            </button>
        </div>
        @endif
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

    <!-- Assistant Details -->
    @if($assistant && !$showCreateForm)
    <div class="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex justify-between items-start">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    {{ $assistant->name }}
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                    {{ $assistant->description ?: 'No description provided' }}
                </p>
                
                <!-- Use Case Tags -->
                @if($assistant->use_case_tags)
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($assistant->getUseCaseBadges() as $badge)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                        {{ $badge }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>
            
            <div class="flex space-x-2">
                <button wire:click="editAssistant" type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    <x-heroicon-m-pencil class="-ml-1 mr-1 h-4 w-4" />
                    Edit
                </button>
                <button wire:click="deleteAssistant" wire:confirm="Are you sure you want to delete this assistant and all its files?" type="button" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 dark:bg-gray-700 dark:text-red-400 dark:border-red-600 dark:hover:bg-red-900">
                    <x-heroicon-m-trash class="-ml-1 mr-1 h-4 w-4" />
                    Delete
                </button>
            </div>
        </div>
        
        <div class="border-t border-gray-200 dark:border-gray-700">
            <dl>
                <!-- Model & Settings -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">AI Model</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                        {{ $availableModels[$assistant->model] ?? $assistant->model }}
                        <span class="ml-2 text-xs text-gray-500">
                            (Temperature: {{ $assistant->temperature }}, Max tokens: {{ number_format($assistant->max_tokens) }})
                        </span>
                    </dd>
                </div>

                <!-- System Instructions -->
                <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">System Instructions</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                        <div class="max-h-32 overflow-y-auto">
                            <pre class="whitespace-pre-wrap text-xs bg-gray-50 dark:bg-gray-700 p-3 rounded">{{ $assistant->system_instructions }}</pre>
                        </div>
                    </dd>
                </div>

                <!-- Uploaded Files -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Knowledge Base</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                        @if($assistant->hasUploadedFiles())
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $assistant->getFileCount() }} files, {{ number_format($assistant->getContentSize()) }} characters processed
                                </span>
                                <button wire:click="clearAllFiles" wire:confirm="Clear all uploaded files?" class="text-red-600 hover:text-red-800 text-xs">
                                    Clear all files
                                </button>
                            </div>
                            
                            @foreach($assistant->getFilesWithStatus() as $file)
                            <div class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded border">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        @if($file['type'] === 'csv')
                                        <x-heroicon-o-table-cells class="h-5 w-5 text-green-500" />
                                        @elseif(in_array($file['type'], ['txt', 'md']))
                                        <x-heroicon-o-document-text class="h-5 w-5 text-blue-500" />
                                        @elseif($file['type'] === 'json')
                                        <x-heroicon-o-code-bracket class="h-5 w-5 text-purple-500" />
                                        @else
                                        <x-heroicon-o-document class="h-5 w-5 text-gray-500" />
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $file['original_name'] }}</p>
                                        <p class="text-xs text-gray-500">
                                            @if(isset($file['size']) && $file['size'] > 0)
                                                {{ number_format($file['size']) }} bytes
                                            @else
                                                0 bytes
                                            @endif
                                            • {{ strtoupper($file['type']) }}
                                            @if(isset($file['exists']) && !$file['exists'])
                                            <span class="text-red-500">• File missing</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <button wire:click="removeFile('{{ $file['original_name'] }}')" class="text-red-600 hover:text-red-800">
                                    <x-heroicon-m-trash class="h-4 w-4" />
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-gray-500 italic">No files uploaded yet</p>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
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
