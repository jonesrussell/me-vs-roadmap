<?php

declare(strict_types=1);

namespace App\Domain\Roadmap;

use App\Support\Proficiency;

final class RollUpCalculator
{
    /**
     * @param float[] $childScores
     */
    public static function rollUp(array $childScores): RollUpResult
    {
        $withEvidence = array_filter($childScores, static fn(float $score): bool => $score > 0);

        if (count($withEvidence) < 2) {
            return new RollUpResult(0.0, Proficiency::None);
        }

        $average = array_sum($withEvidence) / count($withEvidence);

        return new RollUpResult($average, ProficiencyCalculator::fromScore($average));
    }
}
