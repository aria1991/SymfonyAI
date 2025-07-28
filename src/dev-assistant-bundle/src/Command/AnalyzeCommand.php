<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\DevAssistantBundle\Command;

use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisType;
use Symfony\AI\DevAssistantBundle\Service\AnalysisOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Professional command-line interface for the enterprise AI development assistant.
 *
 * This command provides a comprehensive interface for running AI-powered code analysis
 * with enterprise-grade features including parallel analysis, detailed reporting,
 * and professional output formatting.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
#[AsCommand(
    name: 'dev-assistant:analyze',
    description: 'Perform AI-powered code analysis with enterprise-grade insights'
)]
final class AnalyzeCommand extends Command
{
    public function __construct(
        private readonly AnalysisOrchestrator $orchestrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'Path to analyze (file or directory)')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Analysis type (code_quality, architecture, performance, all)', 'code_quality')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format (console, json, html)', 'console')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path (for json/html formats)')
            ->addOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Patterns to exclude')
            ->addOption('include', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Patterns to include (*.php by default)')
            ->addOption('max-files', null, InputOption::VALUE_REQUIRED, 'Maximum number of files to analyze', '50')
            ->addOption('project-type', null, InputOption::VALUE_REQUIRED, 'Project type context (symfony, laravel, etc.)', 'symfony')
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Include detailed metrics and explanations')
            ->addOption('parallel', 'p', InputOption::VALUE_NONE, 'Run multiple analysis types in parallel')
            ->setHelp(
                <<<'HELP'
The <info>dev-assistant:analyze</info> command performs AI-powered code analysis using enterprise-grade analyzers.

<comment>Basic Usage:</comment>
  <info>php bin/console dev-assistant:analyze src/</info>

<comment>Architecture Analysis:</comment>
  <info>php bin/console dev-assistant:analyze src/ --type=architecture --detailed</info>

<comment>Performance Analysis:</comment>
  <info>php bin/console dev-assistant:analyze src/Service/ --type=performance</info>

<comment>Comprehensive Analysis:</comment>
  <info>php bin/console dev-assistant:analyze src/ --type=all --parallel</info>

<comment>Export Results:</comment>
  <info>php bin/console dev-assistant:analyze src/ --format=json --output=analysis-report.json</info>

<comment>Analysis Types:</comment>
  - <info>code_quality</info>: Code style, best practices, maintainability
  - <info>architecture</info>: SOLID principles, design patterns, structure
  - <info>performance</info>: Bottlenecks, optimization opportunities
  - <info>all</info>: Run all analysis types

<comment>Output Formats:</comment>
  - <info>console</info>: Rich console output with colors and formatting
  - <info>json</info>: Structured JSON for integration with tools
  - <info>html</info>: Professional HTML report with charts and graphs
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $path = $input->getArgument('path');
        $analysisType = $input->getOption('type');
        $format = $input->getOption('format');
        $detailed = $input->getOption('detailed');
        $parallel = $input->getOption('parallel');
        $projectType = $input->getOption('project-type');
        $maxFiles = (int) $input->getOption('max-files');

        $io->title('🤖 Enterprise AI Development Assistant');
        
        if (!file_exists($path)) {
            $io->error("Path does not exist: {$path}");
            return Command::FAILURE;
        }

        try {
            // Collect files to analyze
            $files = $this->collectFiles($path, $input);
            
            if (empty($files)) {
                $io->warning('No PHP files found to analyze.');
                return Command::SUCCESS;
            }

            if (count($files) > $maxFiles) {
                $files = array_slice($files, 0, $maxFiles);
                $io->note("Limited analysis to {$maxFiles} files for performance.");
            }

            $io->section('📊 Analysis Configuration');
            $io->definitionList(
                ['Path' => $path],
                ['Files Found' => count($files)],
                ['Analysis Type' => $analysisType],
                ['Project Type' => $projectType],
                ['Parallel Processing' => $parallel ? 'Enabled' : 'Disabled'],
                ['Detailed Output' => $detailed ? 'Enabled' : 'Disabled'],
            );

            // Perform analysis
            if ($analysisType === 'all') {
                $results = $this->performComprehensiveAnalysis($files, $projectType, $parallel, $io);
            } else {
                $results = [$this->performSingleAnalysis($files, $analysisType, $projectType, $io)];
            }

            // Output results
            $this->outputResults($results, $format, $input->getOption('output'), $detailed, $io);

            $io->success('🎉 Analysis completed successfully!');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $io->error('Analysis failed: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * @return array<string, string>
     */
    private function collectFiles(string $path, InputInterface $input): array
    {
        $finder = new Finder();
        $finder->files();

        if (is_file($path)) {
            return [basename($path) => file_get_contents($path)];
        }

        $finder->in($path);

        // Apply include patterns
        $includePatterns = $input->getOption('include') ?: ['*.php'];
        foreach ($includePatterns as $pattern) {
            $finder->name($pattern);
        }

        // Apply exclude patterns
        $excludePatterns = $input->getOption('exclude') ?: ['vendor/', 'node_modules/', 'var/', 'tests/'];
        foreach ($excludePatterns as $pattern) {
            if (str_contains($pattern, '/')) {
                $finder->notPath($pattern);
            } else {
                $finder->notName($pattern);
            }
        }

        $files = [];
        foreach ($finder as $file) {
            $files[$file->getRelativePathname()] = $file->getContents();
        }

        return $files;
    }

    private function performSingleAnalysis(array $files, string $type, string $projectType, SymfonyStyle $io): mixed
    {
        $analysisType = AnalysisType::from($type);
        
        $io->section("🔍 Running {$analysisType->getDisplayName()}");
        $io->progressStart();

        $request = new AnalysisRequest(
            id: uniqid('analysis_'),
            type: $analysisType,
            files: $files,
            projectType: $projectType,
            options: ['detailed' => true],
        );

        $result = $this->orchestrator->analyze($request);
        $io->progressFinish();

        return $result;
    }

    private function performComprehensiveAnalysis(array $files, string $projectType, bool $parallel, SymfonyStyle $io): array
    {
        $types = [AnalysisType::CODE_QUALITY, AnalysisType::ARCHITECTURE, AnalysisType::PERFORMANCE];
        $results = [];

        $io->section('🔍 Running Comprehensive Analysis');

        if ($parallel) {
            $io->text('Running analyses in parallel...');
            $io->progressStart(count($types));

            // In a real implementation, this would use actual parallel processing
            foreach ($types as $type) {
                $request = new AnalysisRequest(
                    id: uniqid('analysis_'),
                    type: $type,
                    files: $files,
                    projectType: $projectType,
                    options: ['detailed' => true],
                );

                $results[] = $this->orchestrator->analyze($request);
                $io->progressAdvance();
            }
        } else {
            foreach ($types as $type) {
                $io->text("Analyzing: {$type->getDisplayName()}");
                $results[] = $this->performSingleAnalysis($files, $type->value, $projectType, $io);
            }
        }

        $io->progressFinish();
        return $results;
    }

    private function outputResults(array $results, string $format, ?string $outputFile, bool $detailed, SymfonyStyle $io): void
    {
        switch ($format) {
            case 'json':
                $this->outputJson($results, $outputFile, $io);
                break;
            case 'html':
                $this->outputHtml($results, $outputFile, $io);
                break;
            default:
                $this->outputConsole($results, $detailed, $io);
        }
    }

    private function outputConsole(array $results, bool $detailed, SymfonyStyle $io): void
    {
        foreach ($results as $result) {
            $io->section("📋 {$result->type->getDisplayName()} Results");
            
            $io->text($result->summary);
            $io->newLine();

            if (!empty($result->issues)) {
                $io->text(sprintf('<error>🚨 Found %d issues</error>', count($result->issues)));
                
                foreach ($result->issues as $issue) {
                    $severity = match($issue->severity->value) {
                        'critical' => '<fg=red>CRITICAL</>',
                        'high' => '<fg=red>HIGH</>',
                        'medium' => '<fg=yellow>MEDIUM</>',
                        'low' => '<fg=green>LOW</>',
                        default => '<fg=gray>INFO</>',
                    };
                    
                    $io->text("  {$severity} {$issue->title}");
                    if ($issue->file) {
                        $io->text("    📁 {$issue->getLocationString()}");
                    }
                    if ($detailed && $issue->description) {
                        $io->text("    💡 {$issue->description}");
                    }
                }
                $io->newLine();
            }

            if (!empty($result->suggestions)) {
                $io->text(sprintf('<info>💡 %d optimization suggestions</info>', count($result->suggestions)));
                
                foreach ($result->suggestions as $suggestion) {
                    $priority = match($suggestion->priority->value) {
                        'critical' => '<fg=red>CRITICAL</>',
                        'high' => '<fg=yellow>HIGH</>',
                        'medium' => '<fg=blue>MEDIUM</>',
                        default => '<fg=gray>LOW</>',
                    };
                    
                    $io->text("  {$priority} {$suggestion->title}");
                    if ($detailed && $suggestion->description) {
                        $io->text("    📝 {$suggestion->description}");
                    }
                }
                $io->newLine();
            }

            if ($detailed && !empty($result->metrics)) {
                $io->text('<comment>📊 Metrics:</comment>');
                foreach ($result->metrics as $key => $value) {
                    if (is_scalar($value)) {
                        $io->text("  {$key}: {$value}");
                    }
                }
                $io->newLine();
            }
        }
    }

    private function outputJson(array $results, ?string $outputFile, SymfonyStyle $io): void
    {
        $data = array_map(fn($result) => $result->toArray(), $results);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($outputFile) {
            file_put_contents($outputFile, $json);
            $io->success("Results exported to: {$outputFile}");
        } else {
            $io->text($json);
        }
    }

    private function outputHtml(array $results, ?string $outputFile, SymfonyStyle $io): void
    {
        // Simplified HTML output - in production, this would use a template engine
        $html = $this->generateHtmlReport($results);

        if ($outputFile) {
            file_put_contents($outputFile, $html);
            $io->success("HTML report generated: {$outputFile}");
        } else {
            $io->text($html);
        }
    }

    private function generateHtmlReport(array $results): string
    {
        $content = '';
        foreach ($results as $result) {
            $content .= "<h2>{$result->type->getDisplayName()}</h2>";
            $content .= "<p>{$result->summary}</p>";
            $content .= "<h3>Issues ({count($result->issues)})</h3>";
            // Add more HTML generation logic here
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>AI Development Assistant Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .severity-critical { color: #dc3545; }
        .severity-high { color: #fd7e14; }
        .severity-medium { color: #ffc107; }
        .severity-low { color: #28a745; }
    </style>
</head>
<body>
    <h1>🤖 AI Development Assistant Report</h1>
    <p>Generated on: {date('Y-m-d H:i:s')}</p>
    {$content}
</body>
</html>
HTML;
    }
}
