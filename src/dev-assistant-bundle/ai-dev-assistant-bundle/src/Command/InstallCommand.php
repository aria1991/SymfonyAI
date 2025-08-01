<?php

declare(strict_types=1);

/*
 * This file is part of the AI Development Assistant Bundle.
 *
 * (c) Aria Vahidi <aria.vahidi2020@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aria1991\AIDevAssistantBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command to install and configure the AI Development Assistant Bundle.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
#[AsCommand(
    name: 'ai-dev-assistant:install',
    description: 'Install and configure the AI Development Assistant Bundle'
)]
final class InstallCommand extends Command
{
    private const CONFIG_TEMPLATE = <<<YAML
# AI Development Assistant Bundle Configuration
# Generated by ai-dev-assistant:install command

ai_dev_assistant:
    # Enable the bundle (set to false to disable completely)
    enabled: true
    
    # AI Provider Configuration
    ai:
        providers:
            openai:
                api_key: '%%env(OPENAI_API_KEY)%%'
                model: 'gpt-4'
                max_tokens: 4000
            anthropic:
                api_key: '%%env(ANTHROPIC_API_KEY)%%'
                model: 'claude-3-sonnet-20240229'
                max_tokens: 4000
            google:
                api_key: '%%env(GOOGLE_AI_API_KEY)%%'
                model: 'gemini-pro'
    
    # Analysis Configuration
    analysis:
        enabled_analyzers:
            - 'security'      # Detect security vulnerabilities
            - 'performance'   # Find performance bottlenecks
            - 'quality'       # Code quality and best practices
            - 'documentation' # Documentation completeness
        max_file_size: 1048576  # 1MB - Maximum file size to analyze
        excluded_paths:
            - 'vendor/'
            - 'var/cache/'
            - 'var/log/'
            - 'node_modules/'
            - 'public/build/'
    
    # Caching (recommended for production)
    cache:
        enabled: true
        ttl: 3600  # 1 hour cache duration
    
    # Rate Limiting (prevents API abuse)
    rate_limiting:
        requests_per_minute: 60   # Per IP address
        requests_per_hour: 1000   # Per IP address

YAML;

    private const ENV_TEMPLATE = <<<ENV

###> ai-dev-assistant-bundle ###
# AI Provider API Keys (add your actual keys here)
# Get OpenAI API key from: https://platform.openai.com/api-keys
OPENAI_API_KEY=your_openai_api_key_here

# Get Anthropic API key from: https://console.anthropic.com/
ANTHROPIC_API_KEY=your_anthropic_api_key_here

# Get Google AI API key from: https://makersuite.google.com/app/apikey
GOOGLE_AI_API_KEY=your_google_api_key_here
###< ai-dev-assistant-bundle ###

ENV;

    public function __construct(
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $io->title('🤖 AI Development Assistant Bundle - Installation');

        // Step 1: Create configuration file
        $configPath = $this->projectDir . '/config/packages/ai_dev_assistant.yaml';
        $configDir = dirname($configPath);

        if (!$filesystem->exists($configDir)) {
            $filesystem->mkdir($configDir);
            $io->success("Created configuration directory: {$configDir}");
        }

        if (!$filesystem->exists($configPath)) {
            $filesystem->dumpFile($configPath, self::CONFIG_TEMPLATE);
            $io->success("Created configuration file: {$configPath}");
        } else {
            $io->warning("Configuration file already exists: {$configPath}");
            if ($io->confirm('Do you want to overwrite it?', false)) {
                $filesystem->dumpFile($configPath, self::CONFIG_TEMPLATE);
                $io->success("Configuration file updated!");
            }
        }

        // Step 2: Update .env file
        $envPath = $this->projectDir . '/.env';
        if ($filesystem->exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            if (!str_contains($envContent, 'ai-dev-assistant-bundle')) {
                $filesystem->appendToFile($envPath, self::ENV_TEMPLATE);
                $io->success("Added environment variables to .env file");
            } else {
                $io->info("Environment variables already exist in .env file");
            }
        } else {
            $io->warning(".env file not found. Please create it and add the API keys manually.");
        }

        // Step 3: Installation summary
        $io->section('🎉 Installation Complete!');
        
        $io->definitionList(
            ['Configuration file' => $configPath],
            ['Environment variables' => 'Added to .env file'],
            ['Bundle status' => 'Ready to use!']
        );

        $io->section('📝 Next Steps:');
        $io->listing([
            '1. Add your AI provider API keys to the .env file',
            '2. Test the configuration: php bin/console ai-dev-assistant:config-test',
            '3. Run your first analysis: php bin/console ai-dev-assistant:analyze src/',
            '4. Check the REST API: /ai-dev-assistant/health'
        ]);

        $io->section('🔑 Getting API Keys:');
        $io->table(
            ['Provider', 'Website', 'Notes'],
            [
                ['OpenAI', 'https://platform.openai.com/api-keys', 'Most reliable, requires billing'],
                ['Anthropic', 'https://console.anthropic.com/', 'Great for code analysis'],
                ['Google AI', 'https://makersuite.google.com/app/apikey', 'Free tier available']
            ]
        );

        $io->note([
            'You only need one API key to start using the bundle.',
            'The system will automatically fallback between providers.',
            'For production, we recommend configuring multiple providers.'
        ]);

        return Command::SUCCESS;
    }
}
