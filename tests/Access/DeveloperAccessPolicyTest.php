<?php

declare(strict_types=1);

namespace App\Tests\Access;

use App\Access\DeveloperAccessPolicy;
use App\Entity\Developer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Access\AccessResult;
use Waaseyaa\Access\AccountInterface;

class DeveloperAccessPolicyTest extends TestCase
{
    private DeveloperAccessPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new DeveloperAccessPolicy();
    }

    #[Test]
    public function allowsViewingPublicProfile(): void
    {
        $entity = new Developer(['is_public' => true]);
        $account = $this->createMock(AccountInterface::class);

        $result = $this->policy->access($entity, 'view', $account);

        $this->assertTrue($result->isAllowed());
    }

    #[Test]
    public function forbidsViewingPrivateProfile(): void
    {
        $entity = new Developer(['is_public' => false]);
        $account = $this->createMock(AccountInterface::class);

        $result = $this->policy->access($entity, 'view', $account);

        $this->assertTrue($result->isForbidden());
    }

    #[Test]
    public function appliesToDeveloperEntityType(): void
    {
        $this->assertTrue($this->policy->appliesTo('developer'));
        $this->assertFalse($this->policy->appliesTo('scan'));
    }
}
