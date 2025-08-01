<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Agent\Memory;

/**
 * @author Denis Zunke <denis.zunke@gmail.com>
 */
final readonly class Memory
{
    public function __construct(public string $content)
    {
    }
}
