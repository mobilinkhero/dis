<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\EcommerceBot;
use App\Services\GoogleSheetsEcommerceService;
use App\Rules\PurifiedInput;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class EcommerceSetup extends Component
{
    public ?EcommerceBot $ecommerceBot = null;
    public bool $isEnabled = false;
    public string $productSheetsUrl = '';
    public string $orderSheetsUrl = '';
    public array $productValidation = [];
    public array $orderValidation = [];
    public array $syncSettings = [];
    public bool $showAdvancedSettings = false;
    public string $currentStep = 'setup'; // setup, validate, configure, complete
    public array $validationResults = [];

    protected function rules()
    {
        return [
            'isEnabled' => 'boolean',
            'productSheetsUrl' => [
                'required_if:isEnabled,true',
                'url',
                new PurifiedInput('Invalid URL format'),
            ],
            'orderSheetsUrl' => [
                'nullable',
                'url', 
                new PurifiedInput('Invalid URL format'),
            ],
        ];
    }

    public function mount()
    {
        // Check permissions
        if (!checkPermission('tenant.ecommerce.setup')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        // Load existing configuration
        $this->ecommerceBot = EcommerceBot::where('tenant_id', tenant_id())->first();
        
        if ($this->ecommerceBot) {
            $this->isEnabled = $this->ecommerceBot->is_enabled;
            $this->productSheetsUrl = $this->ecommerceBot->google_sheets_product_url ?? '';
            $this->orderSheetsUrl = $this->ecommerceBot->google_sheets_order_url ?? '';
            $this->syncSettings = $this->ecommerceBot->sync_settings ?? $this->getDefaultSyncSettings();
        } else {
            $this->syncSettings = $this->getDefaultSyncSettings();
        }
    }

    public function updatedIsEnabled($value)
    {
        // This method is called when isEnabled property changes
        if ($value) {
            $this->notify(['type' => 'success', 'message' => 'E-commerce bot enabled! Configure your Google Sheets below.']);
        } else {
            $this->notify(['type' => 'info', 'message' => 'E-commerce bot disabled.']);
        }
    }

    public function validateGoogleSheets()
    {
        $this->validate();

        try {
            $this->validationResults = [];

            // Create temporary EcommerceBot for validation
            $tempBot = new EcommerceBot([
                'tenant_id' => tenant_id(),
                'google_sheets_product_url' => $this->productSheetsUrl,
                'google_sheets_order_url' => $this->orderSheetsUrl,
            ]);

            $sheetsService = new GoogleSheetsEcommerceService($tempBot);

            // Validate product sheet
            if ($this->productSheetsUrl) {
                $this->validationResults['products'] = $sheetsService->validateSheetsUrl($this->productSheetsUrl);
            }

            // Validate order sheet if provided
            if ($this->orderSheetsUrl) {
                $this->validationResults['orders'] = $sheetsService->validateSheetsUrl($this->orderSheetsUrl);
            }

            if (empty($this->validationResults)) {
                $this->notify(['type' => 'danger', 'message' => 'Please provide at least a product sheet URL']);
                return;
            }

            $allValid = collect($this->validationResults)->every(fn($result) => $result['success']);

            if ($allValid) {
                $this->currentStep = 'configure';
                $this->notify(['type' => 'success', 'message' => 'Google Sheets validated successfully!']);
            } else {
                $this->notify(['type' => 'danger', 'message' => 'Please fix the validation errors before proceeding']);
            }

        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Validation failed: ' . $e->getMessage()]);
        }
    }

    public function saveConfiguration()
    {
        // Validate before saving
        $this->validate([
            'isEnabled' => 'required|boolean',
            'productSheetsUrl' => 'required_if:isEnabled,true|url',
            'orderSheetsUrl' => 'nullable|url',
        ]);

        try {
            DB::transaction(function () {
                $data = [
                    'tenant_id' => tenant_id(),
                    'is_enabled' => $this->isEnabled,
                    'google_sheets_product_url' => $this->productSheetsUrl,
                    'google_sheets_order_url' => $this->orderSheetsUrl ?: null,
                    'sync_settings' => $this->syncSettings,
                ];

                // Create or update EcommerceBot
                if ($this->ecommerceBot) {
                    $this->ecommerceBot->update($data);
                } else {
                    $this->ecommerceBot = EcommerceBot::create($data);
                }

                // Extract and save sheet IDs
                if ($this->productSheetsUrl) {
                    $productSheetsId = $this->extractSheetsId($this->productSheetsUrl);
                    $this->ecommerceBot->update(['sheets_product_id' => $productSheetsId]);
                }

                if ($this->orderSheetsUrl) {
                    $orderSheetsId = $this->extractSheetsId($this->orderSheetsUrl);
                    $this->ecommerceBot->update(['sheets_order_id' => $orderSheetsId]);
                }
            });

            $this->currentStep = 'complete';
            $this->notify(['type' => 'success', 'message' => 'E-commerce bot configured successfully!']);

        } catch (\Exception $e) {
            \Log::error('EcommerceSetup saveConfiguration error: ' . $e->getMessage());
            $this->notify(['type' => 'danger', 'message' => 'Configuration failed: ' . $e->getMessage()]);
        }
    }

    private function extractSheetsId($url)
    {
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function performInitialSync()
    {
        if (!$this->ecommerceBot || !$this->ecommerceBot->isReady()) {
            $this->notify(['type' => 'danger', 'message' => 'E-commerce bot is not properly configured']);
            return;
        }

        try {
            $sheetsService = new GoogleSheetsEcommerceService($this->ecommerceBot);
            $result = $sheetsService->syncProductsFromSheets();

            if ($result['success']) {
                $this->notify(['type' => 'success', 'message' => $result['message']]);
                return redirect()->route('tenant.products.list');
            } else {
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }

        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    public function createSampleSheets()
    {
        $sheetsService = new GoogleSheetsEcommerceService(new EcommerceBot());
        
        $productStructure = $sheetsService->getDefaultProductSheetStructure();
        $orderStructure = $sheetsService->getDefaultOrderSheetStructure();

        return [
            'products' => $productStructure,
            'orders' => $orderStructure
        ];
    }

    public function updateSyncSettings($field, $value)
    {
        data_set($this->syncSettings, $field, $value);
    }

    public function toggleAdvancedSettings()
    {
        $this->showAdvancedSettings = !$this->showAdvancedSettings;
    }

    public function resetSetup()
    {
        $this->currentStep = 'setup';
        $this->validationResults = [];
        $this->productValidation = [];
        $this->orderValidation = [];
    }

    public function goToStep($step)
    {
        $allowedSteps = ['setup', 'validate', 'configure', 'complete'];
        
        if (!in_array($step, $allowedSteps)) {
            $this->notify(['type' => 'danger', 'message' => 'Invalid step: ' . $step]);
            return;
        }

        $this->currentStep = $step;
        $this->notify(['type' => 'info', 'message' => 'Moved to step: ' . ucfirst($step)]);
    }

    private function getDefaultSyncSettings(): array
    {
        return [
            'auto_sync_enabled' => true,
            'sync_interval_minutes' => 30,
            'product_columns' => [
                'name' => 'Product Name',
                'price' => 'Price',
                'description' => 'Description',
                'image_url' => 'Image URL',
                'stock_quantity' => 'Stock',
                'category' => 'Category',
                'sku' => 'SKU',
                'status' => 'Status'
            ],
            'order_columns' => [
                'customer_name' => 'Customer Name',
                'customer_phone' => 'Phone',
                'product_name' => 'Product',
                'quantity' => 'Quantity',
                'total_amount' => 'Total',
                'status' => 'Status',
                'order_date' => 'Order Date',
                'delivery_address' => 'Address'
            ]
        ];
    }

    public function render()
    {
        return view('livewire.tenant.ecommerce.ecommerce-setup', [
            'sampleSheets' => $this->createSampleSheets()
        ]);
    }
}
