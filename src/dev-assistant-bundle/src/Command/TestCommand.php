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

use Symfony\AI\DevAssistantBundle\Analyzer\MockArchitectureAnalyzer;
use Symfony\AI\DevAssistantBundle\Analyzer\MockCodeQualityAnalyzer;
use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisType;
use Symfony\AI\DevAssistantBundle\Service\SimpleOrchestrator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Simple test command for demonstration purposes.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
#[AsCommand(
    name: 'dev-assistant:test',
    description: 'Test the AI development assistant with mock analyzers'
)]
final class TestCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'Path to analyze (file or directory)')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Analysis type (code_quality, architecture)', 'code_quality')
            ->setHelp('This command tests the dev assistant with mock analyzers (no AI API calls required)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $path = $input->getArgument('path');
        $type = $input->getOption('type');

        $io->title('🤖 AI Development Assistant - Test Mode');
        
        if (!file_exists($path)) {
            $io->error("Path does not exist: {$path}");
            return Command::FAILURE;
        }

        try {
            // Collect files
            $files = $this->collectFiles($path);
            
            if (empty($files)) {
                $io->warning('No PHP files found to analyze.');
                return Command::SUCCESS;
            }

            $io->section('📊 Analysis Configuration');
            $io->definitionList(
                ['Path' => $path],
                ['Files Found' => count($files)],
                ['Analysis Type' => $type],
                ['Mode' => 'Mock (No AI API calls)'],
            );

            // Create mock analyzers
            $analyzers = [
                new MockCodeQualityAnalyzer(),
                new MockArchitectureAnalyzer(),
            ];

            $orchestrator = new SimpleOrchestrator($analyzers);

            // Create analysis request
            $analysisType = AnalysisType::from($type);
            $request = new AnalysisRequest(
                id: uniqid('test_'),
                type: $analysisType,
                files: $files,
                projectType: 'symfony',
            );

            // Perform analysis
            $io->section("🔍 Running {$analysisType->getDisplayName()}");
            $result = $orchestrator->analyze($request);

            // Display results
            $this->displayResults($result, $io);

            $io->success('🎉 Test analysis completed successfully!');
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
    private function collectFiles(string $path): array
    {
        if (is_file($path)) {
            return [basename($path) => file_get_contents($path)];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[$file->getFilename()] = file_get_contents($file->getPathname());
            }
        }

        return $files;
    }

    private function displayResults($result, SymfonyStyle $io): void
    {
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
                    $io->text("    📁 {$issue->file}" . ($issue->line ? ":{$issue->line}" : ''));
                }
                if ($issue->description) {
                    $io->text("    💬 {$issue->description}");
                }
                if ($issue->fixSuggestion) {
                    $io->text("    💡 {$issue->fixSuggestion}");
                }
                $io->newLine();
            }
        }

        if (!empty($result->suggestions)) {
            $io->text(sprintf('<info>💡 %d improvement suggestions</info>', count($result->suggestions)));
            
            foreach ($result->suggestions as $suggestion) {
                $priority = match($suggestion->priority->value) {
                    'critical' => '<fg=red>CRITICAL</>',
                    'high' => '<fg=yellow>HIGH</>',
                    'medium' => '<fg=blue>MEDIUM</>',
                    default => '<fg=gray>LOW</>',
                };
                
                $io->text("  {$priority} {$suggestion->title}");
                if ($suggestion->description) {
                    $io->text("    📝 {$suggestion->description}");
                }
                $io->newLine();
            }
        }

        if (!empty($result->metrics)) {
            $io->text('<comment>📊 Metrics:</comment>');
            foreach ($result->metrics as $key => $value) {
                if (is_scalar($value)) {
                    $io->text("  {$key}: {$value}");
                }
            }
        }
    }
}
