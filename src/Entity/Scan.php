<?php

declare(strict_types=1);

namespace App\Entity;

use App\Support\ScanStatus;
use Waaseyaa\Entity\ContentEntityBase;

class Scan extends ContentEntityBase
{
    protected string $entityTypeId = 'scan';

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
            'status' => ScanStatus::Queued->value,
        ];

        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function markAnalyzing(): static
    {
        return $this->set('status', ScanStatus::Analyzing->value)
            ->set('started_at', date('Y-m-d H:i:s'));
    }

    public function markComplete(int $reposAnalyzed): static
    {
        return $this->set('status', ScanStatus::Complete->value)
            ->set('completed_at', date('Y-m-d H:i:s'))
            ->set('repos_analyzed', $reposAnalyzed);
    }

    public function markFailed(): static
    {
        return $this->set('status', ScanStatus::Failed->value)
            ->set('completed_at', date('Y-m-d H:i:s'));
    }
}
