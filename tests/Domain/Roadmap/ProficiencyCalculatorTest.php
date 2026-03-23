<?php

declare(strict_types=1);

namespace App\Tests\Domain\Roadmap;

use App\Domain\Roadmap\ProficiencyCalculator;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProficiencyCalculator::class)]
final class ProficiencyCalculatorTest extends TestCase
{
    /**
     * @return array<string, array{float, Proficiency}>
     */
    public static function scoreToProfileProvider(): array
    {
        return [
            'zero maps to None' => [0, Proficiency::None],
            '15 maps to Beginner' => [15, Proficiency::Beginner],
            '30 maps to Beginner' => [30, Proficiency::Beginner],
            '31 maps to Intermediate' => [31, Proficiency::Intermediate],
            '50 maps to Intermediate' => [50, Proficiency::Intermediate],
            '65 maps to Intermediate' => [65, Proficiency::Intermediate],
            '66 maps to Advanced' => [66, Proficiency::Advanced],
            '100 maps to Advanced' => [100, Proficiency::Advanced],
        ];
    }

    #[Test]
    #[DataProvider('scoreToProfileProvider')]
    public function fromScoreMapsCorrectly(float $score, Proficiency $expected): void
    {
        $this->assertSame($expected, ProficiencyCalculator::fromScore($score));
    }

    #[Test]
    public function calculateRawScoreReturnsWeightedResult(): void
    {
        // 5/10 repos = 50% * 0.4 = 20
        // 0.8 recency * 0.3 = 24
        // 0.5 depth * 0.3 = 15
        // total = 59
        $score = ProficiencyCalculator::calculateRawScore(
            repoCount: 5,
            totalRepos: 10,
            recentRatio: 0.8,
            depthScore: 0.5,
        );

        $this->assertEqualsWithDelta(59.0, $score, 0.01);
    }

    #[Test]
    public function calculateRawScoreClampedToMax100(): void
    {
        $score = ProficiencyCalculator::calculateRawScore(
            repoCount: 10,
            totalRepos: 1,
            recentRatio: 1.0,
            depthScore: 1.0,
        );

        $this->assertSame(100.0, $score);
    }

    #[Test]
    public function calculateConfidenceReturnsRatio(): void
    {
        $confidence = ProficiencyCalculator::calculateConfidence(3, 10);
        $this->assertEqualsWithDelta(0.3, $confidence, 0.001);
    }

    #[Test]
    public function calculateConfidenceClampedToOne(): void
    {
        $confidence = ProficiencyCalculator::calculateConfidence(15, 10);
        $this->assertSame(1.0, $confidence);
    }

    #[Test]
    public function calculateConfidenceClampedToZero(): void
    {
        $confidence = ProficiencyCalculator::calculateConfidence(0, 10);
        $this->assertSame(0.0, $confidence);
    }
}
