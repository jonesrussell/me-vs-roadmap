<?php

declare(strict_types=1);

namespace App\Entity;

use App\Support\Proficiency;
use Waaseyaa\Entity\ContentEntityBase;

class SkillAssessment extends ContentEntityBase
{
    protected string $entityTypeId = 'skill_assessment';

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
            'proficiency' => Proficiency::None->value,
            'confidence' => 0.0,
        ];

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getProficiency(): Proficiency
    {
        return Proficiency::from((string) $this->get('proficiency'));
    }
}
