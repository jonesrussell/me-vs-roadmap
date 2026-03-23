<?php

declare(strict_types=1);

namespace App\Entity;

use App\Support\EvidenceType;
use Waaseyaa\Entity\ContentEntityBase;

class SkillEvidence extends ContentEntityBase
{
    protected string $entityTypeId = 'skill_evidence';

    /** @var array<string, string> */
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
    ];

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $values += [
            'details' => [],
        ];

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getType(): EvidenceType
    {
        return EvidenceType::from((string) $this->get('type'));
    }
}
