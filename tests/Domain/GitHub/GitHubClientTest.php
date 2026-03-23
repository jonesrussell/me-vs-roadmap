<?php
declare(strict_types=1);

namespace App\Tests\Domain\GitHub;

use App\Domain\GitHub\GitHubClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitHubClient::class)]
final class GitHubClientTest extends TestCase
{
    #[Test]
    public function fetchesUserProfile(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'login' => 'jonesrussell',
                'name' => 'Russell Jones',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/123',
            ])),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new GitHubClient($httpClient);

        $user = $client->getUser('jonesrussell');

        $this->assertSame('jonesrussell', $user['login']);
        $this->assertSame('Russell Jones', $user['name']);
        $this->assertSame('https://avatars.githubusercontent.com/u/123', $user['avatar_url']);
    }

    #[Test]
    public function fetchesUserReposPaginated(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                ['name' => 'repo1', 'full_name' => 'user/repo1'],
                ['name' => 'repo2', 'full_name' => 'user/repo2'],
            ])),
            // Second page returns empty, stopping pagination
            new Response(200, [], json_encode([])),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new GitHubClient($httpClient);

        $repos = $client->getUserRepos('user');

        $this->assertCount(2, $repos);
        $this->assertSame('repo1', $repos[0]['name']);
    }

    #[Test]
    public function fetchesRepoTree(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'tree' => [
                    ['path' => 'src/main.go', 'type' => 'blob'],
                    ['path' => 'src', 'type' => 'tree'],
                    ['path' => 'README.md', 'type' => 'blob'],
                ],
            ])),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new GitHubClient($httpClient);

        $tree = $client->getRepoTree('user/repo');

        $this->assertCount(2, $tree);
        $this->assertContains('src/main.go', $tree);
        $this->assertContains('README.md', $tree);
        $this->assertNotContains('src', $tree);
    }

    #[Test]
    public function fetchesFileContent(): void
    {
        $content = 'Hello, World!';
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'content' => base64_encode($content),
                'encoding' => 'base64',
            ])),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new GitHubClient($httpClient);

        $result = $client->getFileContent('user/repo', 'README.md');

        $this->assertSame($content, $result);
    }

    #[Test]
    public function getFileContentReturnsNullOnError(): void
    {
        $mock = new MockHandler([
            new Response(404, [], json_encode(['message' => 'Not Found'])),
        ]);

        $httpClient = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new GitHubClient($httpClient);

        $result = $client->getFileContent('user/repo', 'nonexistent.txt');

        $this->assertNull($result);
    }
}
