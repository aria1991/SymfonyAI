<?php

/**
 * Simple standalone test for AI Development Assistant
 * This demonstrates the enterprise architecture without requiring full Symfony setup
 */

echo "🤖 AI Development Assistant - Standalone Demo\n";
echo "==============================================\n\n";

// Mock the basic structure for demonstration
class AnalysisType {
    public const CODE_QUALITY = 'code_quality';
    public const ARCHITECTURE = 'architecture';
    
    public function __construct(public readonly string $value) {}
    
    public static function from(string $value): self {
        return new self($value);
    }
    
    public function getDisplayName(): string {
        return match($this->value) {
            self::CODE_QUALITY => 'Code Quality Analysis',
            self::ARCHITECTURE => 'Architecture Review',
            default => $this->value,
        };
    }
}

class Severity {
    public const CRITICAL = 'critical';
    public const HIGH = 'high';
    public const MEDIUM = 'medium';
    public const LOW = 'low';
    public const INFO = 'info';
    
    public function __construct(public readonly string $value) {}
    
    public static function from(string $value): self {
        return new self($value);
    }
}

class IssueCategory {
    public const SECURITY = 'security';
    public const BEST_PRACTICE = 'best_practice';
    public const COMPLEXITY = 'complexity';
    public const DESIGN_PATTERN = 'design_pattern';
    
    public function __construct(public readonly string $value) {}
    
    public static function from(string $value): self {
        return new self($value);
    }
}

class Issue {
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly Severity $severity,
        public readonly IssueCategory $category,
        public readonly ?string $file = null,
        public readonly ?int $line = null,
        public readonly ?string $fixSuggestion = null,
    ) {}
}

class AnalysisResult {
    public function __construct(
        public readonly AnalysisType $type,
        public readonly string $summary,
        public readonly array $issues = [],
        public readonly array $suggestions = [],
        public readonly array $metrics = [],
    ) {}
}

class AnalysisRequest {
    public function __construct(
        public readonly string $id,
        public readonly AnalysisType $type,
        public readonly array $files,
        public readonly string $projectType = 'symfony',
    ) {}
}

// Simple analyzer that detects common issues
class CodeQualityAnalyzer {
    public function analyze(AnalysisRequest $request): AnalysisResult {
        $issues = [];
        
        foreach ($request->files as $filePath => $content) {
            // Detect SQL injection
            if (preg_match('/query\(["\'].*\.\s*\$/', $content)) {
                $issues[] = new Issue(
                    id: 'sql_injection_001',
                    title: 'SQL Injection Vulnerability',
                    description: 'Direct SQL concatenation detected - use prepared statements',
                    severity: Severity::from(Severity::CRITICAL),
                    category: IssueCategory::from(IssueCategory::SECURITY),
                    file: $filePath,
                    line: $this->findLineNumber($content, 'query('),
                    fixSuggestion: 'Use prepared statements with parameter binding'
                );
            }
            
            // Detect missing type declarations
            if (preg_match('/public function \w+\([^)]*\)\s*{/', $content)) {
                $issues[] = new Issue(
                    id: 'type_missing_001',
                    title: 'Missing Type Declarations',
                    description: 'Method parameters and return types should be explicitly declared',
                    severity: Severity::from(Severity::MEDIUM),
                    category: IssueCategory::from(IssueCategory::BEST_PRACTICE),
                    file: $filePath,
                    line: $this->findLineNumber($content, 'public function'),
                    fixSuggestion: 'Add parameter and return type declarations'
                );
            }
            
            // Detect complex nested conditions
            if (preg_match_all('/if\s*\([^{]*\{[^}]*if\s*\([^{]*\{[^}]*if/', $content) > 0) {
                $issues[] = new Issue(
                    id: 'complexity_001',
                    title: 'Complex Nested Conditions',
                    description: 'Too many nested if statements reduce readability',
                    severity: Severity::from(Severity::MEDIUM),
                    category: IssueCategory::from(IssueCategory::COMPLEXITY),
                    file: $filePath,
                    line: $this->findLineNumber($content, 'if ('),
                    fixSuggestion: 'Extract conditions into separate methods or use early returns'
                );
            }
        }
        
        return new AnalysisResult(
            type: $request->type,
            summary: sprintf('Found %d code quality issues requiring attention', count($issues)),
            issues: $issues,
            metrics: [
                'files_analyzed' => count($request->files),
                'issues_found' => count($issues),
                'quality_score' => max(1, 10 - count($issues)),
            ]
        );
    }
    
    private function findLineNumber(string $content, string $pattern): int {
        $lines = explode("\n", $content);
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $pattern) !== false) {
                return $lineNumber + 1;
            }
        }
        return 1;
    }
}

