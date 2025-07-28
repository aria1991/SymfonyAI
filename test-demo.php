<?php

require_once __DIR__ . '/demo/vendor/autoload.php';

use Symfony\AI\DevAssistantBundle\Analyzer\MockArchitectureAnalyzer;
use Symfony\AI\DevAssistantBundle\Analyzer\MockCodeQualityAnalyzer;
use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisType;
use Symfony\AI\DevAssistantBundle\Service\SimpleOrchestrator;

echo "🤖 AI Development Assistant - Test Demo\n";
echo "=====================================\n\n";

// Collect test files
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
echo "  Mode: Mock (No AI API calls)\n\n";

// Create analyzers
$analyzers = [
    new MockCodeQualityAnalyzer(),
    new MockArchitectureAnalyzer(),
];

$orchestrator = new SimpleOrchestrator($analyzers);

// Test Code Quality Analysis
echo "🔍 Running Code Quality Analysis\n";
echo "==================================\n";

$request = new AnalysisRequest(
    id: uniqid('test_'),
    type: AnalysisType::CODE_QUALITY,
    files: $files,
    projectType: 'symfony',
);

try {
    $result = $orchestrator->analyze($request);
    
    echo "📋 Results:\n";
    echo "  " . $result->summary . "\n\n";
    
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
    
    if (!empty($result->suggestions)) {
        echo "💡 Suggestions (" . count($result->suggestions) . "):\n";
        foreach ($result->suggestions as $suggestion) {
            $priority = strtoupper($suggestion->priority->value);
            echo "  [{$priority}] {$suggestion->title}\n";
            echo "    📝 {$suggestion->description}\n\n";
        }
    }
    
    echo "📊 Metrics:\n";
    foreach ($result->metrics as $key => $value) {
        if (is_scalar($value)) {
            echo "  {$key}: {$value}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test Architecture Analysis
echo "🔍 Running Architecture Analysis\n";
echo "=================================\n";

$request = new AnalysisRequest(
    id: uniqid('test_'),
    type: AnalysisType::ARCHITECTURE,
    files: $files,
    projectType: 'symfony',
);

try {
    $result = $orchestrator->analyze($request);
    
    echo "📋 Results:\n";
    echo "  " . $result->summary . "\n\n";
    
    if (!empty($result->issues)) {
        echo "🚨 Architecture Issues Found (" . count($result->issues) . "):\n";
        foreach ($result->issues as $issue) {
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
    
    if (!empty($result->suggestions)) {
        echo "💡 Architecture Suggestions (" . count($result->suggestions) . "):\n";
        foreach ($result->suggestions as $suggestion) {
            $priority = strtoupper($suggestion->priority->value);
            echo "  [{$priority}] {$suggestion->title}\n";
            echo "    📝 {$suggestion->description}\n\n";
        }
    }
    
    echo "📊 Architecture Metrics:\n";
    foreach ($result->metrics as $key => $value) {
        if (is_scalar($value)) {
            echo "  {$key}: {$value}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Demo completed successfully!\n";
echo "\nThis demonstrates the enterprise AI development assistant capability.\n";
echo "In production, this would use real AI models like GPT-4 or Claude for\n";
echo "sophisticated analysis with detailed explanations and context-aware suggestions.\n";
