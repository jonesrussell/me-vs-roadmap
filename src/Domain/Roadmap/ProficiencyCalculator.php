<?php

declare(strict_types=1);

namespace App\Domain\Roadmap;

use App\Support\Proficiency;

final class ProficiencyCalculator
{
    public static function fromScore(float $score): Proficiency
    {
        return match (true) {
            $score <= 0 => Proficiency::None,
            $score <= 30 => Proficiency::Beginner,
            $score <= 65 => Proficiency::Intermediate,
            default => Proficiency::Advanced,
        };
    }

    public static function calculateRawScore(
        int $repoCount,
        int $totalRepos,
        float $recentRatio,
        float $depthScore,
    ): float {
        $frequency = ($repoCount / $totalRepos) * 100 * 0.4;
        $recency = $recentRatio * 100 * 0.3;
        $depth = $depthScore * 100 * 0.3;

        return min($frequency + $recency + $depth, 100.0);
    }

    public static function calculateConfidence(int $evidenceCount, int $maxEvidenceCount): float
    {
        if ($maxEvidenceCount <= 0) {
            return 0.0;
        }

        return min(max($evidenceCount / $maxEvidenceCount, 0.0), 1.0);
    }
}
