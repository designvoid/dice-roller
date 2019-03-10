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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class Profiler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $logLevel;

    /**
     * @var string
     */
    protected $logFormat = '[{method}] - {rollable} : {trace} = {result}';

    /**
     * New instance.
     *
     * @param ?string $logFormat
     */
    public function __construct(LoggerInterface $logger, string $logLevel = LogLevel::DEBUG, ?string $logFormat = null)
    {
        $this->logger = $logger;
        $this->logLevel = $logLevel;
        $this->logFormat = $logFormat ?? $this->logFormat;
    }

    /**
     * Records the Rollable action.
     */
    public function profile(string $method, Rollable $rollable, string $trace, int $result): void
    {
        $this->logger->log($this->logLevel, $this->logFormat, [
            'method' => $method,
            'rollable' => $rollable->toString(),
            'trace' => $trace,
            'result' => $result,
        ]);
    }

    /**
     * Returns the underlying LoggerInterface object.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns the LogLevel.
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Returns the LogFormat.
     */
    public function getLogFormat(): string
    {
        return $this->logFormat;
    }

    /**
     * Sets the Logger.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Sets the LogLevel.
     */
    public function setLogLevel(string $level): void
    {
        $this->logLevel = $level;
    }

    /**
     * Sets the LogFormat.
     */
    public function setLogFormat(string $format): void
    {
        $this->logFormat = $format;
    }
}