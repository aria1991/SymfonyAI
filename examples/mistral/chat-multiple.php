<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Bridge\Mistral\Mistral;
use Symfony\AI\Platform\Bridge\Mistral\PlatformFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

require_once dirname(__DIR__).'/bootstrap.php';

$platform = PlatformFactory::create(env('MISTRAL_API_KEY'), http_client());
$agent = new Agent($platform, new Mistral(), logger: logger());

$messages = new MessageBag(
    Message::forSystem('Just give short answers.'),
    Message::ofUser('What is your favorite color?'),
);
$result = $agent->call($messages, [
    'temperature' => 1.5,
    'n' => 10,
]);

foreach ($result->getContent() as $key => $choice) {
    echo sprintf('Choice #%d: %s', ++$key, $choice->getContent()).\PHP_EOL;
}
