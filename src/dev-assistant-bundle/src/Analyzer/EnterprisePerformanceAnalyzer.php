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
use Symfony\AI\DevAssistantBundle\Service\IntelligentModelSelector;
use Symfony\AI\DevAssistantBundle\Service\PromptTemplateEngine;
use Symfony\AI\DevAssistantBundle\Service\ResponseParser;
use Symfony\AI\ToolBox\ToolboxRunner;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Enterprise-grade performance analyzer with advanced optimization detection.
 *
 * This analyzer identifies performance bottlenecks, algorithmic inefficiencies,
 * database optimization opportunities, and provides actionable performance
 * improvement suggestions using sophisticated AI analysis.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class EnterprisePerformanceAnalyzer implements AnalyzerInterface
{
    public function __construct(
        private ToolboxRunner $toolboxRunner,
        private IntelligentModelSelector $modelSelector,
        private PromptTemplateEngine $templateEngine,
        private ResponseParser $responseParser,
        private CacheInterface $cache,
        private RateLimiterFactory $rateLimiterFactory,
        private LoggerInterface $logger,
        private Stopwatch $stopwatch,
    ) {
    }

    public function supports(AnalysisType $type): bool
    {
        return $type === AnalysisType::PERFORMANCE;
    }

    public function getName(): string
    {
        return 'enterprise_performance_analyzer';
    }

    public function getPriority(): int
    {
        return 85; // High priority for performance analysis
    }

    /**
     * @throws AnalysisException
     */
    public function analyze(AnalysisRequest $request): AnalysisResult
    {
        $this->validateRequest($request);
        
        // Rate limiting protection
        $rateLimiter = $this->rateLimiterFactory->create($this->getName());
        if (!$rateLimiter->consume(1)->isAccepted()) {
            throw new AnalysisException('Rate limit exceeded for performance analysis');
        }

        $this->stopwatch->start('performance_analysis');

        try {
            $this->logger->info('Starting enterprise performance analysis', [
                'request_id' => $request->id,
                'files_count' => count($request->files),
                'complexity_estimate' => $request->getCodeComplexityEstimate(),
            ]);

            // Check cache first
            $cacheKey = $this->generateCacheKey($request);
            $cachedResult = $this->cache->get($cacheKey, function () use ($request) {
                return $this->performPerformanceAnalysis($request);
            });

            $duration = $this->stopwatch->stop('performance_analysis')->getDuration();
            
            $this->logger->info('Performance analysis completed', [
                'request_id' => $request->id,
                'duration' => $duration,
                'issues_found' => count($cachedResult->issues),
                'optimization_suggestions' => count($cachedResult->suggestions),
                'performance_score' => $cachedResult->metrics['performance_score'] ?? 'N/A',
            ]);

            return $cachedResult;

        } catch (\Throwable $e) {
            $this->logger->error('Performance analysis failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new AnalysisException(
                'Failed to perform performance analysis: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    private function validateRequest(AnalysisRequest $request): void
    {
        if ($request->type !== AnalysisType::PERFORMANCE) {
            throw new AnalysisException('Invalid analysis type for performance analyzer');
        }

        if (empty($request->files)) {
            throw new AnalysisException('No files provided for performance analysis');
        }

        // Performance analysis needs substantial code to be meaningful
        $totalSize = array_sum(array_map('strlen', $request->files));
        if ($totalSize < 500) {
            throw new AnalysisException('Insufficient code for meaningful performance analysis');
        }
    }

    private function performPerformanceAnalysis(AnalysisRequest $request): AnalysisResult
    {
        // Select optimal AI model for performance analysis
        $modelInfo = $this->modelSelector->selectBestModel($request);
        
        $this->logger->debug('Selected AI model for performance analysis', [
            'model' => $modelInfo['model'],
            'provider' => $modelInfo['provider'],
            'performance_focus' => true,
            'estimated_cost' => $modelInfo['estimated_cost'],
        ]);

        // Generate specialized performance analysis prompt
        $prompt = $this->templateEngine->generatePrompt(
            'performance_analysis',
            [
                'files' => $request->files,
                'project_type' => $request->projectType,
                'performance_focus_areas' => $this->getPerformanceFocusAreas(),
                'complexity_indicators' => $this->getComplexityIndicators($request),
                'optimization_patterns' => $this->getOptimizationPatterns(),
            ]
        );

        // Execute AI analysis with performance-specific configuration
        $response = $this->executeWithRetry($modelInfo, $prompt, $request);

        // Parse and validate response
        return $this->responseParser->parsePerformanceResponse($response, $request);
    }

    /**
     * @return array<string>
     */
    private function getPerformanceFocusAreas(): array
    {
        return [
            'Algorithmic Complexity (Big O Analysis)',
            'Database Query Optimization',
            'Memory Usage Patterns',
            'Caching Opportunities',
            'I/O Operations Efficiency',
            'Loop and Iteration Optimization',
            'Data Structure Selection',
            'Lazy vs Eager Loading',
            'Resource Management',
            'Concurrency and Async Patterns',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getComplexityIndicators(AnalysisRequest $request): array
    {
        $indicators = [];
        
        // Analyze code for complexity indicators
        foreach ($request->files as $filePath => $content) {
            $indicators[$filePath] = [
                'nested_loops' => $this->countNestedLoops($content),
                'database_calls' => $this->countDatabaseCalls($content),
                'recursive_patterns' => $this->countRecursivePatterns($content),
                'memory_allocations' => $this->countMemoryAllocations($content),
            ];
        }
        
        return $indicators;
    }

    /**
     * @return array<string>
     */
    private function getOptimizationPatterns(): array
    {
        return [
            'Batch Processing',
            'Connection Pooling',
            'Query Result Caching',
            'Object Pooling',
            'Lazy Initialization',
            'Memoization',
            'Compression Strategies',
            'Index Optimization',
            'Pagination Implementation',
            'Asynchronous Processing',
        ];
    }

    private function countNestedLoops(string $content): int
    {
        // Simple pattern matching for nested loop detection
        $pattern = '/foreach\s*\([^)]+\)\s*{[^}]*foreach\s*\([^)]+\)/s';
        return preg_match_all($pattern, $content);
    }

    private function countDatabaseCalls(string $content): int
    {
        // Pattern matching for common database operations
        $patterns = [
            '/\$this->.*Repository.*->find/',
            '/\$entityManager->/',
            '/\$connection->execute/',
            '/Query.*->execute/',
        ];
        
        $count = 0;
        foreach ($patterns as $pattern) {
            $count += preg_match_all($pattern, $content);
        }
        
        return $count;
    }

    private function countRecursivePatterns(string $content): int
    {
        // Look for functions calling themselves
        preg_match_all('/function\s+(\w+)/', $content, $functions);
        $count = 0;
        
        foreach ($functions[1] as $functionName) {
            if (preg_match('/\b' . preg_quote($functionName) . '\s*\(/', $content)) {
                $count++;
            }
        }
        
        return $count;
    }

    private function countMemoryAllocations(string $content): int
    {
        // Pattern matching for potential memory-intensive operations
        $patterns = [
            '/new\s+\w+/',           // Object instantiation
            '/array_merge\s*\(/',    // Array merging
            '/str_repeat\s*\(/',     // String repetition
            '/range\s*\(/',          // Range generation
        ];
        
        $count = 0;
        foreach ($patterns as $pattern) {
            $count += preg_match_all($pattern, $content);
        }
        
        return $count;
    }

    /**
     * @param array<string, mixed> $modelInfo
     */
    private function executeWithRetry(array $modelInfo, string $prompt, AnalysisRequest $request): string
    {
        $maxRetries = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->logger->debug("Performance analysis attempt {$attempt}/{$maxRetries}", [
                    'request_id' => $request->id,
                    'model' => $modelInfo['model'],
                ]);

                $response = $this->toolboxRunner->run($modelInfo['provider'], $modelInfo['model'], [
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.05, // Very low temperature for consistent performance analysis
                    'max_tokens' => 4000,
                ]);

                if (empty($response)) {
                    throw new AnalysisException('Empty response from AI model');
                }

                return $response;

            } catch (\Throwable $e) {
                $lastException = $e;
                $this->logger->warning("Performance analysis attempt {$attempt} failed", [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    // Exponential backoff with jitter
                    usleep(1000000 * $attempt + random_int(0, 500000));
                }
            }
        }

        throw new AnalysisException(
            'Performance analysis failed after all retry attempts',
            previous: $lastException
        );
    }

    private function generateCacheKey(AnalysisRequest $request): string
    {
        return sprintf(
            'performance_analysis_%s_%s',
            $request->getCacheKey(),
            hash('md5', $this->getName() . '|performance|' . $request->projectType)
        );
    }
}
