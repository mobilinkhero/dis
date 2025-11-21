<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Security Intelligence Service
 * Real-time fraud detection and security monitoring
 */
class SecurityIntelligenceService
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Analyze potential security threats
     */
    public function analyzeThreat(string $message, $contact, array $context = []): array
    {
        $riskScore = 0;
        $threats = [];

        // Check for suspicious patterns
        $riskScore += $this->checkSuspiciousPatterns($message);
        $riskScore += $this->checkRateLimit($contact);
        $riskScore += $this->checkSpamIndicators($message);

        // Determine risk level
        $riskLevel = 'low';
        if ($riskScore >= 70) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 40) {
            $riskLevel = 'medium';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'threats' => $threats,
            'blocked' => $riskLevel === 'high'
        ];
    }

    protected function checkSuspiciousPatterns(string $message): int
    {
        $suspiciousWords = ['hack', 'fraud', 'scam', 'phishing'];
        $score = 0;
        
        foreach ($suspiciousWords as $word) {
            if (stripos($message, $word) !== false) {
                $score += 20;
            }
        }
        
        return min(50, $score);
    }

    protected function checkRateLimit($contact): int
    {
        // Simple rate limiting check
        $key = "rate_limit_{$this->tenantId}_{$contact->id}";
        $count = Cache::get($key, 0);
        
        if ($count > 10) { // More than 10 messages per minute
            return 30;
        }
        
        Cache::put($key, $count + 1, 60); // Increment for 1 minute
        return 0;
    }

    protected function checkSpamIndicators(string $message): int
    {
        $score = 0;
        
        // Check for excessive caps
        if (preg_match('/[A-Z]{10,}/', $message)) {
            $score += 15;
        }
        
        // Check for repetitive characters
        if (preg_match('/(.)\1{5,}/', $message)) {
            $score += 20;
        }
        
        return $score;
    }
}
