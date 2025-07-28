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
 * Mock architecture analyzer for testing without AI API calls.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class MockArchitectureAnalyzer implements AnalyzerInterface
{
    public function supports(AnalysisType $type): bool
    {
        return $type === AnalysisType::ARCHITECTURE;
    }

    public function getName(): string
    {
        return 'mock_architecture_analyzer';
    }

    public function getPriority(): int
    {
        return 90;
    }

    public function analyze(AnalysisRequest $request): AnalysisResult
    {
        $issues = [];
        $suggestions = [];

        foreach ($request->files as $filePath => $content) {
            // Detect SRP violations (too many responsibilities)
            if (preg_match_all('/public function/', $content) > 10) {
                $issues[] = new Issue(
                    id: 'arch_001',
                    title: 'Single Responsibility Principle Violation',
                    description: 'Class has too many public methods, indicating multiple responsibilities',
                    severity: Severity::HIGH,
                    category: IssueCategory::DESIGN_PATTERN,
                    file: $filePath,
                    rule: 'SRP_VIOLATION',
                    fixSuggestion: 'Split class into smaller, focused classes',
                );
            }

            // Detect tight coupling (too many dependencies)
            $constructorParams = preg_match_all('/private \$\w+/', $content);
            if ($constructorParams > 5) {
                $issues[] = new Issue(
                    id: 'arch_002',
                    title: 'High Coupling Detected',
                    description: 'Class has too many dependencies, violating Interface Segregation Principle',
                    severity: Severity::MEDIUM,
                    category: IssueCategory::DESIGN_PATTERN,
                    file: $filePath,
                    rule: 'ISP_VIOLATION',
                    fixSuggestion: 'Break down into smaller interfaces or use facade pattern',
                );
            }

            // Detect missing interfaces
            if (strpos($content, 'class') !== false && strpos($content, 'implements') === false) {
                $suggestions[] = new Suggestion(
                    id: 'arch_sugg_001',
                    title: 'Consider Interface Implementation',
                    description: 'Add interfaces to improve testability and flexibility',
                    type: SuggestionType::DESIGN_PATTERN,
                    priority: Priority::MEDIUM,
                    implementation: 'Create interface and implement it in the class',
                );
            }
        }

        return new AnalysisResult(
            type: AnalysisType::ARCHITECTURE,
            summary: sprintf(
                'Architecture analysis identified %d SOLID principle violations and %d improvement opportunities',
                count($issues),
                count($suggestions)
            ),
            issues: $issues,
            suggestions: $suggestions,
            metrics: [
                'files_analyzed' => count($request->files),
                'classes_found' => $this->countClasses($request->files),
                'interfaces_found' => $this->countInterfaces($request->files),
                'solid_score' => max(1, 10 - count($issues)),
                'coupling_score' => 7.5,
                'cohesion_score' => 8.0,
            ],
            overallSeverity: $this->calculateOverallSeverity($issues),
            confidence: 0.80,
            analyzedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * @param array<string, string> $files
     */
    private function countClasses(array $files): int
    {
        $count = 0;
        foreach ($files as $content) {
            $count += preg_match_all('/class\s+\w+/', $content);
        }
        return $count;
    }

    /**
     * @param array<string, string> $files
     */
    private function countInterfaces(array $files): int
    {
        $count = 0;
        foreach ($files as $content) {
            $count += preg_match_all('/interface\s+\w+/', $content);
        }
        return $count;
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
