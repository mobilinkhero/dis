<?php

namespace App\Livewire\Tenant\AI;

use App\Models\PersonalAssistant;
use App\Services\PersonalAssistantFileService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;

class PersonalAssistantManager extends Component
{
    use WithFileUploads;

    public $assistant;
    public $showCreateForm = false;
    public $files = [];
    
    // Form fields
    public $name = '';
    public $description = '';
    public $system_instructions = '';
    public $model = 'gpt-4o-mini';
    public $temperature = 0.7;
    public $max_tokens = 1000;
    public $use_case_tags = [];
    public $file_analysis_enabled = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:1000',
        'system_instructions' => 'required|string|max:5000',
        'model' => 'required|string|in:gpt-3.5-turbo,gpt-3.5-turbo-16k,gpt-4,gpt-4-turbo,gpt-4o-mini',
        'temperature' => 'required|numeric|between:0,2',
        'max_tokens' => 'required|integer|between:100,4000',
        'use_case_tags' => 'array|max:5',
        'use_case_tags.*' => 'string|in:faq,product,onboarding,csv,sop,general',
        'file_analysis_enabled' => 'boolean',
        'files.*' => 'file|max:5120|mimes:txt,md,csv,json,pdf,doc,docx', // 5MB max
    ];

    protected $messages = [
        'files.*.max' => 'Each file must be smaller than 5MB',
        'files.*.mimes' => 'Only text, markdown, CSV, JSON files are supported currently',
    ];

    public function mount()
    {
        $this->loadAssistant();
    }

    public function loadAssistant()
    {
        $this->assistant = PersonalAssistant::getForCurrentTenant();
        
        if ($this->assistant) {
            $this->name = $this->assistant->name;
            $this->description = $this->assistant->description;
            $this->system_instructions = $this->assistant->system_instructions;
            $this->model = $this->assistant->model;
            $this->temperature = $this->assistant->temperature;
            $this->max_tokens = $this->assistant->max_tokens;
            $this->use_case_tags = $this->assistant->use_case_tags ?? [];
            $this->file_analysis_enabled = $this->assistant->file_analysis_enabled;
        } else {
            $this->resetForm();
        }
    }

    public function createAssistant()
    {
        $this->resetForm();
        $this->showCreateForm = true;
        
        // Set default instructions based on use cases
        $this->system_instructions = $this->getDefaultInstructions();
    }

    public function editAssistant()
    {
        $this->showCreateForm = true;
    }

    public function cancelForm()
    {
        $this->showCreateForm = false;
        $this->files = [];
        $this->resetErrorBag();
        $this->loadAssistant();
    }

    public function saveAssistant()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $data = [
                'name' => $this->name,
                'description' => $this->description,
                'system_instructions' => $this->system_instructions,
                'model' => $this->model,
                'temperature' => $this->temperature,
                'max_tokens' => $this->max_tokens,
                'use_case_tags' => $this->use_case_tags,
                'file_analysis_enabled' => $this->file_analysis_enabled,
            ];

            // Create or update assistant
            $assistant = PersonalAssistant::createOrUpdateForTenant($data);

            // Process uploaded files if any
            if (!empty($this->files)) {
                $fileService = new PersonalAssistantFileService();
                $result = $fileService->uploadFiles($assistant, $this->files);
                
                if (!$result['success']) {
                    throw new \Exception('File processing failed');
                }
                
                session()->flash('file-upload-success', "Processed {$result['files_processed']} files successfully");
            }

            DB::commit();

            $this->assistant = $assistant;
            $this->showCreateForm = false;
            $this->files = [];
            
            session()->flash('success', $this->assistant->wasRecentlyCreated ? 'Personal assistant created successfully!' : 'Personal assistant updated successfully!');
            
            $this->dispatch('assistant-saved');

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save assistant: ' . $e->getMessage());
        }
    }

    public function deleteAssistant()
    {
        if (!$this->assistant) {
            return;
        }

        try {
            // Clear all files first
            $fileService = new PersonalAssistantFileService();
            $fileService->clearAllFiles($this->assistant);
            
            // Delete assistant
            $this->assistant->delete();
            
            $this->assistant = null;
            session()->flash('success', 'Personal assistant deleted successfully!');
            
            $this->dispatch('assistant-deleted');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete assistant: ' . $e->getMessage());
        }
    }

    public function removeFile($fileName)
    {
        if (!$this->assistant) {
            return;
        }

        try {
            $fileService = new PersonalAssistantFileService();
            $fileService->removeFile($this->assistant, $fileName);
            
            $this->loadAssistant(); // Refresh data
            session()->flash('success', 'File removed successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove file: ' . $e->getMessage());
        }
    }

    public function clearAllFiles()
    {
        if (!$this->assistant) {
            return;
        }

        try {
            $fileService = new PersonalAssistantFileService();
            $fileService->clearAllFiles($this->assistant);
            
            $this->loadAssistant(); // Refresh data
            session()->flash('success', 'All files cleared successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to clear files: ' . $e->getMessage());
        }
    }

    public function updatedUseCaseTags()
    {
        // Auto-update system instructions based on selected use cases
        if (empty($this->system_instructions) || $this->system_instructions === $this->getDefaultInstructions()) {
            $this->system_instructions = $this->getDefaultInstructions();
        }
    }

    private function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->system_instructions = '';
        $this->model = 'gpt-4o-mini';
        $this->temperature = 0.7;
        $this->max_tokens = 1000;
        $this->use_case_tags = [];
        $this->file_analysis_enabled = true;
        $this->files = [];
    }

    private function getDefaultInstructions(): string
    {
        $instructions = "You are a helpful AI assistant";

        if (empty($this->use_case_tags)) {
            return $instructions . " designed to help with various tasks and answer questions based on uploaded documents and data.";
        }

        $useCases = [];
        foreach ($this->use_case_tags as $tag) {
            $useCases[] = match($tag) {
                'faq' => 'answer frequently asked questions',
                'product' => 'provide product information and handle inquiries',
                'onboarding' => 'guide users through onboarding and setup processes',
                'csv' => 'search and analyze CSV data',
                'sop' => 'help with standard operating procedures and internal guidelines',
                'general' => 'assist with general tasks',
                default => $tag
            };
        }

        $instructions .= " specialized in " . implode(', ', $useCases) . ".";
        
        $instructions .= "\n\nKey guidelines:";
        $instructions .= "\n- Use the uploaded documents and data as your primary knowledge source";
        $instructions .= "\n- Provide accurate, helpful, and concise responses";
        $instructions .= "\n- If information is not available in the documents, clearly state this";
        $instructions .= "\n- Maintain a professional and friendly tone";
        $instructions .= "\n- For CSV data, provide specific lookups when requested";

        return $instructions;
    }

    public function render()
    {
        return view('livewire.tenant.ai.personal-assistant-manager', [
            'availableModels' => PersonalAssistant::AVAILABLE_MODELS,
            'useCaseOptions' => PersonalAssistant::USE_CASES,
        ]);
    }
}
