<?php
declare(strict_types=1);

namespace App\Tests\Domain\GitHub;

use App\Domain\GitHub\RepoMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RepoMetadata::class)]
final class RepoMetadataTest extends TestCase
{
    #[Test]
    public function constructsFromGitHubApiResponse(): void
    {
        $data = [
            'name' => 'north-cloud',
            'full_name' => 'jonesrussell/north-cloud',
            'description' => 'Content pipeline',
            'fork' => false,
            'language' => 'Go',
            'stargazers_count' => 5,
            'topics' => ['golang', 'microservices'],
            'pushed_at' => '2026-03-01T12:00:00Z',
            'size' => 1024,
        ];

        $repo = RepoMetadata::fromApiResponse($data);

        $this->assertSame('north-cloud', $repo->name);
        $this->assertSame('jonesrussell/north-cloud', $repo->fullName);
        $this->assertFalse($repo->isFork);
        $this->assertSame('Go', $repo->primaryLanguage);
        $this->assertSame(['golang', 'microservices'], $repo->topics);
    }

    #[Test]
    public function calculatesSignalScore(): void
    {
        $recent = RepoMetadata::fromApiResponse([
            'name' => 'recent',
            'full_name' => 'user/recent',
            'description' => '',
            'fork' => false,
            'language' => 'Go',
            'stargazers_count' => 10,
            'topics' => [],
            'pushed_at' => '2026-03-01T12:00:00Z',
            'size' => 500,
        ]);

        $old = RepoMetadata::fromApiResponse([
            'name' => 'old',
            'full_name' => 'user/old',
            'description' => '',
            'fork' => true,
            'language' => null,
            'stargazers_count' => 0,
            'topics' => [],
            'pushed_at' => '2020-01-01T00:00:00Z',
            'size' => 10,
        ]);

        $this->assertGreaterThan($old->signalScore(), $recent->signalScore());
    }
}
