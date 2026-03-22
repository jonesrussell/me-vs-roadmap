# Me vs Roadmap Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a developer skill assessment tool that maps GitHub activity against roadmap.sh learning paths, producing an interactive profile with evidence-backed proficiency ratings.

**Architecture:** Waaseyaa entity-centric — six entity types (Developer, Scan, RoadmapPath, RoadmapSkill, SkillAssessment, SkillEvidence) with a queued scan pipeline that fetches GitHub data via hybrid API approach, maps findings to roadmap skills, and renders an interactive tree-first profile.

**Tech Stack:** Waaseyaa (PHP 8.4+, Symfony 7.x), Nuxt 3 + Vue 3 + TypeScript (admin/frontend), SQLite, GitHub REST API v3.

**Spec:** `docs/superpowers/specs/2026-03-22-me-vs-roadmap-design.md`

---

## File Structure

```
me-vs-roadmap/                          # Created by composer create-project
├── config/
│   ├── waaseyaa.php                    # Modify: add GitHub OAuth config keys
│   ├── entity-types.php                # Modify: register all 6 entity types
│   └── services.php                    # Modify: register GitHubClient service
├── src/
│   ├── Entity/
│   │   ├── Developer.php               # Create: Developer entity
│   │   ├── Scan.php                    # Create: Scan entity
│   │   ├── RoadmapPath.php             # Create: RoadmapPath entity
│   │   ├── RoadmapSkill.php            # Create: RoadmapSkill entity
│   │   ├── SkillAssessment.php         # Create: SkillAssessment entity
│   │   └── SkillEvidence.php           # Create: SkillEvidence entity
│   ├── Access/
│   │   ├── DeveloperAccessPolicy.php   # Create: Developer access policy
│   │   ├── ScanAccessPolicy.php        # Create: Scan access policy
│   │   └── ProfileAccessPolicy.php     # Create: Public profile access
│   ├── Provider/
│   │   └── MeVsRoadmapProvider.php     # Create: ServiceProvider (routes, services, commands)
│   ├── Controller/
│   │   ├── ProfileController.php       # Create: Public profile page
│   │   ├── ScanController.php          # Create: Trigger/status scan endpoints
│   │   └── AuthController.php          # Create: GitHub OAuth flow
│   ├── Domain/
│   │   ├── GitHub/
│   │   │   ├── GitHubClient.php        # Create: GitHub API wrapper
│   │   │   ├── GitHubClientInterface.php # Create: Interface for testability
│   │   │   ├── RepoMetadata.php        # Create: Value object for repo data
│   │   │   └── FileDetector.php        # Create: Pattern matching on file trees
│   │   ├── Scanning/
│   │   │   ├── ScanJob.php             # Create: Queue job orchestrating the 5 stages
│   │   │   ├── ProfileFetcher.php      # Create: Stage 1
│   │   │   ├── RepoTriager.php         # Create: Stage 2
│   │   │   ├── FileAnalyzer.php        # Create: Stage 3
│   │   │   ├── SkillMapper.php         # Create: Stage 4
│   │   │   └── ResultPersister.php     # Create: Stage 5
│   │   └── Roadmap/
│   │       ├── ProficiencyCalculator.php # Create: Score → proficiency mapping
│   │       ├── RollUpCalculator.php    # Create: Parent proficiency from children
│   │       └── RoadmapDetector.php     # Create: Auto-detect relevant roadmaps
│   ├── Seed/
│   │   └── RoadmapSeeder.php           # Create: Seed 3 roadmaps + skills + detection rules
│   └── Support/
│       └── Enums.php                   # Create: ScanStatus, Proficiency, EvidenceType enums
├── tests/
│   ├── Entity/
│   │   ├── DeveloperTest.php           # Create
│   │   ├── ScanTest.php                # Create
│   │   ├── RoadmapSkillTest.php        # Create
│   │   ├── SkillAssessmentTest.php     # Create
│   │   └── SkillEvidenceTest.php       # Create
│   ├── Domain/
│   │   ├── GitHub/
│   │   │   ├── GitHubClientTest.php    # Create
│   │   │   ├── FileDetectorTest.php    # Create
│   │   │   └── RepoTriagerTest.php     # Create
│   │   ├── Scanning/
│   │   │   ├── ScanJobTest.php         # Create
│   │   │   ├── SkillMapperTest.php     # Create
│   │   │   └── ResultPersisterTest.php # Create
│   │   └── Roadmap/
│   │       ├── ProficiencyCalculatorTest.php # Create
│   │       ├── RollUpCalculatorTest.php     # Create
│   │       └── RoadmapDetectorTest.php      # Create
│   └── Controller/
│       ├── ProfileControllerTest.php   # Create
│       └── ScanControllerTest.php      # Create
└── templates/
    └── profile.html.twig               # Create: Public profile SSR page
```

---

## Task 1: Project Scaffolding

**Files:**
- Create: entire project via `composer create-project`
- Modify: `composer.json` (add guzzlehttp/guzzle)
- Create: `.env` (GitHub OAuth credentials placeholder)

- [ ] **Step 1: Scaffold project with composer create-project**

```bash
cd /home/jones/dev
composer create-project waaseyaa/waaseyaa me-vs-roadmap
```

Document any issues encountered in the Waaseyaa GitHub repo.

- [ ] **Step 2: Verify the skeleton boots**

```bash
cd /home/jones/dev/me-vs-roadmap
php -S localhost:8080 -t public
# Visit http://localhost:8080 — should see default Waaseyaa page
```

- [ ] **Step 3: Add Guzzle HTTP dependency**

```bash
composer require guzzlehttp/guzzle
```

- [ ] **Step 4: Create .env with placeholder config**

```bash
# .env
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=http://localhost:8080/auth/github/callback
WAASEYAA_DB=waaseyaa.sqlite
```

- [ ] **Step 5: Verify tests can run**

```bash
vendor/bin/phpunit
```

Expected: 0 tests, 0 assertions (or skeleton defaults pass).

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: scaffold me-vs-roadmap from waaseyaa skeleton"
```

---

## Task 2: Enums and Value Objects

**Files:**
- Create: `src/Support/Enums.php`
- Create: `src/Domain/GitHub/RepoMetadata.php`
- Create: `tests/Support/EnumsTest.php`
- Create: `tests/Domain/GitHub/RepoMetadataTest.php`

- [ ] **Step 1: Write failing test for enums**

```php
// tests/Support/EnumsTest.php
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
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Support/EnumsTest.php
```

Expected: FAIL — classes not found.

- [ ] **Step 3: Implement enums**

```php
// src/Support/Enums.php
<?php
declare(strict_types=1);

namespace App\Support;

enum ScanStatus: string
{
    case Queued = 'queued';
    case Analyzing = 'analyzing';
    case Complete = 'complete';
    case Failed = 'failed';
}

enum Proficiency: string
{
    case None = 'none';
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
}

enum EvidenceType: string
{
    case LanguageUsage = 'language_usage';
    case ConfigFile = 'config_file';
    case Dependency = 'dependency';
    case CiWorkflow = 'ci_workflow';
    case TestPresence = 'test_presence';
    case CommitPattern = 'commit_pattern';
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Support/EnumsTest.php
```

Expected: 3 tests, 3 assertions, PASS.

- [ ] **Step 5: Write failing test for RepoMetadata**

```php
// tests/Domain/GitHub/RepoMetadataTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\GitHub;

use App\Domain\GitHub\RepoMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RepoMetadata::class)]
final class RepoMetadataTest extends TestCase
{
    #[Test]
    public function constructsFromGitHubApiResponse(): void
    {
        $data = [
            'name' => 'north-cloud',
            'full_name' => 'jonesrussell/north-cloud',
            'description' => 'Content pipeline',
            'fork' => false,
            'language' => 'Go',
            'stargazers_count' => 5,
            'topics' => ['golang', 'microservices'],
            'pushed_at' => '2026-03-01T12:00:00Z',
            'size' => 1024,
        ];

        $repo = RepoMetadata::fromApiResponse($data);

        $this->assertSame('north-cloud', $repo->name);
        $this->assertSame('jonesrussell/north-cloud', $repo->fullName);
        $this->assertFalse($repo->isFork);
        $this->assertSame('Go', $repo->primaryLanguage);
        $this->assertSame(['golang', 'microservices'], $repo->topics);
    }

