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

use Psr\Log\LoggerInterface;
use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisResult;
use Symfony\AI\DevAssistantBundle\Model\AnalysisType;
use Symfony\AI\DevAssistantBundle\Model\Issue;
use Symfony\AI\DevAssistantBundle\Model\IssueCategory;
use Symfony\AI\DevAssistantBundle\Model\Priority;
use Symfony\AI\DevAssistantBundle\Model\Severity;
use Symfony\AI\DevAssistantBundle\Model\Suggestion;
use Symfony\AI\DevAssistantBundle\Model\SuggestionType;

/**
 * Professional AI response parser with comprehensive error handling.
 *
 * This service handles the complex task of parsing AI responses into structured
 * domain objects, with robust error recovery and validation.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class ResponseParser
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function parseCodeQualityResponse(string $response, AnalysisRequest $request): AnalysisResult
    {
        try {
            $data = $this->extractAndParseJson($response);
            
            return new AnalysisResult(
                type: AnalysisType::CODE_QUALITY,
                summary: $data['summary'] ?? 'Code quality analysis completed',
                issues: $this->parseIssues($data['issues'] ?? []),
                suggestions: $this->parseSuggestions($data['suggestions'] ?? []),
                metrics: $data['metrics'] ?? [],
                overallSeverity: $this->calculateOverallSeverity($data['issues'] ?? []),
                confidence: (float) ($data['confidence'] ?? 0.8),
                analyzedAt: new \DateTimeImmutable(),
            );
            
        } catch (\Throwable $e) {
            $this->logger->error('Failed to parse AI response', [
                'error' => $e->getMessage(),
                'response_length' => \strlen($response),
            ]);
            
            return $this->createFallbackResult($request, $response);
        }
    }

    public function parseArchitectureResponse(string $response, AnalysisRequest $request): AnalysisResult
    {
        try {
            $data = $this->extractAndParseJson($response);
            
            return new AnalysisResult(
                type: AnalysisType::ARCHITECTURE,
                summary: $data['summary'] ?? 'Architecture analysis completed',
                issues: $this->parseIssues($data['issues'] ?? []),
                suggestions: $this->parseSuggestions($data['suggestions'] ?? []),
                metrics: array_merge($data['metrics'] ?? [], [
                    'architectural_score' => $data['architectural_score'] ?? null,
                    'solid_compliance' => $data['solid_compliance'] ?? null,
                    'design_patterns_used' => $data['design_patterns_used'] ?? [],
                    'coupling_score' => $data['coupling_score'] ?? null,
                    'cohesion_score' => $data['cohesion_score'] ?? null,
                ]),
                overallSeverity: $this->calculateOverallSeverity($data['issues'] ?? []),
                confidence: (float) ($data['confidence'] ?? 0.85),
                analyzedAt: new \DateTimeImmutable(),
            );
            
        } catch (\Throwable $e) {
            $this->logger->error('Failed to parse architecture analysis response', [
                'error' => $e->getMessage(),
                'response_length' => \strlen($response),
            ]);
            
            return $this->createFallbackResult($request, $response);
        }
    }

    public function parsePerformanceResponse(string $response, AnalysisRequest $request): AnalysisResult
    {
        try {
            $data = $this->extractAndParseJson($response);
            
            return new AnalysisResult(
                type: AnalysisType::PERFORMANCE,
                summary: $data['summary'] ?? 'Performance analysis completed',
                issues: $this->parseIssues($data['issues'] ?? []),
                suggestions: $this->parseSuggestions($data['suggestions'] ?? []),
                metrics: array_merge($data['metrics'] ?? [], [
                    'performance_score' => $data['performance_score'] ?? null,
                    'complexity_metrics' => $data['complexity_metrics'] ?? [],
                    'bottlenecks_identified' => $data['bottlenecks_identified'] ?? [],
                    'optimization_potential' => $data['optimization_potential'] ?? null,
                ]),
                overallSeverity: $this->calculateOverallSeverity($data['issues'] ?? []),
                confidence: (float) ($data['confidence'] ?? 0.8),
                analyzedAt: new \DateTimeImmutable(),
            );
            
        } catch (\Throwable $e) {
            $this->logger->error('Failed to parse performance analysis response', [
                'error' => $e->getMessage(),
                'response_length' => \strlen($response),
            ]);
            
            return $this->createFallbackResult($request, $response);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractAndParseJson(string $response): array
    {
        // Try to extract JSON from various formats
        $patterns = [
            '/```json\s*(\{.*?\})\s*```/s',  // JSON code blocks
            '/(\{.*\})/s',                   // Direct JSON
            '/<json>(.*?)<\/json>/s',        // XML-style tags
        ];

        $jsonContent = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                $jsonContent = $matches[1];
                break;
            }
        }

        if ($jsonContent === null) {
            $jsonContent = $response; // Fallback to entire response
        }

        $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        
        if (!is_array($data)) {
            throw new \JsonException('Response is not a valid JSON object');
        }

        return $data;
    }

    /**
     * @param array<array<string, mixed>> $issuesData
     * @return array<Issue>
     */
    private function parseIssues(array $issuesData): array
    {
        $issues = [];
        
        foreach ($issuesData as $index => $issueData) {
            try {
                $issues[] = new Issue(
                    id: $issueData['id'] ?? uniqid('issue_'),
                    title: $issueData['title'] ?? "Issue #" . ($index + 1),
                    description: $issueData['description'] ?? '',
                    severity: $this->parseSeverity($issueData['severity'] ?? 'medium'),
                    category: $this->parseIssueCategory($issueData['category'] ?? 'best_practice'),
                    file: $issueData['file'] ?? null,
                    line: $this->parseNullableInt($issueData['line'] ?? null),
                    column: $this->parseNullableInt($issueData['column'] ?? null),
                    rule: $issueData['rule'] ?? null,
                    fixSuggestion: $issueData['fix_suggestion'] ?? $issueData['fixSuggestion'] ?? null,
                    codeSnippet: $issueData['code_snippet'] ?? $issueData['codeSnippet'] ?? null,
                    metadata: [
                        'reasoning' => $issueData['reasoning'] ?? null,
                        'impact' => $issueData['impact'] ?? null,
                        'confidence' => $issueData['confidence'] ?? null,
                        'ai_generated' => true,
                    ],
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to parse issue', [
                    'issue_index' => $index,
                    'issue_data' => $issueData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $issues;
    }

    /**
     * @param array<array<string, mixed>> $suggestionsData
     * @return array<Suggestion>
     */
    private function parseSuggestions(array $suggestionsData): array
    {
        $suggestions = [];
        
        foreach ($suggestionsData as $index => $suggestionData) {
            try {
                $suggestions[] = new Suggestion(
                    id: $suggestionData['id'] ?? uniqid('suggestion_'),
                    title: $suggestionData['title'] ?? "Suggestion #" . ($index + 1),
                    description: $suggestionData['description'] ?? '',
                    type: $this->parseSuggestionType($suggestionData['type'] ?? 'code_cleanup'),
                    priority: $this->parsePriority($suggestionData['priority'] ?? 'medium'),
                    implementation: $suggestionData['implementation'] ?? null,
                    reasoning: $suggestionData['reasoning'] ?? null,
                    exampleCode: $suggestionData['example_code'] ?? $suggestionData['exampleCode'] ?? null,
                    benefits: $suggestionData['benefits'] ?? [],
                    estimatedImpact: $this->parseNullableFloat($suggestionData['estimated_impact'] ?? $suggestionData['estimatedImpact'] ?? null),
                    metadata: [
                        'difficulty' => $suggestionData['difficulty'] ?? null,
                        'time_estimate' => $suggestionData['time_estimate'] ?? null,
                        'ai_generated' => true,
                    ],
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to parse suggestion', [
                    'suggestion_index' => $index,
                    'suggestion_data' => $suggestionData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $suggestions;
    }

    private function parseSeverity(string $severity): Severity
    {
        try {
            return Severity::from(strtolower($severity));
        } catch (\ValueError) {
            $this->logger->warning('Invalid severity value', ['severity' => $severity]);
            return Severity::MEDIUM;
        }
    }

    private function parseIssueCategory(string $category): IssueCategory
    {
        try {
            return IssueCategory::from(strtolower($category));
        } catch (\ValueError) {
            $this->logger->warning('Invalid issue category', ['category' => $category]);
            return IssueCategory::BEST_PRACTICE;
        }
    }

    private function parseSuggestionType(string $type): SuggestionType
    {
        try {
            return SuggestionType::from(strtolower($type));
        } catch (\ValueError) {
            $this->logger->warning('Invalid suggestion type', ['type' => $type]);
            return SuggestionType::CODE_CLEANUP;
        }
    }

    private function parsePriority(string $priority): Priority
    {
        try {
            return Priority::from(strtolower($priority));
        } catch (\ValueError) {
            $this->logger->warning('Invalid priority value', ['priority' => $priority]);
            return Priority::MEDIUM;
        }
    }

    private function parseNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        return (int) $value;
    }

    private function parseNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        return (float) $value;
    }

    /**
     * @param array<array<string, mixed>> $issuesData
     */
    private function calculateOverallSeverity(array $issuesData): Severity
    {
        if (empty($issuesData)) {
            return Severity::INFO;
        }

        $maxSeverity = Severity::INFO;
        foreach ($issuesData as $issueData) {
            $severity = $this->parseSeverity($issueData['severity'] ?? 'medium');
            if ($severity->getPriority() > $maxSeverity->getPriority()) {
                $maxSeverity = $severity;
            }
        }

        return $maxSeverity;
    }

    private function createFallbackResult(AnalysisRequest $request, string $response): AnalysisResult
    {
        $this->logger->info('Creating fallback analysis result');
        
        return new AnalysisResult(
            type: $request->type,
            summary: 'Analysis completed with parsing errors. Please review manually.',
            issues: [],
            suggestions: [],
            metrics: [
                'parse_error' => true,
                'response_length' => \strlen($response),
                'fallback_result' => true,
            ],
            overallSeverity: Severity::INFO,
            confidence: 0.1, // Very low confidence for fallback
            analyzedAt: new \DateTimeImmutable(),
        );
    }
}
