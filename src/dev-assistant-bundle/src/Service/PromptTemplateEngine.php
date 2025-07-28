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
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Twig\Environment;

/**
 * Professional prompt template engine with Twig integration.
 *
 * This service manages AI prompts as configurable templates, enabling
 * easy customization and A/B testing of prompt strategies.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class PromptTemplateEngine
{
    public function __construct(
        private string $templatesPath,
        private Environment $twig,
        private LoggerInterface $logger,
    ) {
    }

    public function generatePrompt(string $templateName, AnalysisRequest $request): string
    {
        try {
            $templateFile = "{$templateName}.twig";
            
            $context = $this->buildTemplateContext($request);
            
            $prompt = $this->twig->render($templateFile, $context);
            
            $this->logger->debug('Prompt generated successfully', [
                'template' => $templateName,
                'prompt_length' => \strlen($prompt),
                'request_id' => $request->requestId,
            ]);
            
            return $prompt;
            
        } catch (\Throwable $e) {
            $this->logger->error('Prompt generation failed, using fallback', [
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);
            
            return $this->getFallbackPrompt($templateName, $request);
        }
    }

    public function createMessageBag(string $prompt, AnalysisRequest $request): MessageBag
    {
        $systemPrompt = $this->getSystemPrompt($request);
        
        return new MessageBag(
            Message::forSystem($systemPrompt),
            Message::ofUser($prompt)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTemplateContext(AnalysisRequest $request): array
    {
        return [
            'code' => $request->code,
            'file_path' => $request->filePath,
            'analysis_type' => $request->type->value,
            'depth' => $request->depth,
            'rules' => $request->rules,
            'options' => $request->options,
            'context' => $request->context,
            'code_stats' => [
                'length' => $request->getCodeLength(),
                'complexity' => $request->getCodeComplexityEstimate(),
                'lines' => substr_count($request->code, "\n") + 1,
            ],
        ];
    }

    private function getSystemPrompt(AnalysisRequest $request): string
    {
        $expertise = match ($request->type->value) {
            'code_quality' => 'code quality analysis, design patterns, and best practices',
            'architecture' => 'software architecture, system design, and scalability',
            'performance' => 'performance optimization, algorithmic complexity, and bottleneck analysis',
            'security' => 'security analysis, vulnerability assessment, and OWASP guidelines',
            default => 'comprehensive code analysis',
        };

        return <<<SYSTEM
You are a senior software engineer and architect with 15+ years of experience in {$expertise}.
Your task is to analyze the provided PHP code and return structured, actionable insights.

Analysis Guidelines:
- Focus on practical, implementable recommendations
- Prioritize issues by severity and business impact
- Provide specific code examples where helpful
- Consider enterprise-scale development practices
- Follow Symfony framework conventions and PHP best practices

Response Format: Return valid JSON only, no additional text or markdown.
SYSTEM;
    }

    private function getFallbackPrompt(string $templateName, AnalysisRequest $request): string
    {
        return match ($templateName) {
            'code_quality' => $this->getCodeQualityFallback($request),
            'architecture' => $this->getArchitectureFallback($request),
            'performance' => $this->getPerformanceFallback($request),
            default => "Analyze this PHP code:\n\n```php\n{$request->code}\n```",
        };
    }

    private function getCodeQualityFallback(AnalysisRequest $request): string
    {
        return <<<PROMPT
Analyze this PHP code for quality issues and best practices:

```php
{$request->code}
```

Focus on:
- PSR-12 coding standards
- SOLID principles
- Design patterns usage
- Code maintainability
- Error handling

Return JSON with issues and suggestions.
PROMPT;
    }

    private function getArchitectureFallback(AnalysisRequest $request): string
    {
        return <<<PROMPT
Analyze this PHP code's architectural design:

```php
{$request->code}
```

Evaluate:
- System architecture patterns
- Dependency management
- Separation of concerns
- Scalability considerations

Return JSON with architectural analysis.
PROMPT;
    }

    private function getPerformanceFallback(AnalysisRequest $request): string
    {
        return <<<PROMPT
Analyze this PHP code for performance optimization:

```php
{$request->code}
```

Focus on:
- Algorithmic complexity
- Memory usage
- Database query efficiency
- Caching opportunities

Return JSON with performance insights.
PROMPT;
    }
}
