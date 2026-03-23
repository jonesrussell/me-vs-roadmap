<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RoadmapPath;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RoadmapPathTest extends TestCase
{
    #[Test]
    public function it_creates_with_values(): void
    {
        $path = new RoadmapPath([
            'id' => 1,
            'slug' => 'backend',
            'name' => 'Backend Developer',
            'description' => 'Server-side development path',
        ]);

        $this->assertSame('roadmap_path', $path->getEntityTypeId());
        $this->assertSame(1, $path->id());
        $this->assertSame('Backend Developer', $path->label());
        $this->assertSame('backend', $path->get('slug'));
        $this->assertSame('Server-side development path', $path->get('description'));
        $this->assertNotEmpty($path->uuid());
    }

    #[Test]
    public function it_supports_set(): void
    {
        $path = new RoadmapPath();
        $path->set('name', 'Frontend Developer');

        $this->assertSame('Frontend Developer', $path->label());
    }
}
