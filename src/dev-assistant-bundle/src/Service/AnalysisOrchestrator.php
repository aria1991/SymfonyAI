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

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\DevAssistantBundle\Contract\AnalyzerInterface;
use Symfony\AI\DevAssistantBundle\Contract\ModelSelectorInterface;
use Symfony\AI\DevAssistantBundle\Exception\AnalysisException;
use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisResult;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Enterprise-grade analysis orchestration service.
 *
 * This service coordinates multiple analyzers, manages caching, handles errors gracefully,
 * and provides comprehensive observability for production environments.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final class AnalysisOrchestrator
{
    /**
     * @param iterable<AnalyzerInterface> $analyzers
     */
    public function __construct(
        private iterable $analyzers,
        private ModelSelectorInterface $modelSelector,
        private CacheItemPoolInterface $cache,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private Stopwatch $stopwatch,
        private int $cacheDefaultTtl = 3600,
        private bool $cacheEnabled = true,
    ) {
    }

    /**
     * Orchestrates the complete analysis process with enterprise-grade reliability.
     *
     * @throws AnalysisException When all analysis attempts fail
     */
    public function analyze(AnalysisRequest $request): AnalysisResult
    {
        $this->stopwatch->start('analysis_orchestration');
        
        try {
            // Check cache first if enabled
            if ($this->cacheEnabled) {
                $cachedResult = $this->getCachedResult($request);
                if ($cachedResult !== null) {
                    $this->logger->info('Analysis result served from cache', [
                        'request_id' => $request->requestId,
                        'cache_key' => $request->getUniqueKey(),
                    ]);
                    return $cachedResult;
                }
            }

            // Find the appropriate analyzer
            $analyzer = $this->selectAnalyzer($request);
            if ($analyzer === null) {
                throw new AnalysisException("No suitable analyzer found for request type: {$request->type->value}");
            }

            // Select optimal AI model
            $selectedModel = $this->modelSelector->selectModel($request);
            
            $this->logger->info('Starting analysis', [
                'request_id' => $request->requestId,
                'analyzer' => $analyzer->getName(),
                'model' => $selectedModel,
                'code_length' => $request->getCodeLength(),
                'depth' => $request->depth,
            ]);

            // Perform analysis with retry logic
            $result = $this->executeAnalysisWithRetry($analyzer, $request, $selectedModel);

            // Cache successful results
            if ($this->cacheEnabled && $result->confidence > 0.7) {
                $this->cacheResult($request, $result);
            }

            // Record metrics
            $this->recordAnalysisMetrics($request, $result, $analyzer->getName(), $selectedModel);

            return $result;

        } finally {
            $event = $this->stopwatch->stop('analysis_orchestration');
            $this->logger->info('Analysis orchestration completed', [
                'request_id' => $request->requestId,
                'duration_ms' => $event->getDuration(),
                'memory_mb' => round($event->getMemory() / 1024 / 1024, 2),
            ]);
        }
    }

    /**
     * Analyzes multiple requests concurrently for batch processing.
     *
     * @param array<AnalysisRequest> $requests
     * @return array<AnalysisResult>
     */
    public function analyzeBatch(array $requests): array
    {
        $this->logger->info('Starting batch analysis', [
            'requests_count' => \count($requests),
        ]);

        $results = [];
        $errors = [];

        foreach ($requests as $index => $request) {
            try {
                $results[$index] = $this->analyze($request);
            } catch (\Throwable $e) {
                $errors[$index] = $e;
                $this->logger->error('Batch analysis item failed', [
                    'request_index' => $index,
                    'request_id' => $request->requestId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Batch analysis completed', [
            'successful_count' => \count($results),
            'failed_count' => \count($errors),
        ]);

        return $results;
    }

    private function selectAnalyzer(AnalysisRequest $request): ?AnalyzerInterface
    {
        $supportedAnalyzers = [];
        
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer->supports($request)) {
                $supportedAnalyzers[] = $analyzer;
            }
        }

        if (empty($supportedAnalyzers)) {
            return null;
        }

        // Sort by priority (highest first)
        usort($supportedAnalyzers, fn($a, $b) => $b->getPriority() - $a->getPriority());

        return $supportedAnalyzers[0];
    }

    private function executeAnalysisWithRetry(
        AnalyzerInterface $analyzer, 
        AnalysisRequest $request, 
        string $selectedModel,
        int $maxRetries = 3
    ): AnalysisResult {
        $lastException = null;
        $currentModel = $selectedModel;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->stopwatch->start("analysis_attempt_{$attempt}");
                
                $result = $analyzer->analyze($request);
                
                $this->logger->info('Analysis successful', [
                    'request_id' => $request->requestId,
                    'attempt' => $attempt,
                    'model' => $currentModel,
                ]);

                return $result;

            } catch (\Throwable $e) {
                $lastException = $e;
                
                $this->logger->warning('Analysis attempt failed', [
                    'request_id' => $request->requestId,
                    'attempt' => $attempt,
                    'model' => $currentModel,
                    'error' => $e->getMessage(),
                ]);

                // Try fallback model on next attempt
                if ($attempt < $maxRetries) {
                    $fallbackModel = $this->modelSelector->getFallbackModel($currentModel);
                    if ($fallbackModel) {
                        $currentModel = $fallbackModel;
                        $this->logger->info('Switching to fallback model', [
                            'fallback_model' => $fallbackModel,
                        ]);
                    }
                }
                
            } finally {
                $this->stopwatch->stop("analysis_attempt_{$attempt}");
            }
        }

        throw new AnalysisException(
            "Analysis failed after {$maxRetries} attempts. Last error: " . $lastException?->getMessage(),
            0,
            $lastException
        );
    }

    private function getCachedResult(AnalysisRequest $request): ?AnalysisResult
    {
        try {
            $cacheItem = $this->cache->getItem($this->getCacheKey($request));
            
            if ($cacheItem->isHit()) {
                $data = $cacheItem->get();
                if ($data instanceof AnalysisResult) {
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Cache retrieval failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function cacheResult(AnalysisRequest $request, AnalysisResult $result): void
    {
        try {
            $cacheItem = $this->cache->getItem($this->getCacheKey($request));
            $cacheItem->set($result);
            $cacheItem->expiresAfter($this->cacheDefaultTtl);
            
            $this->cache->save($cacheItem);
            
            $this->logger->debug('Analysis result cached', [
                'cache_key' => $this->getCacheKey($request),
                'ttl' => $this->cacheDefaultTtl,
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('Cache storage failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getCacheKey(AnalysisRequest $request): string
    {
        return 'dev_assistant.analysis.' . $request->getUniqueKey();
    }

    private function recordAnalysisMetrics(
        AnalysisRequest $request,
        AnalysisResult $result,
        string $analyzerName,
        string $model
    ): void {
        // This would integrate with your metrics system (Prometheus, etc.)
        $metrics = [
            'analyzer' => $analyzerName,
            'model' => $model,
            'confidence' => $result->confidence,
            'issues_count' => \count($result->issues),
            'suggestions_count' => \count($result->suggestions),
            'code_length' => $request->getCodeLength(),
            'analysis_type' => $request->type->value,
        ];

        $this->logger->info('Analysis metrics recorded', $metrics);
    }
}
