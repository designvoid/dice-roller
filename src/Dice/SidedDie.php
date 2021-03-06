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

namespace Bakame\DiceRoller\Dice;

use Bakame\DiceRoller\Contract\Dice;
use Bakame\DiceRoller\Contract\Roll;
use Bakame\DiceRoller\Contract\SupportsTracing;
use Bakame\DiceRoller\Contract\Tracer;
use Bakame\DiceRoller\Exception\SyntaxError;
use Bakame\DiceRoller\Toss;
use Bakame\DiceRoller\TossContext;
use Bakame\DiceRoller\Tracer\NullTracer;
use function preg_match;
use function random_int;

final class SidedDie implements Dice, SupportsTracing
{
    private const REGEXP_NOTATION = '/^d(?<sides>\d+)$/i';

    private int $sides;

    private Tracer $tracer;

    /**
     * new instance.
     *
     * @param int $sides side count
     *
     * @throws SyntaxError if a SimpleDice contains less than 2 sides
     */
    public function __construct(int $sides)
    {
        if (2 > $sides) {
            throw SyntaxError::dueToTooFewSides($sides);
        }

        $this->sides = $sides;
        $this->setTracer(new NullTracer());
    }

    /**
     * New instance from a dice notation.
     *
     * @throws SyntaxError if the dice notation is not valid.
     */
    public static function fromNotation(string $notation): self
    {
        if (1 !== preg_match(self::REGEXP_NOTATION, $notation, $matches)) {
            throw SyntaxError::dueToInvalidNotation($notation);
        }

        return new self((int) $matches['sides']);
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
    public function jsonSerialize(): string
    {
        return $this->notation();
    }

    /**
     * {@inheritDoc}
     */
    public function notation(): string
    {
        return 'D'.$this->sides;
    }

    /**
     * {@inheritDoc}
     */
    public function size(): int
    {
        return $this->sides;
    }

    /**
     * {@inheritDoc}
     */
    public function minimum(): int
    {
        $roll = new Toss(1, '1', new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritDoc}
     */
    public function maximum(): int
    {
        $roll = new Toss($this->sides, (string) $this->sides, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll->value();
    }

    /**
     * {@inheritDoc}
     */
    public function roll(): Roll
    {
        $result = random_int(1, $this->sides);

        $roll = new Toss($result, (string) $result, new TossContext($this, __METHOD__));

        $this->tracer->append($roll);

        return $roll;
    }
}
