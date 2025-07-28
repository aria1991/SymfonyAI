<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\DevAssistantBundle\Analyzer;

use Psr\Log\LoggerInterface;
use Symfony\AI\DevAssistantBundle\Contract\AnalyzerInterface;
use Symfony\AI\DevAssistantBundle\Exception\AnalysisException;
use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisResult;
use Symfony\AI\DevAssistantBundle\Model\AnalysisType;
use Symfony\AI\DevAssistantBundle\Service\PromptTemplateEngine;
use Symfony\AI\DevAssistantBundle\Service\ResponseParser;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Enterprise-grade code quality analyzer with proper abstraction layers.
 *
 * This analyzer implements the strategy pattern for flexible AI model usage,
 * includes comprehensive error handling, rate limiting, and follows SOLID principles.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final class EnterpriseCodeQualityAnalyzer implements AnalyzerInterface
{
    public function __construct(
        private PlatformInterface $aiPlatform,
        private PromptTemplateEngine $promptEngine,
        private ResponseParser $responseParser,
        private ValidatorInterface $validator,
        private RateLimiterFactory $rateLimiterFactory,
        private LoggerInterface $logger,
        private int $maxCodeLength = 50000,
        private int $timeoutSeconds = 30,
    ) {
    }

    public function analyze(AnalysisRequest $request): AnalysisResult
    {
        // Validate request
        $this->validateRequest($request);

        // Apply rate limiting
        $rateLimiter = $this->rateLimiterFactory->create('code_analysis');
        if (!$rateLimiter->consume()->isAccepted()) {
            throw new AnalysisException('Rate limit exceeded for code analysis');
        }

        $this->logger->info('Starting enterprise code quality analysis', [
            'request_id' => $request->requestId,
            'code_length' => $request->getCodeLength(),
            'depth' => $request->depth,
        ]);

        try {
            // Generate optimized prompt
            $prompt = $this->promptEngine->generatePrompt('code_quality', $request);

            // Execute AI analysis with timeout
            $response = $this->executeAiAnalysis($prompt, $request);

            // Parse and validate response
            $result = $this->responseParser->parseCodeQualityResponse($response, $request);

            // Enhance with post-processing
            $enhancedResult = $this->enhanceResult($result, $request);

            $this->logger->info('Code quality analysis completed successfully', [
                'request_id' => $request->requestId,
                'issues_found' => \count($enhancedResult->issues),
                'confidence' => $enhancedResult->confidence,
            ]);

            return $enhancedResult;

        } catch (\Throwable $e) {
            $this->logger->error('Code quality analysis failed', [
                'request_id' => $request->requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new AnalysisException(
                "Code quality analysis failed: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    public function supports(AnalysisRequest $request): bool
    {
        return $request->type === AnalysisType::CODE_QUALITY &&
               $request->getCodeLength() <= $this->maxCodeLength;
    }

    public function getName(): string
    {
        return 'enterprise_code_quality';
    }

    public function getPriority(): int
    {
        return 100; // High priority for code quality analysis
    }

    public function getEstimatedDuration(AnalysisRequest $request): int
    {
        $baseTime = 5; // Base 5 seconds
        $complexityMultiplier = min($request->getCodeComplexityEstimate() / 5, 3);
        $lengthMultiplier = min($request->getCodeLength() / 10000, 2);
        
        return (int) ($baseTime * $complexityMultiplier * $lengthMultiplier);
    }

    private function validateRequest(AnalysisRequest $request): void
    {
        $violations = $this->validator->validate($request);
        
        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            
            throw new AnalysisException('Invalid analysis request: ' . implode(', ', $errors));
        }

        if ($request->getCodeLength() > $this->maxCodeLength) {
            throw new AnalysisException(
                "Code length ({$request->getCodeLength()}) exceeds maximum allowed ({$this->maxCodeLength})"
            );
        }

        if (empty(trim($request->code))) {
            throw new AnalysisException('Code content cannot be empty');
        }
    }

    private function executeAiAnalysis(string $prompt, AnalysisRequest $request): string
    {
        $startTime = microtime(true);
        
        try {
            // Create message with proper structure
            $messages = $this->promptEngine->createMessageBag($prompt, $request);

            // Execute with timeout and error handling
            $result = $this->aiPlatform->invoke(
                model: $this->getSelectedModel($request),
                messages: $messages,
                options: [
                    'temperature' => $this->getTemperature($request),
                    'max_tokens' => $this->getMaxTokens($request),
                    'timeout' => $this->timeoutSeconds,
                ]
            )->getResult();

            $duration = microtime(true) - $startTime;
            
            $this->logger->debug('AI analysis executed', [
                'duration_seconds' => round($duration, 3),
                'model' => $this->getSelectedModel($request),
            ]);

            return $result->getContent();

        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            
            $this->logger->error('AI analysis execution failed', [
                'duration_seconds' => round($duration, 3),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function enhanceResult(AnalysisResult $result, AnalysisRequest $request): AnalysisResult
    {
        // Add static analysis correlation
        $enhancedMetrics = array_merge(
            $result->metrics,
            $this->calculateAdditionalMetrics($request, $result)
        );

        // Calculate confidence adjustments
        $adjustedConfidence = $this->adjustConfidence($result->confidence, $request);

        return new AnalysisResult(
            type: $result->type,
            summary: $result->summary,
            issues: $this->prioritizeIssues($result->issues),
            suggestions: $this->optimizeSuggestions($result->suggestions),
            metrics: $enhancedMetrics,
            overallSeverity: $result->overallSeverity,
            confidence: $adjustedConfidence,
            analyzedAt: $result->analyzedAt,
        );
    }

    private function getSelectedModel(AnalysisRequest $request): string
    {
        // This would typically come from the model selector service
        if ($request->requiresHighPerformanceModel()) {
            return 'claude-3-5-sonnet-20241022';
        }

        return 'claude-3-5-haiku-20241022';
    }

    private function getTemperature(AnalysisRequest $request): float
    {
        // Lower temperature for more consistent analysis
        return match ($request->depth) {
            'expert' => 0.05,
            'comprehensive' => 0.1,
            default => 0.15,
        };
    }

    private function getMaxTokens(AnalysisRequest $request): int
    {
        return match ($request->depth) {
            'basic' => 1500,
            'standard' => 2500,
            'comprehensive' => 3500,
            'expert' => 4000,
            default => 2500,
        };
    }

    /**
     * @param array<string, mixed> $existingMetrics
     * @return array<string, mixed>
     */
    private function calculateAdditionalMetrics(AnalysisRequest $request, AnalysisResult $result): array
    {
        return [
            'analysis_efficiency' => $this->calculateAnalysisEfficiency($request, $result),
            'code_health_score' => $this->calculateCodeHealthScore($result),
            'improvement_potential' => $this->calculateImprovementPotential($result),
            'technical_debt_estimate' => $this->estimateTechnicalDebt($result),
        ];
    }

    private function adjustConfidence(float $baseConfidence, AnalysisRequest $request): float
    {
        $adjustments = 0;

        // Adjust based on code complexity
        if ($request->getCodeComplexityEstimate() > 8) {
            $adjustments -= 0.1; // Lower confidence for complex code
        }

        // Adjust based on code length
        if ($request->getCodeLength() < 100) {
            $adjustments -= 0.15; // Lower confidence for very short code
        }

        return max(0.0, min(1.0, $baseConfidence + $adjustments));
    }

    /**
     * @param array<\Symfony\AI\DevAssistantBundle\Model\Issue> $issues
     * @return array<\Symfony\AI\DevAssistantBundle\Model\Issue>
     */
    private function prioritizeIssues(array $issues): array
    {
        // Sort issues by severity and impact
        usort($issues, function ($a, $b) {
            $severityComparison = $b->severity->getPriority() - $a->severity->getPriority();
            if ($severityComparison !== 0) {
                return $severityComparison;
            }
            
            // Secondary sort by category importance
            $categoryPriority = [
                'security' => 10,
                'performance' => 8,
                'maintainability' => 6,
                'architecture' => 5,
                'code_style' => 3,
            ];
            
            $aPriority = $categoryPriority[$a->category->value] ?? 1;
            $bPriority = $categoryPriority[$b->category->value] ?? 1;
            
            return $bPriority - $aPriority;
        });

        return $issues;
    }

    /**
     * @param array<\Symfony\AI\DevAssistantBundle\Model\Suggestion> $suggestions
     * @return array<\Symfony\AI\DevAssistantBundle\Model\Suggestion>
     */
    private function optimizeSuggestions(array $suggestions): array
    {
        // Filter out low-impact suggestions if there are too many
        if (\count($suggestions) > 10) {
            return array_filter($suggestions, fn($s) => 
                $s->priority->getWeight() > 0.5 || 
                ($s->estimatedImpact ?? 0) > 0.6
            );
        }

        return $suggestions;
    }

    private function calculateAnalysisEfficiency(AnalysisRequest $request, AnalysisResult $result): float
    {
        $issuesPerKLOC = (\count($result->issues) / max(1, $request->getCodeLength() / 1000));
        return min(10, max(0, 10 - $issuesPerKLOC));
    }

    private function calculateCodeHealthScore(AnalysisResult $result): float
    {
        $criticalIssues = array_filter($result->issues, fn($i) => $i->severity->value === 'critical');
        $highIssues = array_filter($result->issues, fn($i) => $i->severity->value === 'high');
        
        $baseScore = 10;
        $baseScore -= \count($criticalIssues) * 2;
        $baseScore -= \count($highIssues) * 1;
        
        return max(0, min(10, $baseScore));
    }

    private function calculateImprovementPotential(AnalysisResult $result): float
    {
        $totalSuggestions = \count($result->suggestions);
        $highImpactSuggestions = array_filter(
            $result->suggestions, 
            fn($s) => ($s->estimatedImpact ?? 0) > 0.7
        );
        
        return $totalSuggestions > 0 ? (\count($highImpactSuggestions) / $totalSuggestions) * 10 : 0;
    }

    private function estimateTechnicalDebt(AnalysisResult $result): int
    {
        $debtMinutes = 0;
        
        foreach ($result->issues as $issue) {
            $debtMinutes += match ($issue->severity->value) {
                'critical' => 240, // 4 hours
                'high' => 120,     // 2 hours
                'medium' => 60,    // 1 hour
                'low' => 30,       // 30 minutes
                default => 15,     // 15 minutes
            };
        }
        
        return $debtMinutes;
    }
}
