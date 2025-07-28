<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\DevAssistantBundle\Service;

use Symfony\AI\DevAssistantBundle\Contract\ModelSelectorInterface;
use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisType;

/**
 * Intelligent AI model selection strategy.
 *
 * This service implements sophisticated model selection logic based on analysis type,
 * complexity, cost considerations, and performance requirements.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class IntelligentModelSelector implements ModelSelectorInterface
{
    /**
     * @param array<string, array<string, mixed>> $modelCapabilities
     * @param array<string, float> $modelCosts
     */
    public function __construct(
        private array $modelCapabilities = [],
        private array $modelCosts = [],
        private string $defaultModel = 'claude-3-5-haiku-20241022',
        private string $highPerformanceModel = 'claude-3-5-sonnet-20241022',
    ) {
    }

    public function selectModel(AnalysisRequest $request): string
    {
        // For expert-level analysis or complex code, use high-performance models
        if ($request->requiresHighPerformanceModel()) {
            return $this->highPerformanceModel;
        }

        // Select model based on analysis type
        $model = match ($request->type) {
            AnalysisType::CODE_QUALITY => $this->selectCodeQualityModel($request),
            AnalysisType::ARCHITECTURE => $this->selectArchitectureModel($request),
            AnalysisType::PERFORMANCE => $this->selectPerformanceModel($request),
            AnalysisType::SECURITY => $this->selectSecurityModel($request),
        };

        return $model ?? $this->defaultModel;
    }

    public function getFallbackModel(string $primaryModel): ?string
    {
        $fallbackMap = [
            'claude-3-5-sonnet-20241022' => 'claude-3-5-haiku-20241022',
            'gpt-4o' => 'gpt-4o-mini',
            'claude-3-5-haiku-20241022' => 'gpt-4o-mini',
            'gpt-4o-mini' => null, // Last resort
        ];

        return $fallbackMap[$primaryModel] ?? $this->defaultModel;
    }

    public function estimateCost(AnalysisRequest $request, string $model): float
    {
        $baseCost = $this->modelCosts[$model] ?? 0.001; // Per 1K tokens
        $estimatedTokens = $this->estimateTokenCount($request);
        
        return ($estimatedTokens / 1000) * $baseCost;
    }

    private function selectCodeQualityModel(AnalysisRequest $request): string
    {
        // Claude excels at code understanding and pattern recognition
        if ($request->depth === 'expert' || \count($request->rules) > 5) {
            return 'claude-3-5-sonnet-20241022';
        }

        return 'claude-3-5-haiku-20241022';
    }

    private function selectArchitectureModel(AnalysisRequest $request): string
    {
        // Architecture analysis requires deep reasoning - always use Sonnet
        return 'claude-3-5-sonnet-20241022';
    }

    private function selectPerformanceModel(AnalysisRequest $request): string
    {
        // Performance analysis benefits from Claude's analytical capabilities
        if ($request->getCodeLength() > 5000 || $request->depth === 'expert') {
            return 'claude-3-5-sonnet-20241022';
        }

        return 'claude-3-5-haiku-20241022';
    }

    private function selectSecurityModel(AnalysisRequest $request): string
    {
        // Security analysis is critical - use high-performance model
        return 'claude-3-5-sonnet-20241022';
    }

    private function estimateTokenCount(AnalysisRequest $request): int
    {
        // Rough estimation: 1 token ≈ 4 characters for code
        $codeTokens = (int) ($request->getCodeLength() / 4);
        
        // Add tokens for prompt and context
        $promptTokens = match ($request->depth) {
            'basic' => 500,
            'standard' => 800,
            'comprehensive' => 1200,
            'expert' => 1800,
            default => 800,
        };

        return $codeTokens + $promptTokens;
    }
}
