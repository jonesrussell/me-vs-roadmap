<?php

declare(strict_types=1);

namespace App\Entity;

use Waaseyaa\Entity\ContentEntityBase;

class Developer extends ContentEntityBase
{
    protected string $entityTypeId = 'developer';

    /** @var array<string, string> */
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'display_name',
    ];

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $values += [
            'is_public' => true,
        ];

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }
}
