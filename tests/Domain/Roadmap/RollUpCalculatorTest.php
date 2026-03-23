<?php

declare(strict_types=1);

namespace App\Tests\Domain\Roadmap;

use App\Domain\Roadmap\ProficiencyCalculator;
use App\Domain\Roadmap\RollUpCalculator;
use App\Domain\Roadmap\RollUpResult;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RollUpCalculator::class)]
#[CoversClass(RollUpResult::class)]
final class RollUpCalculatorTest extends TestCase
{
    #[Test]
    public function rollUpAveragesChildScores(): void
    {
        $result = RollUpCalculator::rollUp([80.0, 40.0]);

        $this->assertEqualsWithDelta(60.0, $result->score, 0.01);
        $this->assertSame(Proficiency::Intermediate, $result->proficiency);
    }

    #[Test]
    public function rollUpRequiresAtLeastTwoChildrenWithEvidence(): void
    {
        $result = RollUpCalculator::rollUp([50.0]);

        $this->assertSame(0.0, $result->score);
        $this->assertSame(Proficiency::None, $result->proficiency);
    }

    #[Test]
    public function rollUpIgnoresZeroScoreChildren(): void
    {
        $result = RollUpCalculator::rollUp([0.0, 80.0, 0.0, 40.0]);

        $this->assertEqualsWithDelta(60.0, $result->score, 0.01);
        $this->assertSame(Proficiency::Intermediate, $result->proficiency);
    }

    #[Test]
    public function rollUpReturnsNoneForEmptyArray(): void
    {
        $result = RollUpCalculator::rollUp([]);

        $this->assertSame(0.0, $result->score);
        $this->assertSame(Proficiency::None, $result->proficiency);
    }

    #[Test]
    public function rollUpReturnsNoneWhenOnlyOneChildHasEvidence(): void
    {
        $result = RollUpCalculator::rollUp([0.0, 70.0, 0.0]);

        $this->assertSame(0.0, $result->score);
        $this->assertSame(Proficiency::None, $result->proficiency);
    }
}
