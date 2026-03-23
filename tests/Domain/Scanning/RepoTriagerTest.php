<?php

declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\RepoMetadata;
use App\Domain\Scanning\RepoTriager;
use PHPUnit\Framework\TestCase;

final class RepoTriagerTest extends TestCase
{
    public function testSelectsTopReposBySignalScore(): void
    {
        $repos = [];
        for ($i = 0; $i < 50; $i++) {
            $repos[] = new RepoMetadata(
                name: "repo-{$i}",
                fullName: "user/repo-{$i}",
                description: null,
                isFork: $i % 2 === 0,     // half are forks (lower score)
                primaryLanguage: $i < 25 ? 'Go' : null,  // half have language
                stars: $i,                 // varying stars
                topics: [],
                pushedAt: new \DateTimeImmutable("-{$i} months"),
                sizeKb: $i * 100,
            );
        }

        $triager = new RepoTriager(30);
        $result = $triager->triage($repos);

        $this->assertCount(30, $result);
        $this->assertGreaterThan(
            $result[29]->signalScore(),
            $result[0]->signalScore(),
        );
    }

    public function testReturnsAllIfUnderLimit(): void
    {
        $repos = [
            new RepoMetadata(
                name: 'solo-repo',
                fullName: 'user/solo-repo',
                description: 'A single repo',
                isFork: false,
                primaryLanguage: 'PHP',
                stars: 5,
                topics: [],
                pushedAt: new \DateTimeImmutable('-1 month'),
                sizeKb: 200,
            ),
        ];

        $triager = new RepoTriager(30);
        $result = $triager->triage($repos);

        $this->assertCount(1, $result);
        $this->assertSame('solo-repo', $result[0]->name);
    }
}
