<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RoadmapSkill;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RoadmapSkillTest extends TestCase
{
    #[Test]
    public function it_creates_with_defaults(): void
    {
        $skill = new RoadmapSkill([
            'id' => 1,
            'slug' => 'php',
            'name' => 'PHP',
            'category' => 'language',
            'roadmap_path_id' => 1,
        ]);

        $this->assertSame('roadmap_skill', $skill->getEntityTypeId());
        $this->assertSame(1, $skill->id());
        $this->assertSame('PHP', $skill->label());
        $this->assertSame('php', $skill->get('slug'));
        $this->assertSame('language', $skill->get('category'));
        $this->assertSame([], $skill->get('detection_rules'));
    }

    #[Test]
    public function it_accepts_nullable_parent_skill_id(): void
    {
        $skill = new RoadmapSkill(['name' => 'PHP', 'parent_skill_id' => null]);
        $this->assertNull($skill->get('parent_skill_id'));

        $child = new RoadmapSkill(['name' => 'Laravel', 'parent_skill_id' => 1]);
        $this->assertSame(1, $child->get('parent_skill_id'));
    }

    #[Test]
    public function get_detection_rules_decodes_json_string(): void
    {
        $rules = [['type' => 'file', 'pattern' => 'composer.json']];
        $skill = new RoadmapSkill([
            'name' => 'PHP',
            'detection_rules' => json_encode($rules),
        ]);

        $this->assertSame($rules, $skill->getDetectionRules());
    }

    #[Test]
    public function get_detection_rules_returns_array_as_is(): void
    {
        $rules = [['type' => 'file', 'pattern' => 'composer.json']];
        $skill = new RoadmapSkill([
            'name' => 'PHP',
            'detection_rules' => $rules,
        ]);

        $this->assertSame($rules, $skill->getDetectionRules());
    }

    #[Test]
    public function get_detection_rules_defaults_to_empty_array(): void
    {
        $skill = new RoadmapSkill(['name' => 'PHP']);
        $this->assertSame([], $skill->getDetectionRules());
    }
}
