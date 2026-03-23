<?php

declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Entity\SkillAssessment;
use App\Entity\SkillEvidence;
use App\Support\EvidenceType;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;

final class ResultPersister
{
    public function __construct(
        private readonly EntityRepositoryInterface $repository,
    ) {}

    /**
     * Persist skill assessments and their evidence.
     *
     * @param int $developerId
     * @param int $scanId
     * @param array<string, array{proficiency: \App\Support\Proficiency, confidence: float, evidence: list<array<string, mixed>>, raw_score: float}> $assessments
     * @param array<string, int> $skillSlugToId
     */
    public function persist(int $developerId, int $scanId, array $assessments, array $skillSlugToId): void
    {
        // Delete existing assessments for this developer
        $existing = $this->repository->findBy([
            'entity_type' => 'skill_assessment',
            'developer_id' => $developerId,
        ]);

        foreach ($existing as $entity) {
            $this->repository->delete($entity);
        }

        // Create new assessments and evidence
        foreach ($assessments as $slug => $assessment) {
            $skillId = $skillSlugToId[$slug] ?? null;
            if ($skillId === null) {
                continue;
            }

            $skillAssessment = new SkillAssessment([
                'developer_id' => $developerId,
                'scan_id' => $scanId,
                'skill_id' => $skillId,
                'proficiency' => $assessment['proficiency']->value,
                'confidence' => $assessment['confidence'],
                'raw_score' => $assessment['raw_score'],
            ]);

            $this->repository->save($skillAssessment);

            foreach ($assessment['evidence'] as $evidenceItem) {
                $skillEvidence = new SkillEvidence([
                    'skill_assessment_id' => $skillAssessment->id(),
                    'scan_id' => $scanId,
                    'type' => $evidenceItem['type'],
                    'source_repo' => $evidenceItem['source_repo'],
                    'source_file' => $evidenceItem['source_file'],
                    'details' => $evidenceItem['details'],
                ]);

                $this->repository->save($skillEvidence);
            }
        }
    }
}
