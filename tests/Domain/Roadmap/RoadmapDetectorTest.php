<?php

declare(strict_types=1);

namespace App\Tests\Domain\Roadmap;

use App\Domain\Roadmap\RoadmapDetector;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoadmapDetector::class)]
final class RoadmapDetectorTest extends TestCase
{
    private RoadmapDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new RoadmapDetector();
    }

    #[Test]
    public function detectsRelevantRoadmapWithThreeOrMoreSkills(): void
    {
        $assessments = [
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Beginner, 'is_leaf' => true],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Intermediate, 'is_leaf' => true],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Advanced, 'is_leaf' => true],
            ['roadmap_path_id' => 2, 'proficiency' => Proficiency::Beginner, 'is_leaf' => true],
        ];

        $relevant = $this->detector->detectRelevant($assessments);

        $this->assertSame([1], $relevant);
    }

    #[Test]
    public function excludesNonLeafSkills(): void
    {
        $assessments = [
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Beginner, 'is_leaf' => false],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Intermediate, 'is_leaf' => false],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Advanced, 'is_leaf' => false],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Beginner, 'is_leaf' => true],
        ];

        $relevant = $this->detector->detectRelevant($assessments);

        $this->assertSame([], $relevant);
    }
}
