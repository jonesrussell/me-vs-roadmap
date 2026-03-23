<?php

declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\RepoMetadata;

final readonly class RepoTriager
{
    public function __construct(private int $maxRepos = 30) {}

    /** @param RepoMetadata[] $repos  @return RepoMetadata[] */
    public function triage(array $repos): array
    {
        usort($repos, fn(RepoMetadata $a, RepoMetadata $b) => $b->signalScore() <=> $a->signalScore());

        return array_slice($repos, 0, $this->maxRepos);
    }
}
