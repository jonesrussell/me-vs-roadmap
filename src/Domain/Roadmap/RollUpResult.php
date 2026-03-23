<?php

declare(strict_types=1);

namespace App\Domain\Roadmap;

use App\Support\Proficiency;

final readonly class RollUpResult
{
    public function __construct(
        public float $score,
        public Proficiency $proficiency,
    ) {}
}
