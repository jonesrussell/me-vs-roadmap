<?php

declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;

final readonly class FileAnalyzer
{
    private const array CONTENT_FETCH_FILES = [
        'package.json', 'composer.json', 'go.mod', 'Cargo.toml',
        'requirements.txt', 'pyproject.toml', 'Gemfile',
    ];

    public function __construct(
        private GitHubClientInterface $github,
        private FileDetector $detector,
    ) {}

    /** @return array{tree: string[], contents: array<string, string>} */
    public function analyze(RepoMetadata $repo): array
    {
        $tree = $this->github->getRepoTree($repo->fullName);
        $contents = [];

        foreach ($tree as $path) {
            if (in_array(basename($path), self::CONTENT_FETCH_FILES, true)) {
                $content = $this->github->getFileContent($repo->fullName, $path);
                if ($content !== null) {
                    $contents[$path] = $content;
                }
            }
        }

        return ['tree' => $tree, 'contents' => $contents];
    }
}
