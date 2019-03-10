<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bakame\DiceRoller;

use Psr\Log\AbstractLogger;

final class Logger extends AbstractLogger
{
    /**
     * @var array
     */
    private $logs = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = []): void
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{'.$key.'}'] = $val;
        }

        if (!array_key_exists($level, $this->logs)) {
            $this->logs[$level] = [];
        }

        $this->logs[$level][] = strtr($message, $replace);
    }

    /**
     * Retrieves the logs from the memory.
     * @param ?string $level
     */
    public function getLogs(?string $level = null): array
    {
        if (null === $level) {
            return $this->logs;
        }

        if (array_key_exists($level, $this->logs)) {
            return $this->logs[$level];
        }

        return [];
    }
}
