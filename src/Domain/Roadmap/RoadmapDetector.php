<?php

declare(strict_types=1);

namespace App\Domain\Roadmap;

use App\Support\Proficiency;

final class RoadmapDetector
{
    private const int MIN_SKILLS_FOR_RELEVANCE = 3;

    /**
     * @param array<int, array{roadmap_path_id: int, proficiency: Proficiency, is_leaf: bool}> $assessments
     * @return int[] Relevant roadmap path IDs
     */
    public function detectRelevant(array $assessments): array
    {
        $counts = [];
        foreach ($assessments as $a) {
            if (!$a['is_leaf']) {
                continue;
            }
            if ($a['proficiency'] === Proficiency::None) {
                continue;
            }
            $pathId = $a['roadmap_path_id'];
            $counts[$pathId] = ($counts[$pathId] ?? 0) + 1;
        }

        $relevant = [];
        foreach ($counts as $pathId => $count) {
            if ($count >= self::MIN_SKILLS_FOR_RELEVANCE) {
                $relevant[] = $pathId;
            }
        }

        return $relevant;
    }
}
