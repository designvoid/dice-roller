<?php

/**
 * PHP Dice Roller (https://github.com/bakame-php/dice-roller/)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bakame\DiceRoller\Test;

use Bakame\DiceRoller\Contract\Rollable;
use Bakame\DiceRoller\Cup;
use Bakame\DiceRoller\CustomDie;
use Bakame\DiceRoller\Exception\IllegalValue;
use Bakame\DiceRoller\ExpressionParser;
use Bakame\DiceRoller\Factory;
use Bakame\DiceRoller\FudgeDie;
use Bakame\DiceRoller\LogProfiler;
use Bakame\DiceRoller\MemoryLogger;
use Bakame\DiceRoller\PercentileDie;
use Bakame\DiceRoller\SidedDie;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Bakame\DiceRoller\Cup
 */
final class CupTest extends TestCase
{
    /**
     * @var \Bakame\DiceRoller\Contract\Profiler
     */
    private $profiler;

    public function setUp(): void
    {
        $this->profiler = LogProfiler::fromNullLogger();
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollable(): void
    {
        $cup = new Cup();
        $altCup = $cup->withAddedRollable(new FudgeDie(), new CustomDie(-1, 1, -1));
        self::assertNotEquals($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::withAddedRollable
     */
    public function testWithRollableReturnsSameInstance(): void
    {
        $cup = new Cup(new FudgeDie());
        $altCup = $cup->withAddedRollable(new Cup());

        self::assertSame($cup, $altCup);
    }

    /**
     * @covers ::__construct
     * @covers ::toString
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::count
     * @covers ::getIterator
     * @covers ::isEmpty
     */
    public function testRoll(): void
    {
        $factory = new Factory(new ExpressionParser());
        $cup = new Cup($factory->newInstance('4D10'), $factory->newInstance('2d4'));
        self::assertFalse($cup->isEmpty());
        self::assertSame(6, $cup->getMinimum());
        self::assertSame(48, $cup->getMaximum());
        self::assertSame('4D10+2D4', $cup->toString());
        self::assertCount(2, $cup);
        self::assertContainsOnlyInstancesOf(Rollable::class, $cup);
        for ($i = 0; $i < 5; $i++) {
            $test = $cup->roll();
            self::assertGreaterThanOrEqual($cup->getMinimum(), $test);
            self::assertLessThanOrEqual($cup->getMaximum(), $test);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::fromRollable
     * @dataProvider validNamedConstructor
     */
    public function testCreateFromRollable(int $quantity, Rollable $template): void
    {
        $cup = Cup::fromRollable($template, $quantity);
        self::assertCount($quantity, $cup);
        self::assertContainsOnlyInstancesOf(get_class($template), $cup);
    }

    public function validNamedConstructor(): iterable
    {
        return [
            'basic dice' => [
                'quantity' => 2,
                'template' => new SidedDie(6),
            ],
            'fudge dice' => [
                'quantity' => 3,
                'template' => new FudgeDie(),
            ],
            'percentile dice' => [
                'quantity' => 4,
                'template' => new PercentileDie(),

            ],
            'custom dice' => [
                'quantity' => 5,
                'template' => new CustomDie(1, 2, 2, 3, 5),
            ],
        ];
    }

    public function testCreateFromRollableThrowsException(): void
    {
        self::expectException(IllegalValue::class);
        Cup::fromRollable(new FudgeDie(), 0);
    }

    /**
     * @covers ::__construct
     * @covers ::fromRollable
     * @covers ::withAddedRollable
     * @covers ::isValid
     */
    public function testCreateFromRollableReturnsEmptyCollection(): void
    {
        $cup = Cup::fromRollable(new Cup(), 12);
        $alt_cup = $cup->withAddedRollable(new Cup());
        self::assertCount(0, $cup);
        self::assertSame($cup, $alt_cup);
    }

    /**
     * @covers ::__construct
     * @covers ::toString
     * @covers ::isEmpty
     */
    public function testEmptyCup(): void
    {
        $cup = new Cup();
        self::assertSame('0', $cup->toString());
        self::assertTrue($cup->isEmpty());
        self::assertSame(0, $cup->roll());
    }

    /**
     * @covers ::__construct
     * @covers ::getMinimum
     * @covers ::getMaximum
     * @covers ::roll
     * @covers ::decorate
     * @covers ::getTrace
     * @covers ::getProfiler
     * @covers ::setProfiler
     */
    public function testTracer(): void
    {
        $logger = new MemoryLogger();
        $profiler = new LogProfiler($logger, LogLevel::DEBUG);
        $cup = Cup::fromRollable(new CustomDie(2, -3, -5), 12);
        $cup->setProfiler($profiler);
        self::assertEmpty($cup->getTrace());
        $cup->roll();
        self::assertNotEmpty($cup->getTrace());
        $cup->getMaximum();
        $cup->getMinimum();
        self::assertSame($profiler, $cup->getProfiler());
        self::assertCount(3, $logger->getLogs(LogLevel::DEBUG));
    }

    /**
     * @covers ::count
     * @covers ::getIterator
     */
    public function testFiveFourSidedDice(): void
    {
        $group = Cup::fromRollable(new SidedDie(4), 5);
        self::assertCount(5, $group);
        self::assertContainsOnlyInstancesOf(SidedDie::class, $group);
        foreach ($group as $dice) {
            self::assertSame(4, $dice->getSize());
        }

        for ($i = 0; $i < 5; $i++) {
            $test = $group->roll();
            self::assertGreaterThanOrEqual($group->getMinimum(), $test);
            self::assertLessThanOrEqual($group->getMaximum(), $test);
        }
    }
}
