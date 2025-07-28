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

use Symfony\AI\DevAssistantBundle\Contract\AnalyzerInterface;
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
 * Mock analyzer for testing without AI API calls.
 * 
 * This analyzer simulates enterprise-grade analysis by detecting
 * common code quality issues using static analysis patterns.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class MockCodeQualityAnalyzer implements AnalyzerInterface
{
    public function supports(AnalysisType $type): bool
    {
        return $type === AnalysisType::CODE_QUALITY;
    }

    public function getName(): string
    {
        return 'mock_code_quality_analyzer';
    }

    public function getPriority(): int
    {
        return 50;
    }

    public function analyze(AnalysisRequest $request): AnalysisResult
    {
        $issues = [];
        $suggestions = [];

        foreach ($request->files as $filePath => $content) {
            // Detect missing type declarations
            if (preg_match('/public function \w+\([^)]*\)\s*{/', $content)) {
                $issues[] = new Issue(
                    id: 'cq_001',
                    title: 'Missing Type Declarations',
                    description: 'Method parameters and return types should be explicitly declared',
                    severity: Severity::MEDIUM,
                    category: IssueCategory::BEST_PRACTICE,
                    file: $filePath,
                    line: $this->findLineNumber($content, 'public function'),
                    rule: 'TYPE_DECLARATION_MISSING',
                    fixSuggestion: 'Add parameter and return type declarations',
                );
            }

            // Detect SQL injection vulnerabilities
            if (preg_match('/query\(["\'].*\.\s*\$/', $content)) {
                $issues[] = new Issue(
                    id: 'cq_002',
                    title: 'SQL Injection Vulnerability',
                    description: 'Direct SQL concatenation detected - use prepared statements',
                    severity: Severity::CRITICAL,
                    category: IssueCategory::SECURITY,
                    file: $filePath,
                    line: $this->findLineNumber($content, 'query('),
                    rule: 'SQL_INJECTION',
                    fixSuggestion: 'Use prepared statements with parameter binding',
                );
            }

            // Detect complex nested conditions
            if (preg_match_all('/if\s*\([^{]*\{[^}]*if\s*\([^{]*\{[^}]*if/', $content) > 0) {
                $issues[] = new Issue(
                    id: 'cq_003',
                    title: 'Complex Nested Conditions',
                    description: 'Too many nested if statements reduce readability',
                    severity: Severity::MEDIUM,
                    category: IssueCategory::COMPLEXITY,
                    file: $filePath,
                    line: $this->findLineNumber($content, 'if ('),
                    rule: 'COMPLEX_CONDITIONS',
                    fixSuggestion: 'Extract conditions into separate methods or use early returns',
                );
            }
        }

        // Generate suggestions
        $suggestions[] = new Suggestion(
            id: 'cq_sugg_001',
            title: 'Implement Strict Types',
            description: 'Add declare(strict_types=1) to all PHP files',
            type: SuggestionType::CODE_CLEANUP,
            priority: Priority::MEDIUM,
            implementation: 'Add declare(strict_types=1); after opening PHP tag',
            reasoning: 'Strict typing prevents type coercion bugs',
        );

        return new AnalysisResult(
            type: AnalysisType::CODE_QUALITY,
            summary: sprintf(
                'Code quality analysis found %d issues and %d suggestions for improvement',
                count($issues),
                count($suggestions)
            ),
            issues: $issues,
            suggestions: $suggestions,
            metrics: [
                'files_analyzed' => count($request->files),
                'total_lines' => array_sum(array_map(fn($c) => substr_count($c, "\n"), $request->files)),
                'issues_found' => count($issues),
                'quality_score' => max(1, 10 - count($issues)),
            ],
            overallSeverity: $this->calculateOverallSeverity($issues),
            confidence: 0.85,
            analyzedAt: new \DateTimeImmutable(),
        );
    }

    private function findLineNumber(string $content, string $pattern): int
    {
        $lines = explode("\n", $content);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $pattern) !== false) {
                return $lineNumber + 1;
            }
        }
        return 1;
    }

    /**
     * @param array<Issue> $issues
     */
    private function calculateOverallSeverity(array $issues): Severity
    {
        if (empty($issues)) {
            return Severity::INFO;
        }

        $maxSeverity = Severity::INFO;
        foreach ($issues as $issue) {
            if ($issue->severity->getPriority() > $maxSeverity->getPriority()) {
                $maxSeverity = $issue->severity;
            }
        }

        return $maxSeverity;
    }
}
