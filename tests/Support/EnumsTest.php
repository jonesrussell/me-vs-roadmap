<?php
declare(strict_types=1);

namespace App\Tests\Support;

use App\Support\ScanStatus;
use App\Support\Proficiency;
use App\Support\EvidenceType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScanStatus::class)]
#[CoversClass(Proficiency::class)]
#[CoversClass(EvidenceType::class)]
final class EnumsTest extends TestCase
{
    #[Test]
    public function scanStatusHasExpectedCases(): void
    {
        $cases = array_map(fn($c) => $c->value, ScanStatus::cases());
        $this->assertSame(['queued', 'analyzing', 'complete', 'failed'], $cases);
    }

    #[Test]
    public function proficiencyHasExpectedCases(): void
    {
        $cases = array_map(fn($c) => $c->value, Proficiency::cases());
        $this->assertSame(['none', 'beginner', 'intermediate', 'advanced'], $cases);
    }

    #[Test]
    public function evidenceTypeHasExpectedCases(): void
    {
        $cases = array_map(fn($c) => $c->value, EvidenceType::cases());
        $this->assertSame([
            'language_usage', 'config_file', 'dependency',
            'ci_workflow', 'test_presence', 'commit_pattern',
        ], $cases);
    }
}
