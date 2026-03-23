<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Scan;
use App\Support\ScanStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ScanTest extends TestCase
{
    #[Test]
    public function it_creates_with_queued_status(): void
    {
        $scan = new Scan(['developer_id' => 1]);

        $this->assertSame('scan', $scan->getEntityTypeId());
        $this->assertSame(ScanStatus::Queued->value, $scan->get('status'));
        $this->assertSame(1, $scan->get('developer_id'));
        $this->assertNotEmpty($scan->uuid());
    }

    #[Test]
    public function mark_analyzing_sets_status_and_started_at(): void
    {
        $scan = new Scan(['developer_id' => 1]);
        $scan->markAnalyzing();

        $this->assertSame(ScanStatus::Analyzing->value, $scan->get('status'));
        $this->assertNotNull($scan->get('started_at'));
    }

    #[Test]
    public function mark_complete_sets_status_and_repos_analyzed(): void
    {
        $scan = new Scan(['developer_id' => 1]);
        $scan->markAnalyzing();
        $scan->markComplete(42);

        $this->assertSame(ScanStatus::Complete->value, $scan->get('status'));
        $this->assertSame(42, $scan->get('repos_analyzed'));
        $this->assertNotNull($scan->get('completed_at'));
    }

    #[Test]
    public function mark_failed_sets_status(): void
    {
        $scan = new Scan(['developer_id' => 1]);
        $scan->markAnalyzing();
        $scan->markFailed();

        $this->assertSame(ScanStatus::Failed->value, $scan->get('status'));
        $this->assertNotNull($scan->get('completed_at'));
    }
}
