<?php

declare(strict_types=1);

namespace App\Tests\Access;

use App\Access\ScanAccessPolicy;
use App\Entity\Scan;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Access\AccessResult;
use Waaseyaa\Access\AccountInterface;

class ScanAccessPolicyTest extends TestCase
{
    private ScanAccessPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new ScanAccessPolicy();
    }

    #[Test]
    public function allowsViewingScanStatus(): void
    {
        $entity = new Scan();
        $account = $this->createMock(AccountInterface::class);

        $result = $this->policy->access($entity, 'view', $account);

        $this->assertTrue($result->isAllowed());
    }

    #[Test]
    public function forbidsCreatingScansDirectly(): void
    {
        $account = $this->createMock(AccountInterface::class);

        $result = $this->policy->createAccess('scan', 'scan', $account);

        $this->assertTrue($result->isForbidden());
    }
}
