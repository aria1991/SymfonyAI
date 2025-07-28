<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\DevAssistantBundle\Contract;

use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;

/**
 * Contract for AI model selection strategies.
 *
 * This interface enables dynamic model selection based on analysis requirements,
 * cost considerations, and performance characteristics.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
interface ModelSelectorInterface
{
    /**
     * Selects the optimal AI model for the given analysis request.
     *
     * @return string The model identifier (e.g., 'claude-3-5-sonnet', 'gpt-4o')
     */
    public function selectModel(AnalysisRequest $request): string;

    /**
     * Returns a fallback model if the primary selection fails.
     */
    public function getFallbackModel(string $primaryModel): ?string;

    /**
     * Estimates the cost for analyzing with the given model.
     */
    public function estimateCost(AnalysisRequest $request, string $model): float;
}
