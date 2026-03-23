# Plan Review: 2026-03-22-me-vs-roadmap

**Status:** ISSUES FOUND

## Issues

### Issue 1 — Entities use `EntityBase` but call `set()` (Tasks 3, 8)
**Severity: High**
The plan has entities (e.g., `Scan`) extending `EntityBase`, yet they call `$this->set()` in methods like `markAnalyzing()`, `markComplete()`, `markFailed()`. Per Waaseyaa's entity persistence pipeline, `EntityBase` is for immutable value-like entities. `ContentEntityBase` is the class that provides `set()` for field mutations. All six entities should extend `ContentEntityBase` instead of `EntityBase`.

**Fix:** In Task 3, change all entity classes from `extends EntityBase` to `extends ContentEntityBase` (import `Waaseyaa\Entity\ContentEntityBase`). Update tests accordingly.

### Issue 2 — `ServiceProvider::routes()` signature mismatch (Task 11)
**Severity: Medium**
The plan's `MeVsRoadmapProvider::routes()` accepts `WaaseyaaRouter $router`, matching the interface. However, the abstract `ServiceProvider` base class declares `routes(WaaseyaaRouter $router, ?EntityTypeManager $entityTypeManager = null)`. The interface only requires `(WaaseyaaRouter $router)`. This is not a blocker (PHP allows narrowing), but the plan should use the base class signature to access `$entityTypeManager` if entity type registration is needed in routes.

**Fix:** Use the full signature `routes(WaaseyaaRouter $router, ?EntityTypeManager $entityTypeManager = null): void` for consistency with the base class.

### Issue 3 — `$this->container` usage in ServiceProvider (Task 11)
**Severity: Medium**
The plan's `register()` calls `$this->container->set(...)`. The actual `ServiceProvider` base class uses a private bindings array with `$this->bind()` / `$this->singleton()` pattern, not a public `$this->container` property. Need to verify the exact registration API.

**Fix:** Use `$this->singleton(GitHubClientInterface::class, fn() => new GitHubClient(...))` or the equivalent binding method from the base class.

### Issue 4 — No test for RoadmapPath entity (Task 3)
**Severity: Low**
Task 3's file list includes tests for Developer, Scan, RoadmapSkill, SkillAssessment, and SkillEvidence, but no `RoadmapPathTest.php`. The entity is defined but not tested.

**Fix:** Add `tests/Entity/RoadmapPathTest.php` to Task 3.

### Issue 5 — Task 12 access policies have no tests (Task 12)
**Severity: Medium**
Task 12 creates `DeveloperAccessPolicy` and `ScanAccessPolicy` but includes no test files. This violates TDD adherence — access logic (especially the owner check and `is_public` gate) should be tested.

**Fix:** Add `tests/Access/DeveloperAccessPolicyTest.php` and `tests/Access/ScanAccessPolicyTest.php` to Task 12 with tests for each access scenario.

### Issue 6 — Task 13 (Profile Template) has no test or verification step
**Severity: Low**
Task 13 creates a Twig template but has no rendering test or verification command. Other tasks include verification steps.

**Fix:** Add a smoke test or verification curl command to confirm the template renders without Twig errors.

### Issue 7 — Missing `commands()` method in ServiceProvider (Task 10-11)
**Severity: Low**
Task 10 creates `SeedRoadmapsCommand` and Task 11 registers it in the provider, but the plan doesn't show the `commands()` method override. The base `ServiceProvider` has a `commands()` method that returns console commands.

**Fix:** Show the `commands()` method implementation in Task 11's ServiceProvider that returns `[new SeedRoadmapsCommand($this->resolve(EntityRepositoryInterface::class))]`.

## Coverage Gaps

| Spec Requirement | Plan Coverage |
|---|---|
| 6 entities (Developer, Scan, RoadmapPath, RoadmapSkill, SkillAssessment, SkillEvidence) | Covered (Task 3) |
| GitHub OAuth authentication | Covered (Task 11 — AuthController) |
| 5-stage scan pipeline | Covered (Tasks 5-8) |
| Detection rules format | Covered (Task 10 seed data) |
| Proficiency scoring + roll-up | Covered (Task 4) |
| Profile visualization (tree-first layout) | Partially covered (Task 13) |
| Evidence drill-down (click skill to see repos/files) | **GAP** — Task 13's template has a Vue mount point (`#skill-tree-app`) but no Vue component is created in any task |
| Roadmap tabs (multiple roadmaps) | **GAP** — Template shows tabs but no interactive switching logic |
| Replacement strategy (delete old assessments on new scan) | Covered (Task 7, ResultPersister) |
| Rate limit awareness | Covered (Task 5, GitHub client) |
| `is_public` profile visibility | Covered (Task 12, access policy) |
| `github_token` encryption | **GAP** — Spec says "encrypted" but no encryption/decryption is shown in any task |

## Task Ordering Assessment

Task ordering is correct. Each task builds on prior ones:
1-2 (foundation) -> 3 (entities) -> 4 (domain logic) -> 5-7 (pipeline) -> 8 (orchestrator) -> 9-10 (data) -> 11-12 (wiring) -> 13-14 (UI/verification). No circular dependencies detected.

## YAGNI Assessment

The plan is well-scoped. No unnecessary abstractions detected. The `GitHubClientInterface` is justified for testability. The pipeline stage separation (fetcher/triager/analyzer/mapper/persister) aligns with the spec's 5-stage model without over-abstracting.
