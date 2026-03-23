<?php
declare(strict_types=1);

namespace App\Domain\GitHub;

interface GitHubClientInterface
{
    public function getUser(string $username): array;

    public function getUserRepos(string $username): array;

    public function getRepoTree(string $fullName, string $branch = 'HEAD'): array;

    public function getFileContent(string $fullName, string $path): ?string;
}
