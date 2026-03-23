<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\SkillAssessment;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SkillAssessmentTest extends TestCase
{
    #[Test]
    public function it_creates_with_defaults(): void
    {
        $assessment = new SkillAssessment([
            'developer_id' => 1,
            'roadmap_skill_id' => 2,
            'scan_id' => 3,
        ]);

        $this->assertSame('skill_assessment', $assessment->getEntityTypeId());
        $this->assertSame(Proficiency::None->value, $assessment->get('proficiency'));
        $this->assertSame(0.0, $assessment->get('confidence'));
        $this->assertNotEmpty($assessment->uuid());
    }

    #[Test]
    public function get_proficiency_returns_enum(): void
    {
        $assessment = new SkillAssessment([
            'developer_id' => 1,
            'roadmap_skill_id' => 2,
            'scan_id' => 3,
            'proficiency' => 'advanced',
        ]);

        $this->assertSame(Proficiency::Advanced, $assessment->getProficiency());
    }

    #[Test]
    public function it_stores_references(): void
    {
        $assessment = new SkillAssessment([
            'developer_id' => 10,
            'roadmap_skill_id' => 20,
            'scan_id' => 30,
        ]);

        $this->assertSame(10, $assessment->get('developer_id'));
        $this->assertSame(20, $assessment->get('roadmap_skill_id'));
        $this->assertSame(30, $assessment->get('scan_id'));
    }
}