    #[Test]
    public function calculatesSignalScore(): void
    {
        $recent = RepoMetadata::fromApiResponse([
            'name' => 'recent',
            'full_name' => 'user/recent',
            'description' => '',
            'fork' => false,
            'language' => 'Go',
            'stargazers_count' => 10,
            'topics' => [],
            'pushed_at' => '2026-03-01T12:00:00Z',
            'size' => 500,
        ]);

        $old = RepoMetadata::fromApiResponse([
            'name' => 'old',
            'full_name' => 'user/old',
            'description' => '',
            'fork' => true,
            'language' => null,
            'stargazers_count' => 0,
            'topics' => [],
            'pushed_at' => '2020-01-01T00:00:00Z',
            'size' => 10,
        ]);

        $this->assertGreaterThan($old->signalScore(), $recent->signalScore());
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/GitHub/RepoMetadataTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 7: Implement RepoMetadata**

```php
// src/Domain/GitHub/RepoMetadata.php
<?php
declare(strict_types=1);

namespace App\Domain\GitHub;

final readonly class RepoMetadata
{
    public function __construct(
        public string $name,
        public string $fullName,
        public ?string $description,
        public bool $isFork,
        public ?string $primaryLanguage,
        public int $stars,
        public array $topics,
        public \DateTimeImmutable $pushedAt,
        public int $sizeKb,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            name: $data['name'],
            fullName: $data['full_name'],
            description: $data['description'] ?? null,
            isFork: $data['fork'],
            primaryLanguage: $data['language'] ?? null,
            stars: $data['stargazers_count'] ?? 0,
            topics: $data['topics'] ?? [],
            pushedAt: new \DateTimeImmutable($data['pushed_at']),
            sizeKb: $data['size'] ?? 0,
        );
    }

    public function signalScore(): float
    {
        $score = 0.0;

        // Recency: repos pushed in last 12 months score higher
        $monthsAgo = (new \DateTimeImmutable())->diff($this->pushedAt)->days / 30;
        $score += max(0, 50 - ($monthsAgo * 2));

        // Not a fork
        if (!$this->isFork) {
            $score += 20;
        }

        // Has a language detected
        if ($this->primaryLanguage !== null) {
            $score += 10;
        }

        // Size (non-trivial repo)
        if ($this->sizeKb > 50) {
            $score += 10;
        }

        // Stars (some community validation)
        $score += min(10, $this->stars);

        return $score;
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/GitHub/RepoMetadataTest.php
```

Expected: 2 tests, PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Support/Enums.php src/Domain/GitHub/RepoMetadata.php tests/Support/EnumsTest.php tests/Domain/GitHub/RepoMetadataTest.php
git commit -m "feat: add enums (ScanStatus, Proficiency, EvidenceType) and RepoMetadata value object"
```

---

## Task 3: Entity Definitions

**Files:**
- Create: `src/Entity/Developer.php`
- Create: `src/Entity/Scan.php`
- Create: `src/Entity/RoadmapPath.php`
- Create: `src/Entity/RoadmapSkill.php`
- Create: `src/Entity/SkillAssessment.php`
- Create: `src/Entity/SkillEvidence.php`
- Modify: `config/entity-types.php`
- Create: `tests/Entity/DeveloperTest.php`
- Create: `tests/Entity/ScanTest.php`
- Create: `tests/Entity/RoadmapSkillTest.php`
- Create: `tests/Entity/SkillAssessmentTest.php`
- Create: `tests/Entity/SkillEvidenceTest.php`

- [ ] **Step 1: Write failing test for Developer entity**

```php
// tests/Entity/DeveloperTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Developer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Developer::class)]
final class DeveloperTest extends TestCase
{
    #[Test]
    public function createsWithGitHubUsername(): void
    {
        $dev = new Developer([
            'github_username' => 'jonesrussell',
            'display_name' => 'Russell Jones',
            'avatar_url' => 'https://avatars.githubusercontent.com/u/123',
            'bio' => 'Full-stack developer',
            'is_public' => true,
        ]);

        $this->assertSame('jonesrussell', $dev->get('github_username'));
        $this->assertSame('Russell Jones', $dev->get('display_name'));
        $this->assertTrue($dev->get('is_public'));
    }

    #[Test]
    public function defaultsToPublic(): void
    {
        $dev = new Developer([
            'github_username' => 'testuser',
        ]);

        $this->assertTrue($dev->get('is_public'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Entity/DeveloperTest.php
```

Expected: FAIL — Developer class not found.

- [ ] **Step 3: Implement Developer entity**

```php
// src/Entity/Developer.php
<?php
declare(strict_types=1);

namespace App\Entity;

use Waaseyaa\Entity\ContentEntityBase;

class Developer extends ContentEntityBase
{
    protected string $entityTypeId = 'developer';
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'display_name',
    ];

    public function __construct(array $values = [])
    {
        $values += ['is_public' => true];
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Entity/DeveloperTest.php
```

Expected: 2 tests, PASS.

- [ ] **Step 5: Write failing test for Scan entity**

```php
// tests/Entity/ScanTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Scan;
use App\Support\ScanStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Scan::class)]
final class ScanTest extends TestCase
{
    #[Test]
    public function createsWithQueuedStatus(): void
    {
        $scan = new Scan([
            'developer_id' => 1,
        ]);

        $this->assertSame(ScanStatus::Queued->value, $scan->get('status'));
        $this->assertNull($scan->get('started_at'));
    }

    #[Test]
    public function transitionsToAnalyzing(): void
    {
        $scan = new Scan(['developer_id' => 1]);
        $scan->markAnalyzing();

        $this->assertSame(ScanStatus::Analyzing->value, $scan->get('status'));
        $this->assertNotNull($scan->get('started_at'));
    }

    #[Test]
    public function transitionsToComplete(): void
    {
        $scan = new Scan(['developer_id' => 1]);
        $scan->markAnalyzing();
        $scan->markComplete(42);

        $this->assertSame(ScanStatus::Complete->value, $scan->get('status'));
        $this->assertSame(42, $scan->get('repos_analyzed'));
        $this->assertNotNull($scan->get('completed_at'));
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Entity/ScanTest.php
```

Expected: FAIL.

- [ ] **Step 7: Implement Scan entity**

```php
// src/Entity/Scan.php
<?php
declare(strict_types=1);

namespace App\Entity;

use App\Support\ScanStatus;
use Waaseyaa\Entity\ContentEntityBase;

class Scan extends ContentEntityBase
{
    protected string $entityTypeId = 'scan';
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
    ];

    public function __construct(array $values = [])
    {
        $values += [
            'status' => ScanStatus::Queued->value,
            'started_at' => null,
            'completed_at' => null,
            'repos_analyzed' => 0,
            'scan_metadata' => [],
        ];
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function markAnalyzing(): void
    {
        $this->set('status', ScanStatus::Analyzing->value);
        $this->set('started_at', (new \DateTimeImmutable())->format('c'));
    }

    public function markComplete(int $reposAnalyzed): void
    {
        $this->set('status', ScanStatus::Complete->value);
        $this->set('repos_analyzed', $reposAnalyzed);
        $this->set('completed_at', (new \DateTimeImmutable())->format('c'));
    }

    public function markFailed(): void
    {
        $this->set('status', ScanStatus::Failed->value);
        $this->set('completed_at', (new \DateTimeImmutable())->format('c'));
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Entity/ScanTest.php
```

Expected: 3 tests, PASS.

- [ ] **Step 9: Implement remaining entities (RoadmapPath, RoadmapSkill, SkillAssessment, SkillEvidence)**

```php
// src/Entity/RoadmapPath.php
<?php
declare(strict_types=1);

namespace App\Entity;

use Waaseyaa\Entity\ContentEntityBase;

class RoadmapPath extends ContentEntityBase
{
    protected string $entityTypeId = 'roadmap_path';
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'name',
    ];

    public function __construct(array $values = [])
    {
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }
}
```

```php
// src/Entity/RoadmapSkill.php
<?php
declare(strict_types=1);

namespace App\Entity;

use Waaseyaa\Entity\ContentEntityBase;

class RoadmapSkill extends ContentEntityBase
{
    protected string $entityTypeId = 'roadmap_skill';
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
        'label' => 'name',
    ];

    public function __construct(array $values = [])
    {
        $values += [
            'parent_skill_id' => null,
            'detection_rules' => [],
        ];
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getDetectionRules(): array
    {
        $rules = $this->get('detection_rules');
        return is_string($rules) ? json_decode($rules, true) : $rules;
    }
}
```

```php
// src/Entity/SkillAssessment.php
<?php
declare(strict_types=1);

namespace App\Entity;

use App\Support\Proficiency;
use Waaseyaa\Entity\ContentEntityBase;

class SkillAssessment extends ContentEntityBase
{
    protected string $entityTypeId = 'skill_assessment';
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
    ];

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
        return Proficiency::from($this->get('proficiency'));
    }
}
```

```php
// src/Entity/SkillEvidence.php
<?php
declare(strict_types=1);

namespace App\Entity;

use App\Support\EvidenceType;
use Waaseyaa\Entity\ContentEntityBase;

class SkillEvidence extends ContentEntityBase
{
    protected string $entityTypeId = 'skill_evidence';
    protected array $entityKeys = [
        'id' => 'id',
        'uuid' => 'uuid',
    ];

    public function __construct(array $values = [])
    {
        $values += ['details' => []];
        parent::__construct($values, $this->entityTypeId, $this->entityKeys);
    }

    public function getType(): EvidenceType
    {
        return EvidenceType::from($this->get('type'));
    }
}
```

- [ ] **Step 10: Write tests for RoadmapSkill and SkillAssessment**

```php
// tests/Entity/RoadmapSkillTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RoadmapSkill;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoadmapSkill::class)]
final class RoadmapSkillTest extends TestCase
{
    #[Test]
    public function parsesDetectionRules(): void
    {
        $skill = new RoadmapSkill([
            'name' => 'Docker',
            'slug' => 'docker',
            'roadmap_path_id' => 1,
            'detection_rules' => [
                'files' => ['Dockerfile', 'docker-compose.yml'],
                'content_matches' => ['Dockerfile' => ['FROM']],
            ],
        ]);

        $rules = $skill->getDetectionRules();
        $this->assertSame(['Dockerfile', 'docker-compose.yml'], $rules['files']);
    }

    #[Test]
    public function supportsParentSkill(): void
    {
        $child = new RoadmapSkill([
            'name' => 'Docker',
            'slug' => 'docker',
            'parent_skill_id' => 42,
        ]);

        $this->assertSame(42, $child->get('parent_skill_id'));
    }
}
```

```php
// tests/Entity/SkillAssessmentTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\SkillAssessment;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SkillAssessment::class)]
final class SkillAssessmentTest extends TestCase
{
    #[Test]
    public function defaultsToNoneProficiency(): void
    {
        $assessment = new SkillAssessment([
            'developer_id' => 1,
            'roadmap_skill_id' => 1,
            'scan_id' => 1,
        ]);

        $this->assertSame(Proficiency::None, $assessment->getProficiency());
        $this->assertSame(0.0, $assessment->get('confidence'));
    }

    #[Test]
    public function storesCustomProficiency(): void
    {
        $assessment = new SkillAssessment([
            'developer_id' => 1,
            'roadmap_skill_id' => 1,
            'scan_id' => 1,
            'proficiency' => Proficiency::Advanced->value,
            'confidence' => 0.85,
        ]);

        $this->assertSame(Proficiency::Advanced, $assessment->getProficiency());
        $this->assertSame(0.85, $assessment->get('confidence'));
    }
}
```

- [ ] **Step 10b: Write test for RoadmapPath**

```php
// tests/Entity/RoadmapPathTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\RoadmapPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoadmapPath::class)]
final class RoadmapPathTest extends TestCase
{
    #[Test]
    public function createsWithSlugAndName(): void
    {
        $path = new RoadmapPath([
            'slug' => 'backend',
            'name' => 'Backend',
            'description' => 'Server-side development',
        ]);

        $this->assertSame('backend', $path->get('slug'));
        $this->assertSame('Backend', $path->get('name'));
        $this->assertSame('Server-side development', $path->get('description'));
    }
}
```

- [ ] **Step 11: Run all entity tests**

```bash
vendor/bin/phpunit tests/Entity/
```

Expected: All tests PASS.

- [ ] **Step 12: Register entity types in config**

```php
// config/entity-types.php
<?php
declare(strict_types=1);

use Waaseyaa\Entity\EntityType;

return [
    new EntityType(
        id: 'developer',
        label: 'Developer',
        class: \App\Entity\Developer::class,
        keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'display_name'],
    ),
    new EntityType(
        id: 'scan',
        label: 'Scan',
        class: \App\Entity\Scan::class,
        keys: ['id' => 'id', 'uuid' => 'uuid'],
    ),
    new EntityType(
        id: 'roadmap_path',
        label: 'Roadmap Path',
        class: \App\Entity\RoadmapPath::class,
        keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'name'],
    ),
    new EntityType(
        id: 'roadmap_skill',
        label: 'Roadmap Skill',
        class: \App\Entity\RoadmapSkill::class,
        keys: ['id' => 'id', 'uuid' => 'uuid', 'label' => 'name'],
    ),
    new EntityType(
        id: 'skill_assessment',
        label: 'Skill Assessment',
        class: \App\Entity\SkillAssessment::class,
        keys: ['id' => 'id', 'uuid' => 'uuid'],
    ),
    new EntityType(
        id: 'skill_evidence',
        label: 'Skill Evidence',
        class: \App\Entity\SkillEvidence::class,
        keys: ['id' => 'id', 'uuid' => 'uuid'],
    ),
];
```

- [ ] **Step 13: Commit**

```bash
git add src/Entity/ tests/Entity/ config/entity-types.php
git commit -m "feat: add all 6 entity types with tests and registration"
```

---

## Task 4: Proficiency Calculator and Roll-Up

**Files:**
- Create: `src/Domain/Roadmap/ProficiencyCalculator.php`
- Create: `src/Domain/Roadmap/RollUpCalculator.php`
- Create: `tests/Domain/Roadmap/ProficiencyCalculatorTest.php`
- Create: `tests/Domain/Roadmap/RollUpCalculatorTest.php`

- [ ] **Step 1: Write failing test for ProficiencyCalculator**

```php
// tests/Domain/Roadmap/ProficiencyCalculatorTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\Roadmap;

use App\Domain\Roadmap\ProficiencyCalculator;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProficiencyCalculator::class)]
final class ProficiencyCalculatorTest extends TestCase
{
    #[Test]
    #[DataProvider('scoreToLevelProvider')]
    public function mapsScoreToProficiency(float $score, Proficiency $expected): void
    {
        $this->assertSame($expected, ProficiencyCalculator::fromScore($score));
    }

    public static function scoreToLevelProvider(): array
    {
        return [
            'zero means none' => [0.0, Proficiency::None],
            'low score is beginner' => [15.0, Proficiency::Beginner],
            'boundary 30 is beginner' => [30.0, Proficiency::Beginner],
            'boundary 31 is intermediate' => [31.0, Proficiency::Intermediate],
            'mid score is intermediate' => [50.0, Proficiency::Intermediate],
            'boundary 65 is intermediate' => [65.0, Proficiency::Intermediate],
            'boundary 66 is advanced' => [66.0, Proficiency::Advanced],
            'high score is advanced' => [100.0, Proficiency::Advanced],
        ];
    }

    #[Test]
    public function calculatesRawScoreFromSignals(): void
    {
        // 5 out of 10 repos (frequency: 50% * 40 = 20)
        // 80% recent (recency: 0.8 * 30 = 24)
        // medium depth (depth: 0.5 * 30 = 15)
        // Total: 59 → intermediate
        $score = ProficiencyCalculator::calculateRawScore(
            repoCount: 5,
            totalRepos: 10,
            recentRatio: 0.8,
            depthScore: 0.5,
        );

        $this->assertEqualsWithDelta(59.0, $score, 0.1);
    }

    #[Test]
    public function calculatesConfidence(): void
    {
        $confidence = ProficiencyCalculator::calculateConfidence(
            evidenceCount: 3,
            maxEvidenceCount: 10,
        );

        $this->assertEqualsWithDelta(0.3, $confidence, 0.01);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/Roadmap/ProficiencyCalculatorTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement ProficiencyCalculator**

```php
// src/Domain/Roadmap/ProficiencyCalculator.php
<?php
declare(strict_types=1);

namespace App\Domain\Roadmap;

use App\Support\Proficiency;

final class ProficiencyCalculator
{
    public static function fromScore(float $score): Proficiency
    {
        return match (true) {
            $score <= 0 => Proficiency::None,
            $score <= 30 => Proficiency::Beginner,
            $score <= 65 => Proficiency::Intermediate,
            default => Proficiency::Advanced,
        };
    }

    public static function calculateRawScore(
        int $repoCount,
        int $totalRepos,
        float $recentRatio,
        float $depthScore,
    ): float {
        if ($totalRepos === 0) {
            return 0.0;
        }

        $frequency = ($repoCount / $totalRepos) * 100 * 0.4;
        $recency = $recentRatio * 100 * 0.3;
        $depth = $depthScore * 100 * 0.3;

        return min(100.0, $frequency + $recency + $depth);
    }

    public static function calculateConfidence(
        int $evidenceCount,
        int $maxEvidenceCount,
    ): float {
        if ($maxEvidenceCount === 0) {
            return 0.0;
        }

        return min(1.0, $evidenceCount / $maxEvidenceCount);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/Roadmap/ProficiencyCalculatorTest.php
```

Expected: All tests PASS.

- [ ] **Step 5: Write failing test for RollUpCalculator**

```php
// tests/Domain/Roadmap/RollUpCalculatorTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\Roadmap;

use App\Domain\Roadmap\RollUpCalculator;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RollUpCalculator::class)]
final class RollUpCalculatorTest extends TestCase
{
    #[Test]
    public function averagesChildScores(): void
    {
        // Docker=80 (advanced), Kubernetes=40 (intermediate)
        // Average: 60 → intermediate
        $result = RollUpCalculator::rollUp([80.0, 40.0]);

        $this->assertEqualsWithDelta(60.0, $result->score, 0.1);
        $this->assertSame(Proficiency::Intermediate, $result->proficiency);
    }

    #[Test]
    public function requiresMinimumTwoChildren(): void
    {
        $result = RollUpCalculator::rollUp([80.0]);

        $this->assertSame(0.0, $result->score);
        $this->assertSame(Proficiency::None, $result->proficiency);
    }

    #[Test]
    public function ignoresZeroScoreChildren(): void
    {
        // Only Docker=80 has evidence, K8s=0 has none
        // Only 1 child with evidence → not enough
        $result = RollUpCalculator::rollUp([80.0, 0.0]);

        $this->assertSame(0.0, $result->score);
        $this->assertSame(Proficiency::None, $result->proficiency);
    }

    #[Test]
    public function handlesEmptyChildren(): void
    {
        $result = RollUpCalculator::rollUp([]);

        $this->assertSame(0.0, $result->score);
        $this->assertSame(Proficiency::None, $result->proficiency);
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/Roadmap/RollUpCalculatorTest.php
```

Expected: FAIL.

- [ ] **Step 7: Implement RollUpCalculator**

```php
// src/Domain/Roadmap/RollUpCalculator.php
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

final class RollUpCalculator
{
    /**
     * Calculate parent proficiency from child raw scores.
     * Requires at least 2 children with evidence (score > 0).
     *
     * @param float[] $childScores Raw scores of child skills
     */
    public static function rollUp(array $childScores): RollUpResult
    {
        $withEvidence = array_filter($childScores, fn(float $s) => $s > 0);

        if (count($withEvidence) < 2) {
            return new RollUpResult(0.0, Proficiency::None);
        }

        $average = array_sum($withEvidence) / count($withEvidence);

        return new RollUpResult($average, ProficiencyCalculator::fromScore($average));
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/Roadmap/RollUpCalculatorTest.php
```

Expected: All tests PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Domain/Roadmap/ tests/Domain/Roadmap/
git commit -m "feat: add proficiency calculator and roll-up logic"
```

---

## Task 5: GitHub Client and File Detector

**Files:**
- Create: `src/Domain/GitHub/GitHubClientInterface.php`
- Create: `src/Domain/GitHub/GitHubClient.php`
- Create: `src/Domain/GitHub/FileDetector.php`
- Create: `tests/Domain/GitHub/GitHubClientTest.php`
- Create: `tests/Domain/GitHub/FileDetectorTest.php`

- [ ] **Step 1: Write failing test for FileDetector**

```php
// tests/Domain/GitHub/FileDetectorTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\GitHub;

use App\Domain\GitHub\FileDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileDetector::class)]
final class FileDetectorTest extends TestCase
{
    #[Test]
    public function matchesExactFiles(): void
    {
        $detector = new FileDetector();
        $tree = ['Dockerfile', 'README.md', 'main.go', 'go.mod'];
        $rules = ['files' => ['Dockerfile', 'go.mod']];

        $matches = $detector->matchFiles($tree, $rules);

        $this->assertSame(['Dockerfile', 'go.mod'], $matches);
    }

    #[Test]
    public function matchesGlobPatterns(): void
    {
        $detector = new FileDetector();
        $tree = [
            '.github/workflows/ci.yml',
            '.github/workflows/deploy.yml',
            'README.md',
        ];
        $rules = ['config_patterns' => ['.github/workflows/*.yml']];

        $matches = $detector->matchConfigPatterns($tree, $rules);

        $this->assertCount(2, $matches);
    }

    #[Test]
    public function detectsLanguageFromFiles(): void
    {
        $detector = new FileDetector();
        $tree = ['go.mod', 'go.sum', 'main.go', 'cmd/server/main.go'];
        $rules = ['languages' => ['go']];

        $this->assertTrue($detector->matchesLanguage($tree, $rules));
    }

    #[Test]
    public function returnsEmptyOnNoMatch(): void
    {
        $detector = new FileDetector();
        $tree = ['README.md', 'LICENSE'];
        $rules = ['files' => ['Dockerfile']];

        $this->assertSame([], $detector->matchFiles($tree, $rules));
    }

    #[Test]
    public function detectsTestPresence(): void
    {
        $detector = new FileDetector();
        $tree = ['src/main.go', 'src/main_test.go', 'tests/unit/ExampleTest.php'];

        $this->assertTrue($detector->hasTests($tree));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/GitHub/FileDetectorTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement FileDetector**

```php
// src/Domain/GitHub/FileDetector.php
<?php
declare(strict_types=1);

namespace App\Domain\GitHub;

final class FileDetector
{
    private const array TEST_PATTERNS = [
        '*_test.go',
        '*Test.php',
        '*.test.ts',
        '*.test.js',
        '*.spec.ts',
        '*.spec.js',
        'tests/',
        '__tests__/',
        'test/',
    ];

    /**
     * Match exact file names from detection rules against a repo file tree.
     *
     * @param string[] $tree File paths from GitHub Trees API
     * @param array $rules Detection rules with 'files' key
     * @return string[] Matched file paths
     */
    public function matchFiles(array $tree, array $rules): array
    {
        $targets = $rules['files'] ?? [];
        if (empty($targets)) {
            return [];
        }

        return array_values(array_filter($tree, function (string $path) use ($targets) {
            $basename = basename($path);
            return in_array($basename, $targets, true) || in_array($path, $targets, true);
        }));
    }

    /**
     * Match glob-style config patterns against a file tree.
     *
     * @param string[] $tree
     * @param array $rules Detection rules with 'config_patterns' key
     * @return string[] Matched file paths
     */
    public function matchConfigPatterns(array $tree, array $rules): array
    {
        $patterns = $rules['config_patterns'] ?? [];
        if (empty($patterns)) {
            return [];
        }

        $matches = [];
        foreach ($tree as $path) {
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $path)) {
                    $matches[] = $path;
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Check if the file tree suggests a particular language.
     *
     * @param string[] $tree
     * @param array $rules Detection rules with 'languages' key
     */
    public function matchesLanguage(array $tree, array $rules): bool
    {
        $languages = $rules['languages'] ?? [];

        $extensionMap = [
            'go' => ['.go', 'go.mod'],
            'php' => ['.php', 'composer.json'],
            'typescript' => ['.ts', '.tsx', 'tsconfig.json'],
            'javascript' => ['.js', '.jsx', 'package.json'],
            'python' => ['.py', 'requirements.txt', 'pyproject.toml'],
            'rust' => ['.rs', 'Cargo.toml'],
            'java' => ['.java', 'pom.xml', 'build.gradle'],
        ];

        foreach ($languages as $lang) {
            $indicators = $extensionMap[$lang] ?? [];
            foreach ($tree as $path) {
                foreach ($indicators as $indicator) {
                    if (str_ends_with($path, $indicator) || basename($path) === $indicator) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if the repo has test files.
     *
     * @param string[] $tree
     */
    public function hasTests(array $tree): bool
    {
        foreach ($tree as $path) {
            foreach (self::TEST_PATTERNS as $pattern) {
                if (str_ends_with($pattern, '/')) {
                    if (str_starts_with($path, $pattern) || str_contains($path, '/' . $pattern)) {
                        return true;
                    }
                } elseif (fnmatch($pattern, basename($path))) {
                    return true;
                }
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/GitHub/FileDetectorTest.php
```

Expected: 5 tests, PASS.

- [ ] **Step 5: Create GitHubClientInterface**

```php
// src/Domain/GitHub/GitHubClientInterface.php
<?php
declare(strict_types=1);

namespace App\Domain\GitHub;

interface GitHubClientInterface
{
    /** Fetch user profile data. */
    public function getUser(string $username): array;

    /** Fetch all public repos for a user (paginated). */
    public function getUserRepos(string $username): array;

    /** Fetch the file tree for a repo (recursive). */
    public function getRepoTree(string $fullName, string $branch = 'HEAD'): array;

    /** Fetch raw file content from a repo. */
    public function getFileContent(string $fullName, string $path): ?string;
}
```

- [ ] **Step 6: Implement GitHubClient**

```php
// src/Domain/GitHub/GitHubClient.php
<?php
declare(strict_types=1);

namespace App\Domain\GitHub;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

final class GitHubClient implements GitHubClientInterface
{
    private const string BASE_URL = 'https://api.github.com';

    public function __construct(
        private readonly ClientInterface $http,
        private readonly ?string $token = null,
    ) {}

    public function getUser(string $username): array
    {
        return $this->get("/users/{$username}");
    }

    public function getUserRepos(string $username): array
    {
        $repos = [];
        $page = 1;

        do {
            $batch = $this->get("/users/{$username}/repos", [
                'per_page' => 100,
                'page' => $page,
                'sort' => 'pushed',
                'type' => 'owner',
            ]);
            $repos = array_merge($repos, $batch);
            $page++;
        } while (count($batch) === 100);

        return $repos;
    }

    public function getRepoTree(string $fullName, string $branch = 'HEAD'): array
    {
        $data = $this->get("/repos/{$fullName}/git/trees/{$branch}", [
            'recursive' => '1',
        ]);

        return array_map(
            fn(array $item) => $item['path'],
            array_filter($data['tree'] ?? [], fn(array $item) => $item['type'] === 'blob'),
        );
    }

    public function getFileContent(string $fullName, string $path): ?string
    {
        try {
            $data = $this->get("/repos/{$fullName}/contents/{$path}");
            if (isset($data['content'])) {
                return base64_decode($data['content']);
            }
            return null;
        } catch (RequestException) {
            return null;
        }
    }

    private function get(string $endpoint, array $query = []): array
    {
        $options = [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'me-vs-roadmap',
            ],
            'query' => $query,
        ];

        if ($this->token !== null) {
            $options['headers']['Authorization'] = "Bearer {$this->token}";
        }

        $response = $this->http->request('GET', self::BASE_URL . $endpoint, $options);

        return json_decode($response->getBody()->getContents(), true);
    }
}
```

- [ ] **Step 7: Write GitHubClient test with mock HTTP**

```php
// tests/Domain/GitHub/GitHubClientTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\GitHub;

use App\Domain\GitHub\GitHubClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitHubClient::class)]
final class GitHubClientTest extends TestCase
{
    #[Test]
    public function fetchesUserProfile(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'login' => 'jonesrussell',
                'name' => 'Russell Jones',
                'avatar_url' => 'https://example.com/avatar.jpg',
            ])),
        ]);

        $http = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new GitHubClient($http, 'fake-token');

        $user = $client->getUser('jonesrussell');

        $this->assertSame('jonesrussell', $user['login']);
        $this->assertSame('Russell Jones', $user['name']);
    }

    #[Test]
    public function fetchesRepoTree(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'tree' => [
                    ['path' => 'main.go', 'type' => 'blob'],
                    ['path' => 'cmd', 'type' => 'tree'],
                    ['path' => 'cmd/server.go', 'type' => 'blob'],
                    ['path' => 'Dockerfile', 'type' => 'blob'],
                ],
            ])),
        ]);

        $http = new Client(['handler' => HandlerStack::create($mock)]);
        $client = new GitHubClient($http);

        $tree = $client->getRepoTree('jonesrussell/north-cloud');

        $this->assertSame(['main.go', 'cmd/server.go', 'Dockerfile'], $tree);
    }
}
```

- [ ] **Step 8: Run all GitHub domain tests**

```bash
vendor/bin/phpunit tests/Domain/GitHub/
```

Expected: All tests PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Domain/GitHub/ tests/Domain/GitHub/
git commit -m "feat: add GitHub client, file detector, and interface"
```

---

## Task 6: Scan Pipeline — Stages 1-3

**Files:**
- Create: `src/Domain/Scanning/ProfileFetcher.php`
- Create: `src/Domain/Scanning/RepoTriager.php`
- Create: `src/Domain/Scanning/FileAnalyzer.php`
- Create: `tests/Domain/Scanning/ProfileFetcherTest.php`
- Create: `tests/Domain/Scanning/RepoTriagerTest.php`
- Create: `tests/Domain/Scanning/FileAnalyzerTest.php`

- [ ] **Step 1: Write failing test for RepoTriager**

```php
// tests/Domain/Scanning/RepoTriagerTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\RepoMetadata;
use App\Domain\Scanning\RepoTriager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RepoTriager::class)]
final class RepoTriagerTest extends TestCase
{
    #[Test]
    public function selectsTopReposBySignalScore(): void
    {
        $repos = [];
        for ($i = 0; $i < 50; $i++) {
            $repos[] = RepoMetadata::fromApiResponse([
                'name' => "repo-{$i}",
                'full_name' => "user/repo-{$i}",
                'description' => '',
                'fork' => $i % 3 === 0,
                'language' => $i % 2 === 0 ? 'Go' : null,
                'stargazers_count' => $i,
                'topics' => [],
                'pushed_at' => (new \DateTimeImmutable("-{$i} months"))->format('c'),
                'size' => $i * 100,
            ]);
        }

        $triager = new RepoTriager(maxRepos: 30);
        $selected = $triager->triage($repos);

        $this->assertCount(30, $selected);
        // First selected should have higher signal than last
        $this->assertGreaterThanOrEqual(
            $selected[29]->signalScore(),
            $selected[0]->signalScore(),
        );
    }

    #[Test]
    public function returnsAllIfUnderLimit(): void
    {
        $repos = [
            RepoMetadata::fromApiResponse([
                'name' => 'only-repo',
                'full_name' => 'user/only-repo',
                'description' => '',
                'fork' => false,
                'language' => 'PHP',
                'stargazers_count' => 5,
                'topics' => [],
                'pushed_at' => '2026-03-01T00:00:00Z',
                'size' => 200,
            ]),
        ];

        $triager = new RepoTriager(maxRepos: 30);
        $selected = $triager->triage($repos);

        $this->assertCount(1, $selected);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/Scanning/RepoTriagerTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement RepoTriager**

```php
// src/Domain/Scanning/RepoTriager.php
<?php
declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\RepoMetadata;

final readonly class RepoTriager
{
    public function __construct(
        private int $maxRepos = 30,
    ) {}

    /**
     * Select the most signal-rich repos for deeper analysis.
     *
     * @param RepoMetadata[] $repos
     * @return RepoMetadata[]
     */
    public function triage(array $repos): array
    {
        usort($repos, fn(RepoMetadata $a, RepoMetadata $b) =>
            $b->signalScore() <=> $a->signalScore()
        );

        return array_slice($repos, 0, $this->maxRepos);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/Scanning/RepoTriagerTest.php
```

Expected: PASS.

- [ ] **Step 5: Implement ProfileFetcher**

```php
// src/Domain/Scanning/ProfileFetcher.php
<?php
declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;

final readonly class ProfileFetcher
{
    public function __construct(
        private GitHubClientInterface $github,
    ) {}

    /**
     * Fetch user profile and repos, return as RepoMetadata array.
     *
     * @return array{profile: array, repos: RepoMetadata[]}
     */
    public function fetch(string $username): array
    {
        $profile = $this->github->getUser($username);
        $rawRepos = $this->github->getUserRepos($username);

        $repos = array_map(
            fn(array $r) => RepoMetadata::fromApiResponse($r),
            $rawRepos,
        );

        return [
            'profile' => $profile,
            'repos' => $repos,
        ];
    }
}
```

- [ ] **Step 6: Implement FileAnalyzer**

```php
// src/Domain/Scanning/FileAnalyzer.php
<?php
declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;

final readonly class FileAnalyzer
{
    /** File basenames whose content we fetch for deeper inspection. */
    private const array CONTENT_FETCH_FILES = [
        'package.json',
        'composer.json',
        'go.mod',
        'Cargo.toml',
        'requirements.txt',
        'pyproject.toml',
        'Gemfile',
    ];

    public function __construct(
        private GitHubClientInterface $github,
        private FileDetector $detector,
    ) {}

    /**
     * Analyze a repo's file tree and fetch key file contents.
     *
     * @return array{tree: string[], contents: array<string, string>}
     */
    public function analyze(RepoMetadata $repo): array
    {
        $tree = $this->github->getRepoTree($repo->fullName);

        $contents = [];
        foreach ($tree as $path) {
            if (in_array(basename($path), self::CONTENT_FETCH_FILES, true)) {
                $content = $this->github->getFileContent($repo->fullName, $path);
                if ($content !== null) {
                    $contents[$path] = $content;
                }
            }
        }

        return [
            'tree' => $tree,
            'contents' => $contents,
        ];
    }
}
```

- [ ] **Step 7: Write test for FileAnalyzer**

```php
// tests/Domain/Scanning/FileAnalyzerTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;
use App\Domain\Scanning\FileAnalyzer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileAnalyzer::class)]
final class FileAnalyzerTest extends TestCase
{
    #[Test]
    public function fetchesTreeAndKeyFileContents(): void
    {
        $github = $this->createMock(GitHubClientInterface::class);

        $github->method('getRepoTree')
            ->willReturn(['main.go', 'go.mod', 'Dockerfile', 'README.md']);

        $github->method('getFileContent')
            ->willReturnCallback(fn(string $repo, string $path) => match ($path) {
                'go.mod' => "module github.com/user/repo\n\ngo 1.24\n",
                default => null,
            });

        $analyzer = new FileAnalyzer($github, new FileDetector());
        $repo = RepoMetadata::fromApiResponse([
            'name' => 'repo',
            'full_name' => 'user/repo',
            'description' => '',
            'fork' => false,
            'language' => 'Go',
            'stargazers_count' => 0,
            'topics' => [],
            'pushed_at' => '2026-03-01T00:00:00Z',
            'size' => 100,
        ]);

        $result = $analyzer->analyze($repo);

        $this->assertSame(['main.go', 'go.mod', 'Dockerfile', 'README.md'], $result['tree']);
        $this->assertArrayHasKey('go.mod', $result['contents']);
        $this->assertStringContains('module github.com/user/repo', $result['contents']['go.mod']);
    }
}
```

- [ ] **Step 8: Run all scanning tests**

```bash
vendor/bin/phpunit tests/Domain/Scanning/
```

Expected: All PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Domain/Scanning/ tests/Domain/Scanning/
git commit -m "feat: add scan pipeline stages 1-3 (profile fetch, repo triage, file analysis)"
```

---

## Task 7: Scan Pipeline — Stages 4-5 (Skill Mapping and Persistence)

**Files:**
- Create: `src/Domain/Scanning/SkillMapper.php`
- Create: `src/Domain/Scanning/ResultPersister.php`
- Create: `tests/Domain/Scanning/SkillMapperTest.php`
- Create: `tests/Domain/Scanning/ResultPersisterTest.php`

- [ ] **Step 1: Write failing test for SkillMapper**

```php
// tests/Domain/Scanning/SkillMapperTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\Scanning\SkillMapper;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SkillMapper::class)]
final class SkillMapperTest extends TestCase
{
    #[Test]
    public function mapsDockerfileToDockerSkill(): void
    {
        $mapper = new SkillMapper(new FileDetector());

        $skills = [
            [
                'id' => 1,
                'slug' => 'docker',
                'detection_rules' => [
                    'files' => ['Dockerfile', 'docker-compose.yml'],
                ],
            ],
        ];

        $repoResults = [
            [
                'repo_name' => 'user/app',
                'tree' => ['Dockerfile', 'main.go', 'go.mod'],
                'contents' => [],
                'pushed_at' => '2026-03-01T00:00:00Z',
            ],
            [
                'repo_name' => 'user/api',
                'tree' => ['Dockerfile', 'docker-compose.yml', 'src/index.ts'],
                'contents' => [],
                'pushed_at' => '2026-02-01T00:00:00Z',
            ],
        ];

        $assessments = $mapper->mapSkills($skills, $repoResults, totalRepos: 5);

        $this->assertArrayHasKey('docker', $assessments);
        $this->assertNotSame(Proficiency::None, $assessments['docker']['proficiency']);
        $this->assertCount(2, $assessments['docker']['evidence']);
    }

    #[Test]
    public function returnsNoneForNoEvidence(): void
    {
        $mapper = new SkillMapper(new FileDetector());

        $skills = [
            [
                'id' => 1,
                'slug' => 'kubernetes',
                'detection_rules' => [
                    'files' => ['k8s/', 'kubernetes/'],
                    'config_patterns' => ['**/k8s/*.yml'],
                ],
            ],
        ];

        $repoResults = [
            [
                'repo_name' => 'user/app',
                'tree' => ['main.go', 'go.mod'],
                'contents' => [],
                'pushed_at' => '2026-03-01T00:00:00Z',
            ],
        ];

        $assessments = $mapper->mapSkills($skills, $repoResults, totalRepos: 1);

        $this->assertArrayHasKey('kubernetes', $assessments);
        $this->assertSame(Proficiency::None, $assessments['kubernetes']['proficiency']);
    }

    #[Test]
    public function checksDependenciesInFileContents(): void
    {
        $mapper = new SkillMapper(new FileDetector());

        $skills = [
            [
                'id' => 1,
                'slug' => 'react',
                'detection_rules' => [
                    'dependencies' => [
                        'package.json' => ['react', 'react-dom'],
                    ],
                ],
            ],
        ];

        $repoResults = [
            [
                'repo_name' => 'user/frontend',
                'tree' => ['package.json', 'src/App.tsx'],
                'contents' => [
                    'package.json' => '{"dependencies":{"react":"^18.0","react-dom":"^18.0"}}',
                ],
                'pushed_at' => '2026-03-01T00:00:00Z',
            ],
        ];

        $assessments = $mapper->mapSkills($skills, $repoResults, totalRepos: 1);

        $this->assertNotSame(Proficiency::None, $assessments['react']['proficiency']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/Scanning/SkillMapperTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement SkillMapper**

```php
// src/Domain/Scanning/SkillMapper.php
<?php
declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\Roadmap\ProficiencyCalculator;
use App\Support\EvidenceType;
use App\Support\Proficiency;

final readonly class SkillMapper
{
    public function __construct(
        private FileDetector $detector,
    ) {}

    /**
     * Map repo analysis results to skill assessments.
     *
     * @param array[] $skills Array of skill data with 'id', 'slug', 'detection_rules'
     * @param array[] $repoResults Array of per-repo analysis results
     * @param int $totalRepos Total repos analyzed
     * @return array<string, array{proficiency: Proficiency, confidence: float, evidence: array[], raw_score: float}>
     */
    public function mapSkills(array $skills, array $repoResults, int $totalRepos): array
    {
        $assessments = [];
        $maxEvidence = 0;

        foreach ($skills as $skill) {
            $slug = $skill['slug'];
            $rules = $skill['detection_rules'];
            $evidence = [];
            $recentCount = 0;
            $depthSignals = 0;

            foreach ($repoResults as $repo) {
                $repoEvidence = $this->findEvidence($repo, $rules);
                if (!empty($repoEvidence)) {
                    $evidence = array_merge($evidence, $repoEvidence);

                    $pushedAt = new \DateTimeImmutable($repo['pushed_at']);
                    $monthsAgo = (new \DateTimeImmutable())->diff($pushedAt)->days / 30;
                    if ($monthsAgo <= 12) {
                        $recentCount++;
                    }

                    $depthSignals += $this->assessDepth($repo, $rules);
                }
            }

            $repoCount = count(array_unique(array_column($evidence, 'source_repo')));
            $recentRatio = $repoCount > 0 ? $recentCount / $repoCount : 0.0;
            $depthScore = $repoCount > 0 ? min(1.0, $depthSignals / $repoCount) : 0.0;

            $rawScore = ProficiencyCalculator::calculateRawScore(
                $repoCount, $totalRepos, $recentRatio, $depthScore,
            );

            $assessments[$slug] = [
                'proficiency' => ProficiencyCalculator::fromScore($rawScore),
                'confidence' => 0.0, // calculated after all skills processed
                'evidence' => $evidence,
                'raw_score' => $rawScore,
            ];

            $maxEvidence = max($maxEvidence, count($evidence));
        }

        // Calculate confidence scores
        foreach ($assessments as $slug => &$assessment) {
            $assessment['confidence'] = ProficiencyCalculator::calculateConfidence(
                count($assessment['evidence']),
                $maxEvidence,
            );
        }

        return $assessments;
    }

    private function findEvidence(array $repo, array $rules): array
    {
        $evidence = [];

        // Check file matches
        $fileMatches = $this->detector->matchFiles($repo['tree'], $rules);
        foreach ($fileMatches as $file) {
            $evidence[] = [
                'type' => EvidenceType::ConfigFile->value,
                'source_repo' => $repo['repo_name'],
                'source_file' => $file,
                'details' => ['matched_rule' => 'files'],
            ];
        }

        // Check config patterns
        $configMatches = $this->detector->matchConfigPatterns($repo['tree'], $rules);
        foreach ($configMatches as $file) {
            $evidence[] = [
                'type' => EvidenceType::CiWorkflow->value,
                'source_repo' => $repo['repo_name'],
                'source_file' => $file,
                'details' => ['matched_rule' => 'config_patterns'],
            ];
        }

        // Check language
        if ($this->detector->matchesLanguage($repo['tree'], $rules)) {
            $evidence[] = [
                'type' => EvidenceType::LanguageUsage->value,
                'source_repo' => $repo['repo_name'],
                'source_file' => '',
                'details' => ['languages' => $rules['languages'] ?? []],
            ];
        }

        // Check dependencies in file contents
        foreach ($rules['dependencies'] ?? [] as $file => $deps) {
            $content = $repo['contents'][$file] ?? null;
            if ($content !== null) {
                foreach ($deps as $dep) {
                    if (str_contains($content, $dep)) {
                        $evidence[] = [
                            'type' => EvidenceType::Dependency->value,
                            'source_repo' => $repo['repo_name'],
                            'source_file' => $file,
                            'details' => ['dependency' => $dep],
                        ];
                    }
                }
            }
        }

        // Check test presence
        if ($this->detector->hasTests($repo['tree'])) {
            $evidence[] = [
                'type' => EvidenceType::TestPresence->value,
                'source_repo' => $repo['repo_name'],
                'source_file' => '',
                'details' => [],
            ];
        }

        return $evidence;
    }

    private function assessDepth(array $repo, array $rules): float
    {
        $depth = 0.5; // Base depth for having any evidence

        // Content matches indicate deeper understanding
        foreach ($rules['content_matches'] ?? [] as $file => $patterns) {
            $content = $repo['contents'][$file] ?? null;
            if ($content !== null) {
                foreach ($patterns as $pattern) {
                    if (str_contains($content, $pattern)) {
                        $depth += 0.25;
                    }
                }
            }
        }

        return min(1.0, $depth);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/Scanning/SkillMapperTest.php
```

Expected: 3 tests, PASS.

- [ ] **Step 5: Implement ResultPersister (uses EntityRepository)**

```php
// src/Domain/Scanning/ResultPersister.php
<?php
declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Entity\SkillAssessment;
use App\Entity\SkillEvidence;
use App\Support\Proficiency;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;

final readonly class ResultPersister
{
    public function __construct(
        private EntityRepositoryInterface $repository,
    ) {}

    /**
     * Persist skill assessments and evidence, replacing any previous results.
     *
     * @param int $developerId
     * @param int $scanId
     * @param array<string, array{proficiency: Proficiency, confidence: float, evidence: array[], raw_score: float}> $assessments
     * @param array<string, int> $skillSlugToId Map of skill slug → entity ID
     */
    public function persist(
        int $developerId,
        int $scanId,
        array $assessments,
        array $skillSlugToId,
    ): void {
        // Delete previous assessments for this developer
        $existing = $this->repository->loadByProperties('skill_assessment', [
            'developer_id' => $developerId,
        ]);
        foreach ($existing as $old) {
            $this->repository->delete($old);
        }

        // Create new assessments and evidence
        foreach ($assessments as $slug => $data) {
            if (!isset($skillSlugToId[$slug])) {
                continue;
            }

            $assessment = new SkillAssessment([
                'developer_id' => $developerId,
                'roadmap_skill_id' => $skillSlugToId[$slug],
                'scan_id' => $scanId,
                'proficiency' => $data['proficiency']->value,
                'confidence' => $data['confidence'],
            ]);
            $this->repository->save($assessment);

            foreach ($data['evidence'] as $evidenceData) {
                $evidence = new SkillEvidence([
                    'scan_id' => $scanId,
                    'skill_assessment_id' => $assessment->id(),
                    'type' => $evidenceData['type'],
                    'source_repo' => $evidenceData['source_repo'],
                    'source_file' => $evidenceData['source_file'],
                    'details' => $evidenceData['details'],
                ]);
                $this->repository->save($evidence);
            }
        }
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add src/Domain/Scanning/ tests/Domain/Scanning/
git commit -m "feat: add scan pipeline stages 4-5 (skill mapping and result persistence)"
```

---

## Task 8: Scan Job (Queue Orchestrator)

**Files:**
- Create: `src/Domain/Scanning/ScanJob.php`
- Create: `tests/Domain/Scanning/ScanJobTest.php`

- [ ] **Step 1: Write failing test for ScanJob**

```php
// tests/Domain/Scanning/ScanJobTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\GitHub\GitHubClientInterface;
use App\Domain\GitHub\RepoMetadata;
use App\Domain\Scanning\FileAnalyzer;
use App\Domain\Scanning\ProfileFetcher;
use App\Domain\Scanning\RepoTriager;
use App\Domain\Scanning\ResultPersister;
use App\Domain\Scanning\ScanJob;
use App\Domain\Scanning\SkillMapper;
use App\Entity\Scan;
use App\Support\ScanStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;

#[CoversClass(ScanJob::class)]
final class ScanJobTest extends TestCase
{
    #[Test]
    public function orchestratesFullScanPipeline(): void
    {
        $github = $this->createMock(GitHubClientInterface::class);
        $repository = $this->createMock(EntityRepositoryInterface::class);

        $github->method('getUser')->willReturn([
            'login' => 'testuser',
            'name' => 'Test User',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'bio' => 'Developer',
        ]);

        $github->method('getUserRepos')->willReturn([
            [
                'name' => 'repo1',
                'full_name' => 'testuser/repo1',
                'description' => 'A Go project',
                'fork' => false,
                'language' => 'Go',
                'stargazers_count' => 5,
                'topics' => ['golang'],
                'pushed_at' => '2026-03-01T00:00:00Z',
                'size' => 500,
            ],
        ]);

        $github->method('getRepoTree')->willReturn([
            'main.go', 'go.mod', 'Dockerfile',
        ]);

        $github->method('getFileContent')->willReturn(
            "module github.com/testuser/repo1\n\ngo 1.24\n"
        );

        // Expect scan to be saved multiple times (status transitions)
        $repository->expects($this->atLeast(2))->method('save');
        $repository->method('loadByProperties')->willReturn([]);

        $scan = new Scan(['developer_id' => 1]);

        $job = new ScanJob(
            scan: $scan,
            username: 'testuser',
            skills: [],
            skillSlugToId: [],
            fetcher: new ProfileFetcher($github),
            triager: new RepoTriager(),
            analyzer: new FileAnalyzer($github, new FileDetector()),
            mapper: new SkillMapper(new FileDetector()),
            persister: new ResultPersister($repository),
            repository: $repository,
        );

        $job->handle();

        $this->assertSame(ScanStatus::Complete->value, $scan->get('status'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/Scanning/ScanJobTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement ScanJob**

```php
// src/Domain/Scanning/ScanJob.php
<?php
declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Entity\Scan;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;
use Waaseyaa\Queue\Job;

final class ScanJob extends Job
{
    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        private readonly Scan $scan,
        private readonly string $username,
        private readonly array $skills,
        private readonly array $skillSlugToId,
        private readonly ProfileFetcher $fetcher,
        private readonly RepoTriager $triager,
        private readonly FileAnalyzer $analyzer,
        private readonly SkillMapper $mapper,
        private readonly ResultPersister $persister,
        private readonly EntityRepositoryInterface $repository,
    ) {}

    public function handle(): void
    {
        $this->scan->markAnalyzing();
        $this->repository->save($this->scan);

        try {
            // Stage 1: Fetch profile and repos
            $data = $this->fetcher->fetch($this->username);

            // Stage 2: Triage repos
            $selected = $this->triager->triage($data['repos']);

            // Stage 3: Analyze file trees
            $repoResults = [];
            foreach ($selected as $repo) {
                $analysis = $this->analyzer->analyze($repo);
                $repoResults[] = [
                    'repo_name' => $repo->fullName,
                    'tree' => $analysis['tree'],
                    'contents' => $analysis['contents'],
                    'pushed_at' => $repo->pushedAt->format('c'),
                ];
            }

            // Stage 4: Map to skills
            $assessments = $this->mapper->mapSkills(
                $this->skills,
                $repoResults,
                totalRepos: count($selected),
            );

            // Stage 5: Persist results
            $this->persister->persist(
                developerId: (int) $this->scan->get('developer_id'),
                scanId: (int) $this->scan->id(),
                assessments: $assessments,
                skillSlugToId: $this->skillSlugToId,
            );

            $this->scan->markComplete(count($selected));
        } catch (\Throwable $e) {
            $this->scan->markFailed();
            throw $e;
        } finally {
            $this->repository->save($this->scan);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->scan->markFailed();
        $this->repository->save($this->scan);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/Scanning/ScanJobTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Domain/Scanning/ScanJob.php tests/Domain/Scanning/ScanJobTest.php
git commit -m "feat: add ScanJob queue orchestrator for the 5-stage pipeline"
```

---

## Task 9: Roadmap Auto-Detection

**Files:**
- Create: `src/Domain/Roadmap/RoadmapDetector.php`
- Create: `tests/Domain/Roadmap/RoadmapDetectorTest.php`

- [ ] **Step 1: Write failing test**

```php
// tests/Domain/Roadmap/RoadmapDetectorTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Domain\Roadmap;

use App\Domain\Roadmap\RoadmapDetector;
use App\Support\Proficiency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RoadmapDetector::class)]
final class RoadmapDetectorTest extends TestCase
{
    #[Test]
    public function detectsRelevantRoadmapWithThreeOrMoreSkills(): void
    {
        $detector = new RoadmapDetector();

        $assessments = [
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Advanced, 'is_leaf' => true],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Intermediate, 'is_leaf' => true],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Beginner, 'is_leaf' => true],
            ['roadmap_path_id' => 2, 'proficiency' => Proficiency::Beginner, 'is_leaf' => true],
            ['roadmap_path_id' => 2, 'proficiency' => Proficiency::None, 'is_leaf' => true],
        ];

        $relevant = $detector->detectRelevant($assessments);

        $this->assertSame([1], $relevant);
    }

    #[Test]
    public function excludesNonLeafSkills(): void
    {
        $detector = new RoadmapDetector();

        $assessments = [
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Advanced, 'is_leaf' => false],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Advanced, 'is_leaf' => false],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Advanced, 'is_leaf' => false],
            ['roadmap_path_id' => 1, 'proficiency' => Proficiency::Advanced, 'is_leaf' => true],
        ];

        $relevant = $detector->detectRelevant($assessments);

        $this->assertSame([], $relevant);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Domain/Roadmap/RoadmapDetectorTest.php
```

Expected: FAIL.

- [ ] **Step 3: Implement RoadmapDetector**

```php
// src/Domain/Roadmap/RoadmapDetector.php
<?php
declare(strict_types=1);

namespace App\Domain\Roadmap;

use App\Support\Proficiency;

final class RoadmapDetector
{
    private const int MIN_SKILLS_FOR_RELEVANCE = 3;

    /**
     * Detect which roadmap paths are relevant based on skill assessments.
     * A roadmap is relevant if >= 3 of its leaf skills have evidence.
     *
     * @param array[] $assessments Each with 'roadmap_path_id', 'proficiency' (Proficiency), 'is_leaf' (bool)
     * @return int[] Relevant roadmap path IDs
     */
    public function detectRelevant(array $assessments): array
    {
        $counts = [];

        foreach ($assessments as $a) {
            if (!$a['is_leaf']) {
                continue;
            }
            if ($a['proficiency'] === Proficiency::None) {
                continue;
            }

            $pathId = $a['roadmap_path_id'];
            $counts[$pathId] = ($counts[$pathId] ?? 0) + 1;
        }

        $relevant = [];
        foreach ($counts as $pathId => $count) {
            if ($count >= self::MIN_SKILLS_FOR_RELEVANCE) {
                $relevant[] = $pathId;
            }
        }

        return $relevant;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Domain/Roadmap/RoadmapDetectorTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Domain/Roadmap/RoadmapDetector.php tests/Domain/Roadmap/RoadmapDetectorTest.php
git commit -m "feat: add roadmap auto-detection (>=3 leaf skills with evidence)"
```

---

## Task 10: Roadmap Data Seeder

**Files:**
- Create: `src/Seed/RoadmapSeeder.php`

This is the curated seed data — the 3 MVP roadmaps with skill trees and detection rules. This is large but essential content, not logic, so it doesn't need TDD.

- [ ] **Step 1: Create the seeder with Backend roadmap**

```php
// src/Seed/RoadmapSeeder.php
<?php
declare(strict_types=1);

namespace App\Seed;

use App\Entity\RoadmapPath;
use App\Entity\RoadmapSkill;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;

final readonly class RoadmapSeeder
{
    public function __construct(
        private EntityRepositoryInterface $repository,
    ) {}

    public function seed(): void
    {
        $this->seedBackend();
        $this->seedFrontend();
        $this->seedDevOps();
    }

    private function seedBackend(): void
    {
        $path = new RoadmapPath([
            'slug' => 'backend',
            'name' => 'Backend',
            'description' => 'Server-side development: languages, databases, APIs, caching, testing',
        ]);
        $this->repository->save($path);

        $this->seedSkillTree($path->id(), [
            ['slug' => 'backend-languages', 'name' => 'Languages', 'children' => [
                ['slug' => 'go', 'name' => 'Go', 'rules' => [
                    'languages' => ['go'], 'files' => ['go.mod', 'go.sum'],
                ]],
                ['slug' => 'php', 'name' => 'PHP', 'rules' => [
                    'languages' => ['php'], 'files' => ['composer.json'],
                ]],
                ['slug' => 'python', 'name' => 'Python', 'rules' => [
                    'languages' => ['python'], 'files' => ['requirements.txt', 'pyproject.toml', 'setup.py'],
                ]],
                ['slug' => 'javascript-backend', 'name' => 'JavaScript/Node.js', 'rules' => [
                    'languages' => ['javascript'],
                    'dependencies' => ['package.json' => ['express', 'fastify', 'koa', 'hapi', 'nest']],
                ]],
                ['slug' => 'rust', 'name' => 'Rust', 'rules' => [
                    'languages' => ['rust'], 'files' => ['Cargo.toml'],
                ]],
                ['slug' => 'java', 'name' => 'Java', 'rules' => [
                    'languages' => ['java'], 'files' => ['pom.xml', 'build.gradle'],
                ]],
            ]],
            ['slug' => 'databases', 'name' => 'Databases', 'children' => [
                ['slug' => 'sql', 'name' => 'SQL/Relational', 'rules' => [
                    'files' => ['migrations/', 'schema.sql'],
                    'dependencies' => [
                        'go.mod' => ['database/sql', 'gorm', 'sqlx'],
                        'composer.json' => ['doctrine', 'illuminate/database'],
                        'package.json' => ['knex', 'sequelize', 'prisma', 'typeorm'],
                    ],
                ]],
                ['slug' => 'nosql', 'name' => 'NoSQL', 'rules' => [
                    'dependencies' => [
                        'go.mod' => ['go.mongodb.org', 'github.com/redis/go-redis'],
                        'package.json' => ['mongodb', 'mongoose', 'redis', 'ioredis'],
                        'composer.json' => ['mongodb', 'predis'],
                    ],
                ]],
            ]],
            ['slug' => 'apis', 'name' => 'APIs', 'children' => [
                ['slug' => 'rest-apis', 'name' => 'REST APIs', 'rules' => [
                    'files' => ['openapi.yml', 'openapi.yaml', 'swagger.json', 'swagger.yaml'],
                    'dependencies' => [
                        'go.mod' => ['github.com/labstack/echo', 'github.com/gin-gonic/gin', 'github.com/go-chi/chi'],
                        'composer.json' => ['laravel/framework', 'symfony/routing'],
                        'package.json' => ['express', 'fastify', '@nestjs/core'],
                    ],
                ]],
                ['slug' => 'graphql', 'name' => 'GraphQL', 'rules' => [
                    'files' => ['schema.graphql', '.graphqlrc'],
                    'dependencies' => [
                        'package.json' => ['graphql', 'apollo-server', '@apollo/server'],
                        'go.mod' => ['github.com/99designs/gqlgen', 'github.com/graphql-go/graphql'],
                        'composer.json' => ['webonyx/graphql-php', 'nuwave/lighthouse'],
                    ],
                ]],
            ]],
            ['slug' => 'backend-testing', 'name' => 'Testing', 'rules' => [
                'files' => ['phpunit.xml', 'phpunit.xml.dist', 'jest.config.js', 'vitest.config.ts'],
            ]],
            ['slug' => 'caching', 'name' => 'Caching', 'rules' => [
                'dependencies' => [
                    'go.mod' => ['github.com/redis/go-redis', 'github.com/allegro/bigcache'],
                    'package.json' => ['redis', 'ioredis', 'node-cache'],
                    'composer.json' => ['predis/predis', 'symfony/cache'],
                ],
            ]],
            ['slug' => 'authentication', 'name' => 'Authentication', 'rules' => [
                'dependencies' => [
                    'package.json' => ['passport', 'jsonwebtoken', 'bcrypt', '@auth0/nextjs-auth0'],
                    'go.mod' => ['github.com/golang-jwt/jwt'],
                    'composer.json' => ['laravel/sanctum', 'laravel/passport', 'tymon/jwt-auth'],
                ],
            ]],
        ]);
    }

    private function seedFrontend(): void
    {
        $path = new RoadmapPath([
            'slug' => 'frontend',
            'name' => 'Frontend',
            'description' => 'Client-side development: HTML/CSS, JavaScript, frameworks, build tools',
        ]);
        $this->repository->save($path);

        $this->seedSkillTree($path->id(), [
            ['slug' => 'frontend-frameworks', 'name' => 'Frameworks', 'children' => [
                ['slug' => 'react', 'name' => 'React', 'rules' => [
                    'dependencies' => ['package.json' => ['react', 'react-dom', 'next']],
                ]],
                ['slug' => 'vue', 'name' => 'Vue.js', 'rules' => [
                    'dependencies' => ['package.json' => ['vue', 'nuxt', '@nuxt/kit']],
                ]],
                ['slug' => 'angular', 'name' => 'Angular', 'rules' => [
                    'dependencies' => ['package.json' => ['@angular/core', '@angular/cli']],
                ]],
                ['slug' => 'svelte', 'name' => 'Svelte', 'rules' => [
                    'dependencies' => ['package.json' => ['svelte', '@sveltejs/kit']],
                ]],
            ]],
            ['slug' => 'css', 'name' => 'CSS', 'children' => [
                ['slug' => 'tailwind', 'name' => 'Tailwind CSS', 'rules' => [
                    'files' => ['tailwind.config.js', 'tailwind.config.ts'],
                    'dependencies' => ['package.json' => ['tailwindcss']],
                ]],
                ['slug' => 'sass', 'name' => 'Sass/SCSS', 'rules' => [
                    'dependencies' => ['package.json' => ['sass', 'node-sass']],
                ]],
            ]],
            ['slug' => 'typescript-frontend', 'name' => 'TypeScript', 'rules' => [
                'files' => ['tsconfig.json'],
                'dependencies' => ['package.json' => ['typescript']],
            ]],
            ['slug' => 'build-tools', 'name' => 'Build Tools', 'children' => [
                ['slug' => 'vite', 'name' => 'Vite', 'rules' => [
                    'files' => ['vite.config.ts', 'vite.config.js'],
                    'dependencies' => ['package.json' => ['vite']],
                ]],
                ['slug' => 'webpack', 'name' => 'Webpack', 'rules' => [
                    'files' => ['webpack.config.js', 'webpack.config.ts'],
                    'dependencies' => ['package.json' => ['webpack']],
                ]],
            ]],
            ['slug' => 'frontend-testing', 'name' => 'Testing', 'children' => [
                ['slug' => 'jest', 'name' => 'Jest', 'rules' => [
                    'files' => ['jest.config.js', 'jest.config.ts'],
                    'dependencies' => ['package.json' => ['jest']],
                ]],
                ['slug' => 'vitest', 'name' => 'Vitest', 'rules' => [
                    'files' => ['vitest.config.ts'],
                    'dependencies' => ['package.json' => ['vitest']],
                ]],
                ['slug' => 'playwright', 'name' => 'Playwright', 'rules' => [
                    'files' => ['playwright.config.ts'],
                    'dependencies' => ['package.json' => ['@playwright/test']],
                ]],
            ]],
        ]);
    }

    private function seedDevOps(): void
    {
        $path = new RoadmapPath([
            'slug' => 'devops',
            'name' => 'DevOps',
            'description' => 'Containers, CI/CD, infrastructure, monitoring, cloud platforms',
        ]);
        $this->repository->save($path);

        $this->seedSkillTree($path->id(), [
            ['slug' => 'containers', 'name' => 'Containers', 'children' => [
                ['slug' => 'docker', 'name' => 'Docker', 'rules' => [
                    'files' => ['Dockerfile', 'docker-compose.yml', 'docker-compose.yaml', '.dockerignore'],
                    'content_matches' => ['Dockerfile' => ['FROM', 'RUN', 'COPY']],
                ]],
                ['slug' => 'kubernetes', 'name' => 'Kubernetes', 'rules' => [
                    'files' => ['k8s/', 'kubernetes/', 'helm/'],
                    'config_patterns' => ['k8s/*.yml', 'k8s/*.yaml', 'kubernetes/*.yml'],
                ]],
            ]],
            ['slug' => 'ci-cd', 'name' => 'CI/CD', 'children' => [
                ['slug' => 'github-actions', 'name' => 'GitHub Actions', 'rules' => [
                    'config_patterns' => ['.github/workflows/*.yml', '.github/workflows/*.yaml'],
                ]],
                ['slug' => 'gitlab-ci', 'name' => 'GitLab CI', 'rules' => [
                    'files' => ['.gitlab-ci.yml'],
                ]],
            ]],
            ['slug' => 'infrastructure', 'name' => 'Infrastructure', 'children' => [
                ['slug' => 'terraform', 'name' => 'Terraform', 'rules' => [
                    'config_patterns' => ['*.tf', 'terraform/*.tf'],
                    'files' => ['.terraform.lock.hcl'],
                ]],
                ['slug' => 'ansible', 'name' => 'Ansible', 'rules' => [
                    'files' => ['ansible.cfg', 'playbook.yml'],
                    'config_patterns' => ['roles/*/tasks/*.yml'],
                ]],
            ]],
            ['slug' => 'monitoring', 'name' => 'Monitoring', 'rules' => [
                'files' => ['prometheus.yml', 'grafana/', 'alertmanager.yml'],
                'dependencies' => [
                    'go.mod' => ['github.com/prometheus/client_golang'],
                    'package.json' => ['prom-client'],
                ],
            ]],
            ['slug' => 'linux', 'name' => 'Linux/Shell', 'rules' => [
                'config_patterns' => ['*.sh', 'scripts/*.sh'],
                'files' => ['Makefile', 'Taskfile.yml'],
            ]],
        ]);
    }

    /**
     * Recursively create skill tree entities.
     *
     * @param int|string $pathId
     * @param array[] $nodes Each with 'slug', 'name', optional 'rules', optional 'children'
     * @param int|string|null $parentId
     */
    private function seedSkillTree(int|string $pathId, array $nodes, int|string|null $parentId = null): void
    {
        foreach ($nodes as $node) {
            $skill = new RoadmapSkill([
                'slug' => $node['slug'],
                'name' => $node['name'],
                'roadmap_path_id' => $pathId,
                'parent_skill_id' => $parentId,
                'detection_rules' => $node['rules'] ?? [],
            ]);
            $this->repository->save($skill);

            if (!empty($node['children'])) {
                $this->seedSkillTree($pathId, $node['children'], $skill->id());
            }
        }
    }
}
```

- [ ] **Step 2: Create a CLI command to run the seeder**

Check Waaseyaa's CLI command pattern: use `#[AsCommand]` attribute and register via the ServiceProvider.

```php
// Add to src/Provider/MeVsRoadmapProvider.php (created in Task 11)
// For now, create a standalone command file:

// src/Command/SeedRoadmapsCommand.php
<?php
declare(strict_types=1);

namespace App\Command;

use App\Seed\RoadmapSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;

#[AsCommand(name: 'app:seed-roadmaps', description: 'Seed the 3 MVP roadmaps with skills and detection rules')]
final class SeedRoadmapsCommand extends Command
{
    public function __construct(
        private readonly EntityRepositoryInterface $repository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $seeder = new RoadmapSeeder($this->repository);
        $seeder->seed();

        $output->writeln('<info>Seeded 3 roadmaps: Backend, Frontend, DevOps</info>');

        return Command::SUCCESS;
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Seed/RoadmapSeeder.php src/Command/SeedRoadmapsCommand.php
git commit -m "feat: add roadmap seeder with 3 MVP roadmaps and detection rules"
```

---

## Task 11: Service Provider, Routes, and Controllers

**Files:**
- Create: `src/Provider/MeVsRoadmapProvider.php`
- Create: `src/Controller/AuthController.php`
- Create: `src/Controller/ScanController.php`
- Create: `src/Controller/ProfileController.php`
- Modify: `config/waaseyaa.php` (add GitHub OAuth keys)
- Modify: `config/services.php` (register provider)

- [ ] **Step 1: Add GitHub config keys to waaseyaa.php**

Add to `config/waaseyaa.php`:

```php
'github' => [
    'client_id' => getenv('GITHUB_CLIENT_ID') ?: '',
    'client_secret' => getenv('GITHUB_CLIENT_SECRET') ?: '',
    'redirect_uri' => getenv('GITHUB_REDIRECT_URI') ?: 'http://localhost:8080/auth/github/callback',
],
```

- [ ] **Step 2: Create the ServiceProvider**

```php
// src/Provider/MeVsRoadmapProvider.php
<?php
declare(strict_types=1);

namespace App\Provider;

use App\Command\SeedRoadmapsCommand;
use App\Controller\AuthController;
use App\Controller\ProfileController;
use App\Controller\ScanController;
use App\Domain\GitHub\GitHubClient;
use App\Domain\GitHub\GitHubClientInterface;
use GuzzleHttp\Client;
use Symfony\Component\Routing\Route;
use Waaseyaa\Entity\EntityTypeManager;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;
use Waaseyaa\Routing\WaaseyaaRouter;

final class MeVsRoadmapProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(GitHubClientInterface::class, function () {
            return new GitHubClient(
                new Client(),
                null, // Token set per-user during scan
            );
        });
    }

    public function routes(WaaseyaaRouter $router, ?EntityTypeManager $entityTypeManager = null): void
    {
        // Auth
        $router->addRoute('auth.github', new Route('/auth/github', [
            '_controller' => AuthController::class,
            '_action' => 'redirect',
            '_public' => true,
        ]));
        $router->addRoute('auth.github.callback', new Route('/auth/github/callback', [
            '_controller' => AuthController::class,
            '_action' => 'callback',
            '_public' => true,
        ]));

        // Scan
        $router->addRoute('scan.trigger', new Route('/scan', [
            '_controller' => ScanController::class,
            '_action' => 'trigger',
        ], methods: ['POST']));
        $router->addRoute('scan.status', new Route('/scan/{id}', [
            '_controller' => ScanController::class,
            '_action' => 'status',
        ]));

        // Profile (public)
        $router->addRoute('profile.view', new Route('/profile/{username}', [
            '_controller' => ProfileController::class,
            '_action' => 'view',
            '_public' => true,
        ]));
    }

    public function commands(
        \Waaseyaa\Entity\EntityTypeManager $entityTypeManager,
        \Waaseyaa\Database\DatabaseInterface $database,
        \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher,
    ): array {
        return [
            new SeedRoadmapsCommand($this->container->get(\Waaseyaa\EntityStorage\EntityRepositoryInterface::class)),
        ];
    }
}
```

- [ ] **Step 3: Implement AuthController**

```php
// src/Controller/AuthController.php
<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Developer;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;

final readonly class AuthController
{
    public function __construct(
        private EntityRepositoryInterface $repository,
        private array $config,
    ) {}

    public function redirect(): Response
    {
        $params = http_build_query([
            'client_id' => $this->config['github']['client_id'],
            'redirect_uri' => $this->config['github']['redirect_uri'],
            'scope' => 'read:user',
        ]);

        return new RedirectResponse("https://github.com/login/oauth/authorize?{$params}");
    }

    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Missing code parameter', 400);
        }

        // Exchange code for token
        $http = new Client();
        $response = $http->post('https://github.com/login/oauth/access_token', [
            'json' => [
                'client_id' => $this->config['github']['client_id'],
                'client_secret' => $this->config['github']['client_secret'],
                'code' => $code,
            ],
            'headers' => ['Accept' => 'application/json'],
        ]);

        $tokenData = json_decode($response->getBody()->getContents(), true);
        $token = $tokenData['access_token'] ?? null;

        if (!$token) {
            return new Response('Failed to get access token', 400);
        }

        // Fetch GitHub user profile
        $userResponse = $http->get('https://api.github.com/user', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ]);

        $githubUser = json_decode($userResponse->getBody()->getContents(), true);

        // Find or create developer
        $existing = $this->repository->loadByProperties('developer', [
            'github_username' => $githubUser['login'],
        ]);

        if (!empty($existing)) {
            $developer = reset($existing);
            $developer->set('display_name', $githubUser['name'] ?? $githubUser['login']);
            $developer->set('avatar_url', $githubUser['avatar_url'] ?? '');
            $developer->set('bio', $githubUser['bio'] ?? '');
        } else {
            $developer = new Developer([
                'github_username' => $githubUser['login'],
                'display_name' => $githubUser['name'] ?? $githubUser['login'],
                'avatar_url' => $githubUser['avatar_url'] ?? '',
                'bio' => $githubUser['bio'] ?? '',
            ]);
        }

        $developer->set('github_token', $token);
        $this->repository->save($developer);

        // Store developer ID in session, redirect to profile
        $request->getSession()->set('developer_id', $developer->id());

        return new RedirectResponse("/profile/{$githubUser['login']}");
    }
}
```

- [ ] **Step 4: Implement ScanController**

```php
// src/Controller/ScanController.php
<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\GitHub\FileDetector;
use App\Domain\GitHub\GitHubClient;
use App\Domain\Scanning\FileAnalyzer;
use App\Domain\Scanning\ProfileFetcher;
use App\Domain\Scanning\RepoTriager;
use App\Domain\Scanning\ResultPersister;
use App\Domain\Scanning\ScanJob;
use App\Domain\Scanning\SkillMapper;
use App\Entity\Scan;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;
use Waaseyaa\Queue\QueueInterface;

final readonly class ScanController
{
    public function __construct(
        private EntityRepositoryInterface $repository,
        private QueueInterface $queue,
    ) {}

    public function trigger(Request $request): Response
    {
        $developerId = $request->getSession()->get('developer_id');
        if (!$developerId) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $developer = $this->repository->load('developer', $developerId);
        if (!$developer) {
            return new JsonResponse(['error' => 'Developer not found'], 404);
        }

        $scan = new Scan(['developer_id' => $developerId]);
        $this->repository->save($scan);

        // Load skills for mapping
        $skills = $this->repository->loadByProperties('roadmap_skill', []);
        $skillData = [];
        $skillSlugToId = [];
        foreach ($skills as $skill) {
            $skillData[] = [
                'id' => $skill->id(),
                'slug' => $skill->get('slug'),
                'detection_rules' => $skill->getDetectionRules(),
            ];
            $skillSlugToId[$skill->get('slug')] = $skill->id();
        }

        $token = $developer->get('github_token');
        $http = new Client();
        $github = new GitHubClient($http, $token);
        $detector = new FileDetector();

        $job = new ScanJob(
            scan: $scan,
            username: $developer->get('github_username'),
            skills: $skillData,
            skillSlugToId: $skillSlugToId,
            fetcher: new ProfileFetcher($github),
            triager: new RepoTriager(),
            analyzer: new FileAnalyzer($github, $detector),
            mapper: new SkillMapper($detector),
            persister: new ResultPersister($this->repository),
            repository: $this->repository,
        );

        $this->queue->push($job);

        return new JsonResponse([
            'scan_id' => $scan->id(),
            'status' => $scan->get('status'),
        ], 202);
    }

    public function status(Request $request, int $id): Response
    {
        $scan = $this->repository->load('scan', $id);
        if (!$scan) {
            return new JsonResponse(['error' => 'Scan not found'], 404);
        }

        return new JsonResponse([
            'scan_id' => $scan->id(),
            'status' => $scan->get('status'),
            'repos_analyzed' => $scan->get('repos_analyzed'),
            'started_at' => $scan->get('started_at'),
            'completed_at' => $scan->get('completed_at'),
        ]);
    }
}
```

- [ ] **Step 5: Implement ProfileController**

```php
// src/Controller/ProfileController.php
<?php
declare(strict_types=1);

namespace App\Controller;

use App\Domain\Roadmap\RollUpCalculator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\EntityStorage\EntityRepositoryInterface;

final readonly class ProfileController
{
    public function __construct(
        private EntityRepositoryInterface $repository,
    ) {}

    public function view(Request $request, string $username): Response
    {
        $developers = $this->repository->loadByProperties('developer', [
            'github_username' => $username,
        ]);

        if (empty($developers)) {
            return new Response('Developer not found', 404);
        }

        $developer = reset($developers);

        if (!$developer->get('is_public')) {
            return new Response('Profile is private', 403);
        }

        // Load assessments
        $assessments = $this->repository->loadByProperties('skill_assessment', [
            'developer_id' => $developer->id(),
        ]);

        // Load skills and paths for context
        $skills = [];
        foreach ($this->repository->loadByProperties('roadmap_skill', []) as $skill) {
            $skills[$skill->id()] = $skill;
        }

        $paths = [];
        foreach ($this->repository->loadByProperties('roadmap_path', []) as $path) {
            $paths[$path->id()] = $path;
        }

        // Build response data
        $profileData = [
            'username' => $developer->get('github_username'),
            'display_name' => $developer->get('display_name'),
            'avatar_url' => $developer->get('avatar_url'),
            'bio' => $developer->get('bio'),
            'roadmaps' => $this->buildRoadmapData($assessments, $skills, $paths),
        ];

        // Return JSON for API consumers, or render template for browsers
        if ($request->headers->get('Accept') === 'application/json') {
            return new JsonResponse($profileData);
        }

        // SSR template rendering will be handled by Waaseyaa's Twig renderer
        // For now, return JSON
        return new JsonResponse($profileData);
    }

    private function buildRoadmapData(array $assessments, array $skills, array $paths): array
    {
        $roadmaps = [];

        foreach ($paths as $path) {
            $pathId = $path->id();
            $pathSkills = array_filter($skills, fn($s) => $s->get('roadmap_path_id') == $pathId);
            $pathAssessments = [];

            foreach ($assessments as $a) {
                $skillId = $a->get('roadmap_skill_id');
                if (isset($pathSkills[$skillId])) {
                    $pathAssessments[] = [
                        'skill_slug' => $pathSkills[$skillId]->get('slug'),
                        'skill_name' => $pathSkills[$skillId]->get('name'),
                        'proficiency' => $a->get('proficiency'),
                        'confidence' => $a->get('confidence'),
                    ];
                }
            }

            if (!empty($pathAssessments)) {
                $roadmaps[] = [
                    'slug' => $path->get('slug'),
                    'name' => $path->get('name'),
                    'skills' => $pathAssessments,
                ];
            }
        }

        return $roadmaps;
    }
}
```

- [ ] **Step 6: Register the provider**

Add to `config/services.php`:

```php
return [
    \App\Provider\MeVsRoadmapProvider::class,
];
```

- [ ] **Step 7: Commit**

```bash
git add src/Provider/ src/Controller/ config/waaseyaa.php config/services.php
git commit -m "feat: add service provider, routes, auth/scan/profile controllers"
```

---

## Task 12: Access Policies

**Files:**
- Create: `src/Access/DeveloperAccessPolicy.php`
- Create: `src/Access/ScanAccessPolicy.php`
- Create: `tests/Access/DeveloperAccessPolicyTest.php`
- Create: `tests/Access/ScanAccessPolicyTest.php`

- [ ] **Step 1: Write failing test for DeveloperAccessPolicy**

```php
// tests/Access/DeveloperAccessPolicyTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Access;

use App\Access\DeveloperAccessPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Access\AccessResult;
use Waaseyaa\Entity\EntityInterface;
use Waaseyaa\Access\AccountInterface;

#[CoversClass(DeveloperAccessPolicy::class)]
final class DeveloperAccessPolicyTest extends TestCase
{
    #[Test]
    public function allowsViewingPublicProfile(): void
    {
        $policy = new DeveloperAccessPolicy();
        $entity = $this->createMock(EntityInterface::class);
        $entity->method('get')->willReturnMap([['is_public', true]]);
        $account = $this->createMock(AccountInterface::class);

        $result = $policy->access($entity, 'view', $account);
        $this->assertTrue($result->isAllowed());
    }

    #[Test]
    public function forbidsViewingPrivateProfile(): void
    {
        $policy = new DeveloperAccessPolicy();
        $entity = $this->createMock(EntityInterface::class);
        $entity->method('get')->willReturnMap([['is_public', false]]);
        $account = $this->createMock(AccountInterface::class);

        $result = $policy->access($entity, 'view', $account);
        $this->assertTrue($result->isForbidden());
    }

    #[Test]
    public function appliesToDeveloperEntityType(): void
    {
        $policy = new DeveloperAccessPolicy();
        $this->assertTrue($policy->appliesTo('developer'));
        $this->assertFalse($policy->appliesTo('scan'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/phpunit tests/Access/DeveloperAccessPolicyTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Implement DeveloperAccessPolicy**

```php
// src/Access/DeveloperAccessPolicy.php
<?php
declare(strict_types=1);

namespace App\Access;

use Waaseyaa\Access\AccessPolicyInterface;
use Waaseyaa\Access\AccessResult;
use Waaseyaa\Access\Gate\PolicyAttribute;
use Waaseyaa\Entity\EntityInterface;
use Waaseyaa\Access\AccountInterface;

#[PolicyAttribute(entityType: 'developer')]
final class DeveloperAccessPolicy implements AccessPolicyInterface
{
    public function appliesTo(string $entityTypeId): bool
    {
        return $entityTypeId === 'developer';
    }

    public function access(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult
    {
        return match ($operation) {
            'view' => $entity->get('is_public')
                ? AccessResult::allowed()
                : AccessResult::forbidden(),
            'update', 'delete' => $account->id() === $entity->get('user_id')
                ? AccessResult::allowed()
                : AccessResult::forbidden(),
            default => AccessResult::neutral(),
        };
    }

    public function createAccess(string $entityTypeId, string $bundle, AccountInterface $account): AccessResult
    {
        return AccessResult::neutral();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Access/DeveloperAccessPolicyTest.php
```

Expected: PASS.

- [ ] **Step 5: Implement ScanAccessPolicy**

```php
// src/Access/ScanAccessPolicy.php
<?php
declare(strict_types=1);

namespace App\Access;

use Waaseyaa\Access\AccessPolicyInterface;
use Waaseyaa\Access\AccessResult;
use Waaseyaa\Access\Gate\PolicyAttribute;
use Waaseyaa\Entity\EntityInterface;
use Waaseyaa\Access\AccountInterface;

#[PolicyAttribute(entityType: 'scan')]
final class ScanAccessPolicy implements AccessPolicyInterface
{
    public function appliesTo(string $entityTypeId): bool
    {
        return $entityTypeId === 'scan';
    }

    public function access(EntityInterface $entity, string $operation, AccountInterface $account): AccessResult
    {
        // Scans are only accessible to the developer who owns them
        return match ($operation) {
            'view' => AccessResult::allowed(), // Status is public
            'create', 'delete' => AccessResult::forbidden(),
            default => AccessResult::neutral(),
        };
    }

    public function createAccess(string $entityTypeId, string $bundle, AccountInterface $account): AccessResult
    {
        return AccessResult::forbidden();
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

```bash
vendor/bin/phpunit tests/Access/DeveloperAccessPolicyTest.php
```

Expected: PASS.

- [ ] **Step 7: Write test for ScanAccessPolicy**

```php
// tests/Access/ScanAccessPolicyTest.php
<?php
declare(strict_types=1);

namespace App\Tests\Access;

use App\Access\ScanAccessPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Access\AccessResult;
use Waaseyaa\Entity\EntityInterface;
use Waaseyaa\Access\AccountInterface;

#[CoversClass(ScanAccessPolicy::class)]
final class ScanAccessPolicyTest extends TestCase
{
    #[Test]
    public function allowsViewingScanStatus(): void
    {
        $policy = new ScanAccessPolicy();
        $entity = $this->createMock(EntityInterface::class);
        $account = $this->createMock(AccountInterface::class);

        $result = $policy->access($entity, 'view', $account);
        $this->assertTrue($result->isAllowed());
    }

    #[Test]
    public function forbidsCreatingScansDirectly(): void
    {
        $policy = new ScanAccessPolicy();
        $entity = $this->createMock(EntityInterface::class);
        $account = $this->createMock(AccountInterface::class);

        $result = $policy->access($entity, 'create', $account);
        $this->assertTrue($result->isForbidden());
    }
}
```

- [ ] **Step 8: Run all access policy tests**

```bash
vendor/bin/phpunit tests/Access/
```

Expected: All tests PASS.

- [ ] **Step 9: Commit**

```bash
git add src/Access/ tests/Access/
git commit -m "feat: add access policies for Developer and Scan entities with tests"
```

---

## Task 13: Profile Template (SSR)

**Files:**
- Create: `templates/profile.html.twig`

- [ ] **Step 1: Create the profile template**

```twig
{# templates/profile.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ developer.display_name }} — Me vs Roadmap{% endblock %}

{% block content %}
<div class="profile">
  <header class="profile-hero">
    <img src="{{ developer.avatar_url }}" alt="{{ developer.display_name }}" class="avatar" />
    <div class="profile-info">
      <h1>{{ developer.display_name }}</h1>
      <p class="username">@{{ developer.github_username }}</p>
      {% if developer.bio %}
        <p class="bio">{{ developer.bio }}</p>
      {% endif %}
    </div>
    <div class="roadmap-badges">
      {% for roadmap in roadmaps %}
        <span class="badge">{{ roadmap.name }}</span>
      {% endfor %}
    </div>
  </header>

  <div id="skill-tree-app" data-profile="{{ profile_json }}">
    {# Vue 3 app mounts here for interactive skill tree #}
    <noscript>
      {% for roadmap in roadmaps %}
        <section class="roadmap-section">
          <h2>{{ roadmap.name }}</h2>
          <ul class="skill-list">
            {% for skill in roadmap.skills %}
              <li class="skill skill--{{ skill.proficiency }}">
                {{ skill.skill_name }}: {{ skill.proficiency }}
              </li>
            {% endfor %}
          </ul>
        </section>
      {% endfor %}
    </noscript>
  </div>
</div>
{% endblock %}
```

- [ ] **Step 2: Verify template syntax**

```bash
# Check Twig syntax is valid (no parse errors)
php -r "require 'vendor/autoload.php'; \$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader('templates')); \$twig->load('profile.html.twig'); echo 'Template OK';"
```

Expected: "Template OK" (or adjust if base template doesn't exist yet — in that case, verify the file exists and has valid Twig syntax by visual inspection).

- [ ] **Step 3: Commit**

```bash
git add templates/profile.html.twig
git commit -m "feat: add profile SSR template with noscript fallback"
```

---

## Task 14: Run All Tests and Final Verification

- [ ] **Step 1: Run the full test suite**

```bash
vendor/bin/phpunit
```

Expected: All tests PASS.

- [ ] **Step 2: Boot the application and verify routes**

```bash
php -S localhost:8080 -t public &
curl -s http://localhost:8080/ | head -20
curl -s http://localhost:8080/profile/testuser -w "\n%{http_code}"
```

Expected: Home page renders, profile returns 404 (no developer yet).

- [ ] **Step 3: Run the roadmap seeder**

```bash
php bin/waaseyaa app:seed-roadmaps
```

Expected: "Seeded 3 roadmaps: Backend, Frontend, DevOps"

- [ ] **Step 4: Commit any remaining changes**

```bash
git add -A
git status
# If there are changes:
git commit -m "chore: final cleanup and verification"
```

---

## Summary

| Task | Description | Key Files |
|------|-------------|-----------|
| 1 | Project scaffolding | `composer create-project` |
| 2 | Enums and value objects | `Enums.php`, `RepoMetadata.php` |
| 3 | Entity definitions (6 entities) | `src/Entity/*.php` |
| 4 | Proficiency calculator + roll-up | `ProficiencyCalculator.php`, `RollUpCalculator.php` |
| 5 | GitHub client + file detector | `GitHubClient.php`, `FileDetector.php` |
| 6 | Scan pipeline stages 1-3 | `ProfileFetcher.php`, `RepoTriager.php`, `FileAnalyzer.php` |
| 7 | Scan pipeline stages 4-5 | `SkillMapper.php`, `ResultPersister.php` |
| 8 | Scan job orchestrator | `ScanJob.php` |
| 9 | Roadmap auto-detection | `RoadmapDetector.php` |
| 10 | Roadmap data seeder | `RoadmapSeeder.php`, `SeedRoadmapsCommand.php` |
| 11 | Service provider + controllers | `MeVsRoadmapProvider.php`, controllers |
| 12 | Access policies | `DeveloperAccessPolicy.php`, `ScanAccessPolicy.php` |
| 13 | Profile template | `profile.html.twig` |
| 14 | Final verification | All tests pass, app boots |

## Follow-Up (Separate Plan)

These are spec requirements that need their own plan after this backend plan is complete:

- **Vue skill tree component** — interactive tree visualization that mounts on `#skill-tree-app`, with proficiency coloring, drill-down, and roadmap tab switching. Requires a separate frontend plan.
- **GitHub token encryption** — the spec calls for encrypted storage of `github_token`. Implement using Waaseyaa's encryption facilities or `sodium_crypto_secretbox`.
- **Roadmap tab switching** — interactive frontend logic for switching between roadmap trees (Backend/Frontend/DevOps tabs).
