<?php
// Simple test to check AI routes
echo "Testing AI routes...\n\n";

// Test if routes are defined
$routes = [
    'tenant.ai.dashboard',
    'tenant.visual.search', 
    'tenant.ar.tryout'
];

foreach ($routes as $route) {
    try {
        $url = route($route, ['subdomain' => 'test']);
        echo "✓ Route '{$route}' exists: {$url}\n";
    } catch (Exception $e) {
        echo "✗ Route '{$route}' failed: {$e->getMessage()}\n";
    }
}

echo "\n✅ Test completed!\n";
?>
