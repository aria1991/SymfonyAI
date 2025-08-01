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

namespace Aria1991\AIDevAssistantBundle;

use Aria1991\AIDevAssistantBundle\DependencyInjection\AIDevAssistantExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * AI Development Assistant Bundle for intelligent code analysis.
 *
 * This bundle provides AI-powered code analysis with graceful fallbacks,
 * supporting multiple AI providers and comprehensive static analysis integration.
 *
 * Quick Setup:
 * 1. composer require aria1991/ai-dev-assistant-bundle
 * 2. php bin/console ai-dev-assistant:install
 * 3. Add API key to .env file
 * 4. php bin/console ai-dev-assistant:analyze src/
 *
 * Features:
 * - Security vulnerability detection
 * - Performance optimization suggestions  
 * - Code quality analysis
 * - Documentation completeness review
 * - Multi-provider AI support (OpenAI, Anthropic, Google)
 * - REST API endpoints
 * - Caching and rate limiting
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final class AIDevAssistantBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AIDevAssistantExtension();
    }
}
