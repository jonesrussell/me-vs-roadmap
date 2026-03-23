<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Scanning\FileAnalyzer;
use App\Domain\Scanning\ProfileFetcher;
use App\Domain\Scanning\RepoTriager;
use App\Domain\Scanning\ResultPersister;
use App\Domain\Scanning\ScanJob;
use App\Domain\Scanning\SkillMapper;
use App\Entity\Scan;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;
use Waaseyaa\Queue\QueueInterface;

final class ScanController
{
    public function __construct(
        private readonly EntityRepositoryInterface $repository,
        private readonly QueueInterface $queue,
        private readonly ProfileFetcher $fetcher,
        private readonly RepoTriager $triager,
        private readonly FileAnalyzer $analyzer,
        private readonly SkillMapper $mapper,
        private readonly ResultPersister $persister,
    ) {}

    public function trigger(Request $request): Response
    {
        $session = $request->getSession();
        $developerId = $session->get('developer_id');

        if ($developerId === null) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $developer = $this->repository->find((string) $developerId);
        if ($developer === null) {
            return new JsonResponse(['error' => 'Developer not found'], Response::HTTP_NOT_FOUND);
        }

        $username = $developer->get('github_username');

        // Load all roadmap skills with detection rules
        $skills = $this->repository->findBy(['entity_type_id' => 'roadmap_skill']);
        $skillData = [];
        $skillSlugToId = [];

        foreach ($skills as $skill) {
            $id = (int) $skill->id();
            $slug = $skill->get('slug');
            $skillData[] = [
                'id' => $id,
                'slug' => $slug,
                'detection_rules' => $skill->get('detection_rules') ?? [],
            ];
            $skillSlugToId[$slug] = $id;
        }

        // Create Scan entity
        $scan = new Scan([
            'developer_id' => $developerId,
            'repos_analyzed' => 0,
        ]);
        $this->repository->save($scan);

        // Create and dispatch ScanJob
        $job = new ScanJob(
            $scan,
            $username,
            $skillData,
            $skillSlugToId,
            $this->fetcher,
            $this->triager,
            $this->analyzer,
            $this->mapper,
            $this->persister,
            $this->repository,
        );

        $this->queue->dispatch($job);

        return new JsonResponse([
            'scan_id' => $scan->id(),
            'status' => 'queued',
        ], Response::HTTP_ACCEPTED);
    }

    public function status(Request $request, int $id): Response
    {
        $scan = $this->repository->find((string) $id);

        if ($scan === null) {
            return new JsonResponse(['error' => 'Scan not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $scan->id(),
            'status' => $scan->get('status'),
            'repos_analyzed' => $scan->get('repos_analyzed'),
            'started_at' => $scan->get('started_at'),
            'completed_at' => $scan->get('completed_at'),
        ]);
    }
}
