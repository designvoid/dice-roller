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

namespace Bakame\DiceRoller\Modifier;

use Bakame\DiceRoller\Contract\Modifier;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function abs;
use function intdiv;
use function strpos;

final class Arithmetic implements Modifier, SupportsTracing
{
    public const ADD = '+';
    public const SUB = '-';
    public const DIV = '/';
    public const EXP = '^';
    public const MUL = '*';

    private const OPERATOR = [
        self::ADD => 1,
        self::SUB => 1,
        self::EXP => 1,
        self::DIV => 1,
        self::MUL => 1,
    ];

    private Rollable $rollable;

    private int $value;

    private string $operator;

    private Tracer $tracer;

    /**
     * @throws SyntaxError if the operator is not recognized
     * @throws SyntaxError if the value is invalid for a given operator
     */
    public function __construct(Rollable $rollable, string $operator, int $value)
    {
        if (!isset(self::OPERATOR[$operator])) {
            throw SyntaxError::dueToInvalidOperator($operator);
        }

        if (0 > $value || (0 === $value && $operator == self::DIV)) {
            throw SyntaxError::dueToOperatorAndValueMismatched($operator, $value);
        }

        $this->rollable = $rollable;
        $this->operator = $operator;
        $this->value = $value;
        $this->setTracer(new NullTracer());
    }

    /**
     * {@inheritDoc}
     */
    public function setTracer(Tracer $tracer): void
    {
        $this->tracer = $tracer;
    }

    /**
     * {@inheritDoc}
     */
    public function getInnerRollable(): Rollable
    {
        return $this->rollable;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    /**
     * {@inheritDoc}
     */
    public function notation(): string
    {
        $str = $this->rollable->notation();
        if (false !== strpos($str, '+')) {
            $str = '('.$str.')';
        }

        return $str.$this->operator.$this->value;
    }

    /**
     * {@inheritDoc}
     */
    public function roll(): Roll
    {
        $value = $this->rollable->roll()->value();

        return $this->decorate($value, __METHOD__);
    }

    /**
     * {@inheritDoc}
     */
    public function minimum(): int
    {
        $value = $this->rollable->minimum();

        return $this->decorate($value, __METHOD__)->value();
    }

    /**
     * {@inheritDoc}
     */
    public function maximum(): int
    {
        $value = $this->rollable->maximum();

        return $this->decorate($value, __METHOD__)->value();
    }

    /**
     * Decorates the operation returned value.
     */
    private function decorate(int $value, string $method): Roll
    {
        $result = $this->calculate($value);
        $operation = $value.' '.$this->operator.' '.$this->value;
        $roll = new Toss($result, $operation, new TossContext($this, $method));

        $this->tracer->append($roll);

        return $roll;
    }

    /**
     * Computes the value to be return.
     */
    private function calculate(int $value): int
    {
        if ('+' === $this->operator) {
            return $value + $this->value;
        }

        if ('-' === $this->operator) {
            return $value - $this->value;
        }

        if ('*' === $this->operator) {
            return $value * $this->value;
        }

        if ('/' === $this->operator) {
            return intdiv($value, $this->value);
        }

        if ($value > -1) {
            return $value ** $this->value;
        }

        return (int) (abs($value) ** $this->value) * -1;
    }
}
