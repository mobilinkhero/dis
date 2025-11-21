<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\Contact;
use App\Models\Tenant\EcommerceConfiguration;
use App\Services\EcommerceOrderService;
use App\Services\EcommerceLogger;
use Livewire\Component;
use Illuminate\Support\Str;

class AiChatTester extends Component
{
    public $config;
    public $messages = [];
    public $currentMessage = '';
    public $isTyping = false;
    public $testContact;
    public $sessionId;
    public $showDebugInfo = false;
    public $lastResponse = [];
    
    // Test scenarios
    public $quickTests = [
        'Show me products' => 'Browse catalog',
        'I want to buy iPhone' => 'Purchase intent',
        'What\'s on sale?' => 'Price inquiry',
        'Track my order' => 'Order tracking',
        'I have a problem' => 'Support request',
        'Compare products' => 'Product comparison',
        'Hello' => 'Greeting',
    ];

    protected $listeners = ['resetChat' => 'initializeChat'];

    public function mount()
    {
        $this->config = EcommerceConfiguration::where('tenant_id', tenant_id())->first();
        $this->initializeChat();
    }

    public function initializeChat()
    {
        $this->sessionId = 'test_' . Str::random(10);
        
        // Create or get test contact
        $this->testContact = Contact::firstOrCreate(
            [
                'tenant_id' => tenant_id(),
                'phone' => '+1234567890_test_' . $this->sessionId,
            ],
            [
                'firstname' => 'Test',
                'lastname' => 'Customer',
                'email' => 'test@example.com',
                'type' => 'lead',
                'source_id' => 1,
            ]
        );

        $this->messages = [
            [
                'role' => 'bot',
                'message' => '👋 Hi! I\'m your AI Shopping Assistant. How can I help you today?',
                'time' => now()->format('h:i A'),
                'debug' => null
            ]
        ];
        
        $this->currentMessage = '';
        $this->isTyping = false;
        $this->lastResponse = [];
    }

    public function sendMessage()
    {
        if (empty(trim($this->currentMessage))) {
            return;
        }

        $userMessage = $this->currentMessage;
        
        // Add user message to chat
        $this->messages[] = [
            'role' => 'user',
            'message' => $userMessage,
            'time' => now()->format('h:i A'),
            'debug' => null
        ];

        $this->currentMessage = '';
        $this->isTyping = true;

        try {
            if (!$this->config || !$this->config->isFullyConfigured()) {
                $this->addBotMessage(
                    '⚠️ E-commerce is not fully configured. Please complete the setup first.',
                    ['error' => 'Configuration incomplete']
                );
                $this->isTyping = false;
                return;
            }

            // Process message with AI service
            EcommerceLogger::info('🧪 TEST CHAT: Processing test message', [
                'tenant_id' => tenant_id(),
                'session_id' => $this->sessionId,
                'message' => $userMessage
            ]);

            $ecommerceService = new EcommerceOrderService(tenant_id());
            $result = $ecommerceService->processMessage($userMessage, $this->testContact);

            EcommerceLogger::info('🧪 TEST CHAT: Received AI response', [
                'tenant_id' => tenant_id(),
                'session_id' => $this->sessionId,
                'handled' => $result['handled'] ?? false,
                'has_response' => !empty($result['response']),
                'has_buttons' => !empty($result['buttons']),
                'has_actions' => !empty($result['actions'])
            ]);

            $this->lastResponse = $result;

            // Add bot response
            $debugInfo = [
                'handled' => $result['handled'] ?? false,
                'response_type' => $result['type'] ?? 'text',
                'buttons_count' => count($result['buttons'] ?? []),
                'actions_count' => count($result['actions'] ?? []),
                'actions' => $result['actions'] ?? [],
                'metadata' => $result['metadata'] ?? []
            ];

            $this->addBotMessage(
                $result['response'] ?? 'No response generated',
                $debugInfo,
                $result['buttons'] ?? []
            );

        } catch (\Exception $e) {
            EcommerceLogger::error('🧪 TEST CHAT: Error processing message', [
                'tenant_id' => tenant_id(),
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addBotMessage(
                '❌ Error: ' . $e->getMessage(),
                ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        }

        $this->isTyping = false;
    }

    public function sendQuickTest($message)
    {
        $this->currentMessage = $message;
        $this->sendMessage();
    }

    public function toggleDebug()
    {
        $this->showDebugInfo = !$this->showDebugInfo;
    }

    protected function addBotMessage($message, $debug = null, $buttons = [])
    {
        $this->messages[] = [
            'role' => 'bot',
            'message' => $message,
            'time' => now()->format('h:i A'),
            'debug' => $debug,
            'buttons' => $buttons
        ];
    }

    public function clearChat()
    {
        $this->initializeChat();
        $this->dispatch('chatCleared');
    }

    public function render()
    {
        return view('livewire.tenant.ecommerce.ai-chat-tester');
    }
}