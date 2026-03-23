<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Developer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DeveloperTest extends TestCase
{
    #[Test]
    public function it_creates_with_defaults(): void
    {
        $dev = new Developer();

        $this->assertSame('developer', $dev->getEntityTypeId());
        $this->assertNull($dev->id());
        $this->assertNotEmpty($dev->uuid());
        $this->assertTrue($dev->get('is_public'));
    }

    #[Test]
    public function it_stores_github_fields(): void
    {
        $dev = new Developer([
            'id' => 1,
            'github_username' => 'octocat',
            'display_name' => 'Octocat',
            'avatar_url' => 'https://example.com/avatar.png',
            'bio' => 'A developer',
        ]);

        $this->assertSame(1, $dev->id());
        $this->assertSame('octocat', $dev->get('github_username'));
        $this->assertSame('Octocat', $dev->label());
        $this->assertSame('https://example.com/avatar.png', $dev->get('avatar_url'));
        $this->assertSame('A developer', $dev->get('bio'));
    }

    #[Test]
    public function it_supports_set(): void
    {
        $dev = new Developer();
        $dev->set('github_username', 'testuser');

        $this->assertSame('testuser', $dev->get('github_username'));
    }

    #[Test]
    public function is_public_defaults_to_true(): void
    {
        $dev = new Developer();
        $this->assertTrue($dev->get('is_public'));

        $dev2 = new Developer(['is_public' => false]);
        $this->assertFalse($dev2->get('is_public'));
    }
}
