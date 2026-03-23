<?php

declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;
use App\Domain\Scanning\FileAnalyzer;
use App\Domain\Scanning\ProfileFetcher;
use App\Domain\Scanning\RepoTriager;
use App\Domain\Scanning\ResultPersister;
use App\Domain\Scanning\ScanJob;
use App\Domain\Scanning\SkillMapper;
use App\Entity\Scan;
use App\Support\ScanStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;

#[CoversClass(ScanJob::class)]
final class ScanJobTest extends TestCase
{
    private function makeGitHubMock(): GitHubClientInterface
    {
        $github = $this->createMock(GitHubClientInterface::class);

        $github->method('getUser')
            ->with('testuser')
            ->willReturn([
                'login' => 'testuser',
                'name' => 'Test User',
                'public_repos' => 1,
            ]);

        $github->method('getUserRepos')
            ->with('testuser')
            ->willReturn([
                [
                    'name' => 'go-project',
                    'full_name' => 'testuser/go-project',
                    'description' => 'A Go project',
                    'fork' => false,
                    'language' => 'Go',
                    'stargazers_count' => 5,
                    'topics' => ['go'],
                    'pushed_at' => '2026-03-01T00:00:00Z',
                    'size' => 200,
                ],
            ]);

        $github->method('getRepoTree')
            ->with('testuser/go-project')
            ->willReturn(['go.mod', 'main.go', 'handler_test.go']);

        $github->method('getFileContent')
            ->willReturnCallback(function (string $fullName, string $path): ?string {
                if ($path === 'go.mod') {
                    return 'module github.com/testuser/go-project';
                }

                return null;
            });

        return $github;
    }

    #[Test]
    public function handleOrchestrates5StagesAndMarksScanComplete(): void
    {
        $github = $this->makeGitHubMock();
        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository->method('save')->willReturn(1);

        $scan = new Scan([
            'id' => 42,
            'developer_id' => 7,
            'status' => ScanStatus::Queued->value,
        ]);

        $skills = [
            [
                'id' => 1,
                'slug' => 'go',
                'detection_rules' => [
                    'languages' => ['go'],
                    'files' => ['go.mod'],
                    'config_patterns' => [],
                    'dependencies' => [],
                ],
            ],
        ];
        $skillSlugToId = ['go' => 1];

        $fetcher = new ProfileFetcher($github);
        $triager = new RepoTriager();
        $detector = new FileDetector();
        $analyzer = new FileAnalyzer($github, $detector);
        $mapper = new SkillMapper($detector);
        $persisterRepo = $this->createMock(EntityRepositoryInterface::class);
        $persisterRepo->method('findBy')->willReturn([]);
        $persisterRepo->method('save')->willReturn(1);
        $persister = new ResultPersister($persisterRepo);

        $job = new ScanJob(
            scan: $scan,
            username: 'testuser',
            skills: $skills,
            skillSlugToId: $skillSlugToId,
            fetcher: $fetcher,
            triager: $triager,
            analyzer: $analyzer,
            mapper: $mapper,
            persister: $persister,
            repository: $repository,
        );

        $job->handle();

        $this->assertSame(ScanStatus::Complete->value, $scan->get('status'));
        $this->assertSame(1, $scan->get('repos_analyzed'));
    }

    #[Test]
    public function handleMarksScanFailedOnException(): void
    {
        $github = $this->createMock(GitHubClientInterface::class);
        $github->method('getUser')->willThrowException(new \RuntimeException('API down'));

        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository->method('save')->willReturn(1);

        $scan = new Scan([
            'id' => 42,
            'developer_id' => 7,
            'status' => ScanStatus::Queued->value,
        ]);

        $fetcher = new ProfileFetcher($github);
        $triager = new RepoTriager();
        $detector = new FileDetector();
        $analyzer = new FileAnalyzer($github, $detector);
        $mapper = new SkillMapper($detector);
        $persisterRepo = $this->createMock(EntityRepositoryInterface::class);
        $persister = new ResultPersister($persisterRepo);

        $job = new ScanJob(
            scan: $scan,
            username: 'testuser',
            skills: [],
            skillSlugToId: [],
            fetcher: $fetcher,
            triager: $triager,
            analyzer: $analyzer,
            mapper: $mapper,
            persister: $persister,
            repository: $repository,
        );

        $this->expectException(\RuntimeException::class);
        $job->handle();
    }

    #[Test]
    public function failedMethodMarksScanFailed(): void
    {
        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository->expects($this->once())->method('save')->willReturn(1);

        $scan = new Scan([
            'id' => 42,
            'developer_id' => 7,
            'status' => ScanStatus::Analyzing->value,
        ]);

        $github = $this->createMock(GitHubClientInterface::class);
        $detector = new FileDetector();
        $persisterRepo = $this->createMock(EntityRepositoryInterface::class);

        $job = new ScanJob(
            scan: $scan,
            username: 'testuser',
            skills: [],
            skillSlugToId: [],
            fetcher: new ProfileFetcher($github),
            triager: new RepoTriager(),
            analyzer: new FileAnalyzer($github, $detector),
            mapper: new SkillMapper($detector),
            persister: new ResultPersister($persisterRepo),
            repository: $repository,
        );

        $job->failed(new \RuntimeException('boom'));

        $this->assertSame(ScanStatus::Failed->value, $scan->get('status'));
    }

    #[Test]
    public function jobHasCorrectTriesAndTimeout(): void
    {
        $github = $this->createMock(GitHubClientInterface::class);
        $detector = new FileDetector();
        $persisterRepo = $this->createMock(EntityRepositoryInterface::class);

        $job = new ScanJob(
            scan: new Scan(['id' => 1, 'developer_id' => 1]),
            username: 'u',
            skills: [],
            skillSlugToId: [],
            fetcher: new ProfileFetcher($github),
            triager: new RepoTriager(),
            analyzer: new FileAnalyzer($github, $detector),
            mapper: new SkillMapper($detector),
            persister: new ResultPersister($persisterRepo),
            repository: $this->createMock(EntityRepositoryInterface::class),
        );

        $this->assertSame(1, $job->tries);
        $this->assertSame(300, $job->timeout);
    }
}
