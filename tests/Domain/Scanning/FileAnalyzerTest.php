<?php

declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;
use App\Domain\Scanning\FileAnalyzer;
use PHPUnit\Framework\TestCase;

final class FileAnalyzerTest extends TestCase
{
    public function testAnalyzesFetchesTreeAndKeyFileContents(): void
    {
        $repo = new RepoMetadata(
            name: 'my-app',
            fullName: 'user/my-app',
            description: 'A Go app',
            isFork: false,
            primaryLanguage: 'Go',
            stars: 10,
            topics: ['go'],
            pushedAt: new \DateTimeImmutable('-1 week'),
            sizeKb: 500,
        );

        $tree = ['go.mod', 'Dockerfile', 'README.md', 'main.go'];

        $github = $this->createMock(GitHubClientInterface::class);
        $github->method('getRepoTree')
            ->with('user/my-app')
            ->willReturn($tree);

        $github->method('getFileContent')
            ->willReturnCallback(function (string $fullName, string $path): ?string {
                if ($path === 'go.mod') {
                    return 'module github.com/user/my-app';
                }

                return null;
            });

        $detector = new FileDetector();
        $analyzer = new FileAnalyzer($github, $detector);
        $result = $analyzer->analyze($repo);

        $this->assertSame($tree, $result['tree']);
        $this->assertArrayHasKey('go.mod', $result['contents']);
        $this->assertStringContainsString('module github.com/user/my-app', $result['contents']['go.mod']);
        $this->assertArrayNotHasKey('Dockerfile', $result['contents']);
        $this->assertArrayNotHasKey('README.md', $result['contents']);
    }
}
