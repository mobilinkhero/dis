<?php

namespace App\Services;

use App\Models\Tenant\TenantSheetConfiguration;
use App\Services\EcommerceLogger;
use Carbon\Carbon;

/**
 * Dynamic Sheet Mapper Service
 * 
 * Handles automatic detection and mapping of Google Sheets columns
 * to database fields, enabling universal structure support.
 */
class DynamicSheetMapperService
{
    protected $tenantId;
    protected $sheetType;
    protected $configuration;

    public function __construct(int $tenantId, string $sheetType = 'products')
    {
        $this->tenantId = $tenantId;
        $this->sheetType = $sheetType;
        $this->loadOrCreateConfiguration();
    }

    /**
     * Load or create configuration for this tenant
     */
    protected function loadOrCreateConfiguration(): void
    {
        $this->configuration = TenantSheetConfiguration::firstOrCreate(
            [
                'tenant_id' => $this->tenantId,
                'sheet_type' => $this->sheetType
            ],
            [
                'auto_detect_columns' => true,
                'allow_custom_fields' => true,
                'strict_mode' => false,
                'detection_status' => 'pending',
            ]
        );
    }

    /**
     * Detect and map columns from sheet headers
     */
    public function detectAndMapColumns(array $headers): array
    {
        try {
            EcommerceLogger::info('🔍 DYNAMIC-MAPPER: Detecting columns from sheet', [
                'tenant_id' => $this->tenantId,
                'sheet_type' => $this->sheetType,
                'headers' => $headers,
                'total_columns' => count($headers)
            ]);

            // Clean headers
            $cleanedHeaders = array_map(function($header) {
                return trim($header);
            }, $headers);

            // Auto-map columns
            $columnMapping = $this->configuration->autoMapColumns($cleanedHeaders);
            
            // Update configuration
            $this->configuration->update([
                'detected_columns' => $cleanedHeaders,
                'column_mapping' => $columnMapping,
                'total_columns_detected' => count($cleanedHeaders),
                'mapped_columns_count' => count($columnMapping),
                'detection_status' => 'detected',
                'last_detection_at' => Carbon::now(),
            ]);

            EcommerceLogger::info('✅ DYNAMIC-MAPPER: Columns detected and mapped', [
                'tenant_id' => $this->tenantId,
                'total_detected' => count($cleanedHeaders),
                'total_mapped' => count($columnMapping),
                'mappings' => $columnMapping
            ]);

            return [
                'success' => true,
                'detected_columns' => $cleanedHeaders,
                'column_mapping' => $columnMapping,
                'custom_fields' => $this->extractCustomFields($columnMapping),
                'has_required_fields' => $this->configuration->hasRequiredMappings(),
                'summary' => $this->configuration->getSummary()
            ];

        } catch (\Exception $e) {
            EcommerceLogger::error('❌ DYNAMIC-MAPPER: Column detection failed', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to detect columns: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Map a single row of sheet data to database fields
     */
    public function mapRowToProduct(array $sheetRow, array $headers): array
    {
        // Combine headers with row data
        $rowData = array_combine($headers, $sheetRow);
        
        $mapping = $this->configuration->column_mapping ?? [];
        
        // Core product data
        $productData = [
            'tenant_id' => $this->tenantId,
        ];

        // Map standard fields
        $standardFields = [
            'google_sheet_row_id', 'sku', 'name', 'description', 'price', 
            'sale_price', 'cost_price', 'stock_quantity', 'low_stock_threshold',
            'category', 'subcategory', 'weight', 'status', 'featured'
        ];

        foreach ($mapping as $sheetColumn => $dbField) {
            if (in_array($dbField, $standardFields)) {
                $value = $rowData[$sheetColumn] ?? null;
                $productData[$dbField] = $this->castValue($dbField, $value);
            }
        }

        // Handle special array fields (tags, images)
        if (isset($mapping['tags'])) {
            $tagsColumn = array_search('tags', $mapping);
            $tagsValue = $rowData[$tagsColumn] ?? '';
            $productData['tags'] = $this->parseArrayField($tagsValue);
        }

        if (isset($mapping['images'])) {
            $imagesColumn = array_search('images', $mapping);
            $imagesValue = $rowData[$imagesColumn] ?? '';
            $productData['images'] = $this->parseArrayField($imagesValue);
        }

        // Collect all custom fields into meta_data
        $customFields = [];
        foreach ($mapping as $sheetColumn => $dbField) {
            if (str_starts_with($dbField, 'custom_')) {
                $customFields[$dbField] = $rowData[$sheetColumn] ?? null;
            }
        }

        if (!empty($customFields)) {
            $productData['meta_data'] = $customFields;
        }

        // Set sync metadata
        $productData['sync_status'] = 'synced';
        $productData['last_synced_at'] = Carbon::now();

        return $productData;
    }

    /**
     * Cast value to appropriate type based on field
     */
    protected function castValue(string $field, $value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Numeric fields
        if (in_array($field, ['price', 'sale_price', 'cost_price', 'weight'])) {
            return (float) $value;
        }

        if (in_array($field, ['stock_quantity', 'low_stock_threshold', 'google_sheet_row_id'])) {
            return (int) $value;
        }

        // Boolean fields
        if (in_array($field, ['featured'])) {
            return in_array(strtolower($value), ['yes', 'true', '1', 'on']);
        }

        // Status field
        if ($field === 'status') {
            $status = strtolower($value);
            return in_array($status, ['active', 'inactive', 'draft']) ? $status : 'active';
        }

        // Default: return as string
        return trim($value);
    }

    /**
     * Parse comma-separated values into array
     */
    protected function parseArrayField(string $value): array
    {
        if (empty($value)) {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }

    /**
     * Extract custom fields from mapping
     */
    protected function extractCustomFields(array $mapping): array
    {
        $customFields = [];
        
        foreach ($mapping as $sheetColumn => $dbField) {
            if (str_starts_with($dbField, 'custom_')) {
                $customFields[] = [
                    'sheet_column' => $sheetColumn,
                    'db_field' => $dbField,
                    'label' => $this->generateLabel($dbField)
                ];
            }
        }
        
        return $customFields;
    }

    /**
     * Generate human-readable label from field name
     */
    protected function generateLabel(string $fieldName): string
    {
        $label = str_replace('custom_', '', $fieldName);
        $label = str_replace('_', ' ', $label);
        return ucwords($label);
    }

    /**
     * Get current configuration
     */
    public function getConfiguration(): TenantSheetConfiguration
    {
        return $this->configuration;
    }

    /**
     * Update column mapping manually
     */
    public function updateMapping(array $newMapping): bool
    {
        try {
            $this->configuration->update([
                'column_mapping' => $newMapping,
                'mapped_columns_count' => count($newMapping),
                'detection_status' => 'configured',
            ]);

            EcommerceLogger::info('✅ DYNAMIC-MAPPER: Mapping updated manually', [
                'tenant_id' => $this->tenantId,
                'new_mapping' => $newMapping
            ]);

            return true;
        } catch (\Exception $e) {
            EcommerceLogger::error('❌ DYNAMIC-MAPPER: Failed to update mapping', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get configuration summary for UI display
     */
    public function getConfigurationSummary(): array
    {
        return [
            'is_configured' => $this->configuration->detection_status === 'configured' 
                             || $this->configuration->detection_status === 'detected',
            'has_required_fields' => $this->configuration->hasRequiredMappings(),
            'total_columns' => $this->configuration->total_columns_detected,
            'mapped_columns' => $this->configuration->mapped_columns_count,
            'custom_fields' => $this->configuration->getCustomFieldNames(),
            'detected_columns' => $this->configuration->detected_columns,
            'column_mapping' => $this->configuration->column_mapping,
            'last_detection' => $this->configuration->last_detection_at,
            'last_sync' => $this->configuration->last_sync_at,
        ];
    }

    /**
     * Reset configuration (for re-detection)
     */
    public function resetConfiguration(): bool
    {
        try {
            $this->configuration->update([
                'detected_columns' => null,
                'column_mapping' => null,
                'total_columns_detected' => 0,
                'mapped_columns_count' => 0,
                'detection_status' => 'pending',
            ]);

            EcommerceLogger::info('🔄 DYNAMIC-MAPPER: Configuration reset', [
                'tenant_id' => $this->tenantId
            ]);

            return true;
        } catch (\Exception $e) {
            EcommerceLogger::error('❌ DYNAMIC-MAPPER: Failed to reset configuration', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
