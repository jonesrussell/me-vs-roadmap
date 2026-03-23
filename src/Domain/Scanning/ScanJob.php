<?php

declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Entity\Scan;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;
use Waaseyaa\Queue\Job;

final class ScanJob extends Job
{
    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param Scan $scan
     * @param string $username
     * @param list<array{id: int, slug: string, detection_rules: array<string, mixed>}> $skills
     * @param array<string, int> $skillSlugToId
     */
    public function __construct(
        private Scan $scan,
        private readonly string $username,
        private readonly array $skills,
        private readonly array $skillSlugToId,
        private readonly ProfileFetcher $fetcher,
        private readonly RepoTriager $triager,
        private readonly FileAnalyzer $analyzer,
        private readonly SkillMapper $mapper,
        private readonly ResultPersister $persister,
        private readonly EntityRepositoryInterface $repository,
    ) {}

    public function handle(): void
    {
        try {
            // Stage 0: Mark analyzing
            $this->scan->markAnalyzing();
            $this->repository->save($this->scan);

            // Stage 1: Fetch profile and repos
            $data = $this->fetcher->fetch($this->username);

            // Stage 2: Triage repos
            $selected = $this->triager->triage($data['repos']);

            // Stage 3: Analyze each selected repo
            $repoResults = [];
            foreach ($selected as $repo) {
                $analysis = $this->analyzer->analyze($repo);
                $repoResults[] = [
                    'repo_name' => $repo->fullName,
                    'tree' => $analysis['tree'],
                    'contents' => $analysis['contents'],
                    'pushed_at' => $repo->pushedAt->format('c'),
                ];
            }

            // Stage 4: Map skills
            $assessments = $this->mapper->mapSkills($this->skills, $repoResults, count($selected));

            // Stage 5: Persist results
            $developerId = (int) $this->scan->get('developer_id');
            $scanId = (int) $this->scan->id();
            $this->persister->persist($developerId, $scanId, $assessments, $this->skillSlugToId);

            // Mark complete
            $this->scan->markComplete(count($selected));
            $this->repository->save($this->scan);
        } catch (\Throwable $e) {
            $this->scan->markFailed();
            $this->repository->save($this->scan);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->scan->markFailed();
        $this->repository->save($this->scan);
    }
}
