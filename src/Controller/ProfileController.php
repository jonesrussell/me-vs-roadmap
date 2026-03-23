<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;

final class ProfileController
{
    public function __construct(
        private readonly EntityRepositoryInterface $repository,
    ) {}

    public function view(Request $request, string $username): Response
    {
        $developers = $this->repository->findBy(['github_username' => $username]);

        if ($developers === []) {
            return new JsonResponse(['error' => 'Developer not found'], Response::HTTP_NOT_FOUND);
        }

        $developer = $developers[0];

        // Check visibility — non-public profiles require session ownership
        $isPublic = $developer->get('is_public');
        if (!$isPublic) {
            $session = $request->getSession();
            $sessionDeveloperId = $session->get('developer_id');

            if ($sessionDeveloperId === null || (string) $sessionDeveloperId !== (string) $developer->id()) {
                return new JsonResponse(['error' => 'Profile is private'], Response::HTTP_FORBIDDEN);
            }
        }

        $developerId = (int) $developer->id();

        // Load assessments for this developer
        $assessments = $this->repository->findBy(['developer_id' => $developerId, 'entity_type_id' => 'skill_assessment']);

        // Load skills referenced by assessments
        $skillIds = array_map(
            static fn(object $a): int => (int) $a->get('skill_id'),
            $assessments,
        );
        $skills = [];
        foreach (array_unique($skillIds) as $skillId) {
            $skill = $this->repository->find((string) $skillId);
            if ($skill !== null) {
                $skills[$skillId] = $skill;
            }
        }

        // Load roadmap paths for referenced skills
        $pathIds = array_unique(array_filter(array_map(
            static fn(object $s): ?int => $s->get('path_id') !== null ? (int) $s->get('path_id') : null,
            $skills,
        )));
        $paths = [];
        foreach ($pathIds as $pathId) {
            $path = $this->repository->find((string) $pathId);
            if ($path !== null) {
                $paths[(int) $pathId] = $path;
            }
        }

        // Build response payload
        $assessmentData = array_map(static function (object $assessment) use ($skills): array {
            $skillId = (int) $assessment->get('skill_id');
            $skill = $skills[$skillId] ?? null;

            return [
                'id' => $assessment->id(),
                'skill_id' => $skillId,
                'skill_name' => $skill?->get('label') ?? 'Unknown',
                'proficiency' => $assessment->get('proficiency'),
                'confidence' => $assessment->get('confidence'),
                'evidence_count' => $assessment->get('evidence_count'),
            ];
        }, $assessments);

        $pathData = array_map(static fn(object $path): array => [
            'id' => $path->id(),
            'label' => $path->get('label'),
            'slug' => $path->get('slug'),
        ], array_values($paths));

        return new JsonResponse([
            'developer' => [
                'id' => $developer->id(),
                'username' => $developer->get('github_username'),
                'display_name' => $developer->get('display_name'),
                'avatar_url' => $developer->get('avatar_url'),
                'is_public' => $developer->get('is_public'),
            ],
            'assessments' => $assessmentData,
            'paths' => $pathData,
        ]);
    }
}
