<?php

declare(strict_types=1);

namespace App\Entity;

use Waaseyaa\Entity\ContentEntityBase;

class RoadmapSkill extends ContentEntityBase
{
    protected string $entityTypeId = 'roadmap_skill';

    /** @var array<string, string> */
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'name',
    ];

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $values += [
            'detection_rules' => [],
        ];

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getDetectionRules(): array
    {
        $rules = $this->get('detection_rules');

        if (\is_string($rules)) {
            /** @var array<int|string, mixed> $decoded */
            $decoded = json_decode($rules, true, 512, \JSON_THROW_ON_ERROR);

            return $decoded;
        }

        return \is_array($rules) ? $rules : [];
    }
}
