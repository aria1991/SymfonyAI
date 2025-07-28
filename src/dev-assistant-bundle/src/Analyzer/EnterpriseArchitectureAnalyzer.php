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
 * Enterprise-grade architecture analyzer with advanced AI reasoning capabilities.
 *
 * This analyzer evaluates architectural decisions, design patterns, SOLID principles,
 * dependency management, and system design quality using sophisticated AI models
 * with proper enterprise patterns implementation.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class EnterpriseArchitectureAnalyzer implements AnalyzerInterface
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
        return $type === AnalysisType::ARCHITECTURE;
    }

    public function getName(): string
    {
        return 'enterprise_architecture_analyzer';
    }

    public function getPriority(): int
    {
        return 90; // High priority for architecture analysis
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
            throw new AnalysisException('Rate limit exceeded for architecture analysis');
        }

        $this->stopwatch->start('architecture_analysis');

        try {
            $this->logger->info('Starting enterprise architecture analysis', [
                'request_id' => $request->id,
                'files_count' => count($request->files),
                'project_type' => $request->projectType,
            ]);

            // Check cache first
            $cacheKey = $this->generateCacheKey($request);
            $cachedResult = $this->cache->get($cacheKey, function () use ($request) {
                return $this->performArchitectureAnalysis($request);
            });

            $this->logger->info('Architecture analysis completed', [
                'request_id' => $request->id,
                'duration' => $this->stopwatch->stop('architecture_analysis')->getDuration(),
                'issues_found' => count($cachedResult->issues),
                'suggestions_count' => count($cachedResult->suggestions),
            ]);

            return $cachedResult;

        } catch (\Throwable $e) {
            $this->logger->error('Architecture analysis failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new AnalysisException(
                'Failed to perform architecture analysis: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    private function validateRequest(AnalysisRequest $request): void
    {
        if ($request->type !== AnalysisType::ARCHITECTURE) {
            throw new AnalysisException('Invalid analysis type for architecture analyzer');
        }

        if (empty($request->files)) {
            throw new AnalysisException('No files provided for architecture analysis');
        }

        // Architecture analysis requires sufficient codebase
        $totalSize = array_sum(array_map('strlen', $request->files));
        if ($totalSize < 1000) {
            throw new AnalysisException('Insufficient code for meaningful architecture analysis');
        }
    }

    private function performArchitectureAnalysis(AnalysisRequest $request): AnalysisResult
    {
        // Select optimal AI model for architecture analysis
        $modelInfo = $this->modelSelector->selectBestModel($request);
        
        $this->logger->debug('Selected AI model for architecture analysis', [
            'model' => $modelInfo['model'],
            'provider' => $modelInfo['provider'],
            'reasoning_capability' => $modelInfo['reasoning_score'],
            'estimated_cost' => $modelInfo['estimated_cost'],
        ]);

        // Generate specialized architecture analysis prompt
        $prompt = $this->templateEngine->generatePrompt(
            'architecture_analysis',
            [
                'files' => $request->files,
                'project_type' => $request->projectType,
                'focus_areas' => $this->getArchitectureFocusAreas($request),
                'analysis_depth' => $this->determineAnalysisDepth($request),
                'design_patterns' => $this->getRelevantDesignPatterns($request->projectType),
                'architectural_principles' => $this->getArchitecturalPrinciples(),
            ]
        );

        // Execute AI analysis with retry logic
        $response = $this->executeWithRetry($modelInfo, $prompt, $request);

        // Parse and validate response
        return $this->responseParser->parseArchitectureResponse($response, $request);
    }

    /**
     * @return array<string>
     */
    private function getArchitectureFocusAreas(AnalysisRequest $request): array
    {
        $baseAreas = [
            'SOLID Principles Compliance',
            'Design Pattern Usage',
            'Dependency Management',
            'Separation of Concerns',
            'Coupling and Cohesion',
            'Layered Architecture',
            'Domain-Driven Design',
        ];

        // Add Symfony-specific areas if applicable
        if ($request->projectType === 'symfony' || str_contains(strtolower($request->projectType), 'symfony')) {
            $baseAreas = array_merge($baseAreas, [
                'Bundle Organization',
                'Service Container Usage',
                'Event System Architecture',
                'Security Implementation',
                'Performance Considerations',
            ]);
        }

        return $baseAreas;
    }

    private function determineAnalysisDepth(AnalysisRequest $request): string
    {
        $complexity = $request->getCodeComplexityEstimate();
        
        return match (true) {
            $complexity >= 8 => 'comprehensive',
            $complexity >= 5 => 'detailed',
            $complexity >= 3 => 'standard',
            default => 'basic',
        };
    }

    /**
     * @return array<string>
     */
    private function getRelevantDesignPatterns(string $projectType): array
    {
        $corePatterns = [
            'Strategy Pattern',
            'Factory Pattern',
            'Observer Pattern',
            'Decorator Pattern',
            'Command Pattern',
            'Template Method',
            'Dependency Injection',
        ];

        // Add framework-specific patterns
        if (str_contains(strtolower($projectType), 'symfony')) {
            $corePatterns = array_merge($corePatterns, [
                'Service Locator',
                'Event Dispatcher',
                'Front Controller',
                'Data Transfer Object',
                'Repository Pattern',
            ]);
        }

        return $corePatterns;
    }

    /**
     * @return array<string>
     */
    private function getArchitecturalPrinciples(): array
    {
        return [
            'Single Responsibility Principle (SRP)',
            'Open/Closed Principle (OCP)',
            'Liskov Substitution Principle (LSP)',
            'Interface Segregation Principle (ISP)',
            'Dependency Inversion Principle (DIP)',
            'Don\'t Repeat Yourself (DRY)',
            'Keep It Simple, Stupid (KISS)',
            'You Aren\'t Gonna Need It (YAGNI)',
            'Composition over Inheritance',
            'Principle of Least Astonishment',
        ];
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
                $this->logger->debug("Architecture analysis attempt {$attempt}/{$maxRetries}", [
                    'request_id' => $request->id,
                    'model' => $modelInfo['model'],
                ]);

                $response = $this->toolboxRunner->run($modelInfo['provider'], $modelInfo['model'], [
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.1, // Low temperature for consistent analysis
                    'max_tokens' => 4000,
                ]);

                if (empty($response)) {
                    throw new AnalysisException('Empty response from AI model');
                }

                return $response;

            } catch (\Throwable $e) {
                $lastException = $e;
                $this->logger->warning("Architecture analysis attempt {$attempt} failed", [
                    'request_id' => $request->id,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    // Exponential backoff
                    usleep(1000000 * $attempt); // 1s, 2s, 3s
                }
            }
        }

        throw new AnalysisException(
            'Architecture analysis failed after all retry attempts',
            previous: $lastException
        );
    }

    private function generateCacheKey(AnalysisRequest $request): string
    {
        return sprintf(
            'architecture_analysis_%s_%s',
            $request->getCacheKey(),
            hash('md5', $this->getName() . '|' . $request->projectType)
        );
    }
}
