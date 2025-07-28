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

use Symfony\AI\DevAssistantBundle\Contract\AnalyzerInterface;
use Symfony\AI\DevAssistantBundle\Model\AnalysisRequest;
use Symfony\AI\DevAssistantBundle\Model\AnalysisResult;

/**
 * Simple orchestrator for testing without complex dependencies.
 *
 * @author Aria Vahidi <aria.vahidi2020@gmail.com>
 */
final readonly class SimpleOrchestrator
{
    /**
     * @param iterable<AnalyzerInterface> $analyzers
     */
    public function __construct(
        private iterable $analyzers,
    ) {
    }

    public function analyze(AnalysisRequest $request): AnalysisResult
    {
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer->supports($request->type)) {
                return $analyzer->analyze($request);
            }
        }

        throw new \RuntimeException('No analyzer found for type: ' . $request->type->value);
    }

    /**
     * @return array<AnalysisResult>
     */
    public function analyzeAll(AnalysisRequest $request): array
    {
        $results = [];
        
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer->supports($request->type)) {
                $results[] = $analyzer->analyze($request);
            }
        }

        return $results;
    }
}
