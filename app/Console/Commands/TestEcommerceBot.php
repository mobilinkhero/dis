<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EcommerceOrderService;
use App\Models\Tenant\Contact;
use App\Models\Tenant\EcommerceConfiguration;

class TestEcommerceBot extends Command
{
    protected $signature = 'ecommerce:test-bot {message=shop} {--tenant=}';
    protected $description = 'Test the e-commerce bot with a sample message';

    public function handle()
    {
        $message = $this->argument('message');
        $tenantId = $this->option('tenant') ?? tenant_id();

        $this->info("Testing E-commerce Bot");
        $this->info("Tenant ID: {$tenantId}");
        $this->info("Message: {$message}");
        $this->line(str_repeat('=', 50));

        // Check configuration
        $config = EcommerceConfiguration::where('tenant_id', $tenantId)->first();
        
        if (!$config) {
            $this->error("❌ No e-commerce configuration found for tenant {$tenantId}");
            return 1;
        }

        $this->info("✅ Configuration found");
        $this->info("   - Is Configured: " . ($config->is_configured ? 'Yes' : 'No'));
        $this->info("   - Google Sheets URL: " . ($config->google_sheets_url ? 'Set' : 'Not set'));
        $this->info("   - Fully Configured: " . ($config->isFullyConfigured() ? 'Yes' : 'No'));
        $this->line('');

        if (!$config->isFullyConfigured()) {
            $this->error("❌ E-commerce is not fully configured");
            return 1;
        }

        // Create a test contact
        $testContact = Contact::firstOrCreate(
            ['phone' => '1234567890', 'tenant_id' => $tenantId],
            [
                'firstname' => 'Test',
                'lastname' => 'Customer',
                'type' => 'lead'
            ]
        );

        $this->info("✅ Test contact created/found: {$testContact->firstname} {$testContact->lastname}");
        $this->line('');

        // Test the bot
        try {
            $service = new EcommerceOrderService($tenantId);
            $result = $service->processMessage($message, $testContact);

            $this->line(str_repeat('=', 50));
            $this->info("Bot Response:");
            $this->line(str_repeat('=', 50));
            
            if ($result['handled']) {
                $this->info("✅ Message was handled by e-commerce bot");
                $this->line('');
                $this->line($result['response']);
            } else {
                $this->warn("⚠️  Message was NOT handled by e-commerce bot");
                if ($result['response']) {
                    $this->line($result['response']);
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}