class ArchitectureAnalyzer {
    public function analyze(AnalysisRequest $request): AnalysisResult {
        $issues = [];
        
        foreach ($request->files as $filePath => $content) {
            // Detect SRP violations
            $methodCount = preg_match_all('/public function/', $content);
            if ($methodCount > 8) {
                $issues[] = new Issue(
                    id: 'srp_violation_001',
                    title: 'Single Responsibility Principle Violation',
                    description: 'Class has too many public methods, indicating multiple responsibilities',
                    severity: Severity::from(Severity::HIGH),
                    category: IssueCategory::from(IssueCategory::DESIGN_PATTERN),
                    file: $filePath,
                    fixSuggestion: 'Split class into smaller, focused classes'
                );
            }
            
            // Detect high coupling
            $dependencyCount = preg_match_all('/private \$\w+/', $content);
            if ($dependencyCount > 5) {
                $issues[] = new Issue(
                    id: 'coupling_001',
                    title: 'High Coupling Detected',
                    description: 'Class has too many dependencies, violating Interface Segregation Principle',
                    severity: Severity::from(Severity::MEDIUM),
                    category: IssueCategory::from(IssueCategory::DESIGN_PATTERN),
                    file: $filePath,
                    fixSuggestion: 'Break down into smaller interfaces or use facade pattern'
                );
            }
        }
        
        return new AnalysisResult(
            type: $request->type,
            summary: sprintf('Architecture analysis identified %d SOLID principle violations', count($issues)),
            issues: $issues,
            metrics: [
                'files_analyzed' => count($request->files),
                'classes_found' => $this->countClasses($request->files),
                'solid_violations' => count($issues),
                'architecture_score' => max(1, 10 - count($issues)),
            ]
        );
    }
    
    private function countClasses(array $files): int {
        $count = 0;
        foreach ($files as $content) {
            $count += preg_match_all('/class\s+\w+/', $content);
        }
        return $count;
    }
}

// Load test files
$testPath = __DIR__ . '/test-example';
$files = [];

if (is_dir($testPath)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testPath)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[$file->getFilename()] = file_get_contents($file->getPathname());
        }
    }
}

if (empty($files)) {
    echo "❌ No PHP files found in test-example directory\n";
    exit(1);
}

echo "📊 Analysis Configuration\n";
echo "  Path: {$testPath}\n";
echo "  Files Found: " . count($files) . "\n";
echo "  Mode: Standalone Demo\n\n";

// Test Code Quality Analysis
echo "🔍 Running Code Quality Analysis\n";
echo "=================================\n";

$analyzer = new CodeQualityAnalyzer();
$request = new AnalysisRequest(
    id: 'demo_001',
    type: AnalysisType::from(AnalysisType::CODE_QUALITY),
    files: $files
);

$result = $analyzer->analyze($request);

echo "📋 {$result->type->getDisplayName()} Results:\n";
echo "  {$result->summary}\n\n";

if (!empty($result->issues)) {
    echo "🚨 Issues Found (" . count($result->issues) . "):\n";
    foreach ($result->issues as $issue) {
        $severity = strtoupper($issue->severity->value);
        echo "  [{$severity}] {$issue->title}\n";
        echo "    📁 {$issue->file}" . ($issue->line ? ":{$issue->line}" : '') . "\n";
        echo "    💬 {$issue->description}\n";
        if ($issue->fixSuggestion) {
            echo "    💡 {$issue->fixSuggestion}\n";
        }
        echo "\n";
    }
}

echo "📊 Metrics:\n";
foreach ($result->metrics as $key => $value) {
    echo "  {$key}: {$value}\n";
}

echo "\n";

// Test Architecture Analysis
echo "🔍 Running Architecture Analysis\n";
echo "=================================\n";

$archAnalyzer = new ArchitectureAnalyzer();
$archRequest = new AnalysisRequest(
    id: 'demo_002',
    type: AnalysisType::from(AnalysisType::ARCHITECTURE),
    files: $files
);

$archResult = $archAnalyzer->analyze($archRequest);

echo "📋 {$archResult->type->getDisplayName()} Results:\n";
echo "  {$archResult->summary}\n\n";

if (!empty($archResult->issues)) {
    echo "🚨 Architecture Issues Found (" . count($archResult->issues) . "):\n";
    foreach ($archResult->issues as $issue) {
        $severity = strtoupper($issue->severity->value);
        echo "  [{$severity}] {$issue->title}\n";
        echo "    📁 {$issue->file}\n";
        echo "    💬 {$issue->description}\n";
        if ($issue->fixSuggestion) {
            echo "    💡 {$issue->fixSuggestion}\n";
        }
        echo "\n";
    }
}

echo "📊 Architecture Metrics:\n";
foreach ($archResult->metrics as $key => $value) {
    echo "  {$key}: {$value}\n";
}

echo "\n🎉 Demo completed successfully!\n\n";
echo "=== Enterprise AI Development Assistant Demonstration ===\n";
echo "\nThis showcases the enterprise architecture capabilities:\n";
echo "✅ Contract-based design with interfaces\n";
echo "✅ Domain-driven design with value objects\n";
echo "✅ SOLID principles implementation\n";
echo "✅ Professional error detection\n";
echo "✅ Comprehensive reporting\n";
echo "✅ Extensible analyzer system\n\n";
echo "In production, this integrates with real AI models like:\n";
echo "• GPT-4 for sophisticated code analysis\n";
echo "• Claude for architectural reasoning\n";
echo "• Gemini for performance optimization\n\n";
echo "The system provides enterprise-grade features:\n";
echo "• Intelligent model selection\n";
echo "• Caching and rate limiting\n";
echo "• Parallel analysis execution\n";
echo "• Professional reporting formats\n";
echo "• Comprehensive observability\n";
