<?php

declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;

final readonly class ProfileFetcher
{
    public function __construct(private GitHubClientInterface $github) {}

    /** @return array{profile: array, repos: RepoMetadata[]} */
    public function fetch(string $username): array
    {
        $profile = $this->github->getUser($username);
        $rawRepos = $this->github->getUserRepos($username);
        $repos = array_map(fn(array $r) => RepoMetadata::fromApiResponse($r), $rawRepos);

        return ['profile' => $profile, 'repos' => $repos];
    }
}
