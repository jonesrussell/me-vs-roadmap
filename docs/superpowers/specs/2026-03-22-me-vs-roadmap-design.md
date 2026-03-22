# Me vs Roadmap — Design Spec

## Overview

A developer skill assessment tool that maps real GitHub activity against roadmap.sh learning paths. Developers connect their GitHub profile, trigger a scan, and get an interactive report showing where they stand on each roadmap — backed by evidence from their actual code.

**Audience:** Developer-first (self-assessment, learning guidance) with shareable public profiles for portfolios and hiring.

**Stack:** Waaseyaa framework (PHP 8.4+, Symfony 7.x, Nuxt 3 admin). Built via `composer create-project waaseyaa/waaseyaa` as a dogfooding exercise — framework issues documented on GitHub.

## Entity Model

### Developer

The authenticated user's profile.

| Field | Type | Notes |
|-------|------|-------|
| `github_username` | string | Unique identifier |
| `display_name` | string | From GitHub |
| `avatar_url` | string | From GitHub |
| `bio` | string | From GitHub |
| `github_token` | string (encrypted) | OAuth token, optional for future private repo support |
| `is_public` | boolean | Profile visibility, default true |

**Relations:** Has many `Scan`, has many `SkillAssessment`.

### Scan

A point-in-time analysis of a developer's GitHub profile.

| Field | Type | Notes |
|-------|------|-------|
| `status` | enum | `queued`, `analyzing`, `complete`, `failed` |
| `started_at` | datetime | When analysis began |
| `completed_at` | datetime | When analysis finished |
| `repos_analyzed` | integer | Count of repos inspected |
| `scan_metadata` | JSON | Summary metadata: repo list, languages detected, total files inspected. Not raw API responses — evidence details live in `SkillEvidence`. |

**Relations:** Belongs to `Developer`, has many `SkillEvidence`.

### RoadmapPath

A roadmap.sh learning path (e.g., "Backend", "DevOps").

| Field | Type | Notes |
|-------|------|-------|
| `slug` | string | URL-safe identifier |
| `name` | string | Display name |
| `description` | string | What this path covers |

**Relations:** Has many `RoadmapSkill`.

### RoadmapSkill

A single node on a roadmap tree (e.g., "Docker", "REST APIs", "SQL").

| Field | Type | Notes |
|-------|------|-------|
| `slug` | string | URL-safe identifier |
| `name` | string | Display name |
| `category` | string | Grouping within the roadmap |
| `parent_skill` | reference | Self-referencing for tree structure |
| `detection_rules` | JSON | Signals that map to this skill (see Detection Rules) |

**Relations:** Belongs to `RoadmapPath`, optional parent `RoadmapSkill`, has many `SkillAssessment`.

### SkillAssessment

The computed link between a developer and a skill.

| Field | Type | Notes |
|-------|------|-------|
| `proficiency` | enum | `none`, `beginner`, `intermediate`, `advanced` |
| `confidence` | float | 0.0–1.0, how certain the assessment is |
| `scan` | reference | The scan that produced this assessment |

**Relations:** Belongs to `Developer`, belongs to `RoadmapSkill`, belongs to `Scan`, has many `SkillEvidence`.

**Replacement strategy:** Each scan overwrites all `SkillAssessment` entities for that developer. Previous assessments are deleted when a new scan completes. There is no versioning — a developer's profile always reflects their latest scan.

### SkillEvidence

Specific proof backing an assessment.

| Field | Type | Notes |
|-------|------|-------|
| `type` | enum | `language_usage`, `config_file`, `dependency`, `ci_workflow`, `test_presence`, `commit_pattern` |
| `source_repo` | string | Repository where evidence was found |
| `source_file` | string | File path within the repo |
| `details` | JSON | Specific findings |

**Relations:** Belongs to `Scan`, belongs to `SkillAssessment`.

## GitHub Analysis Pipeline

The scan runs as a Waaseyaa queued job with five stages.

### Stage 1: Profile Fetch

- Fetch user profile and public repos list via GitHub API (paginated)
- Store metadata: languages, topics, descriptions, stars, fork status
- Filter out forks unless they have significant commits from the user

### Stage 2: Repo Triage

- Rank repos by signal value: recent activity, size, not-a-fork, has commits by user
- Select top ~30 repos for deeper inspection (controls API rate limit usage)
- Skip noise: tutorial forks, trivial repos

### Stage 3: Targeted File Detection

For each selected repo, use the GitHub Trees API (single call per repo) to get the full file listing. Match file patterns against detection rules:

| Pattern | Skill Signal |
|---------|-------------|
| `Dockerfile`, `docker-compose.yml` | Docker |
| `.github/workflows/*.yml` | CI/CD, GitHub Actions |
| `package.json` (inspect deps) | React, Vue, Express, etc. |
| `go.mod`, `composer.json`, `Cargo.toml` | Language + framework detection |
| `*_test.go`, `tests/`, `__tests__/` | Testing practices |
| `terraform/`, `k8s/`, `helm/` | Infrastructure skills |

Fetch raw content only for key files (package.json, composer.json, CI configs) — no full repo cloning.

### Stage 4: Skill Mapping

- Run detection rules from `RoadmapSkill` entities against collected evidence
- Calculate a raw score (0–100) per skill from three weighted signals:
  - **Frequency (40%):** Number of repos showing this skill, normalized against total repos analyzed
  - **Recency (30%):** Proportion of evidence from repos with commits in the last 12 months
  - **Depth (30%):** Complexity indicators (e.g., multi-stage Dockerfile scores higher than a bare `FROM`)
- Map raw score to proficiency:
  - `none`: score = 0 (no evidence found)
  - `beginner`: score 1–30
  - `intermediate`: score 31–65
  - `advanced`: score 66–100
- **Confidence** = (number of evidence items for this skill) / (max evidence items seen for any skill in this scan), clamped to 0.0–1.0. Displayed as a subtle opacity/bar on the skill node — high confidence = solid, low confidence = faded. Skills with confidence < 0.3 show a "?" indicator.

### Stage 5: Persist Results

- Create `SkillAssessment` and `SkillEvidence` entities
- Mark scan as `complete`

### Rate Limits

GitHub API allows 5,000 requests/hour for authenticated users. A typical scan with 30 repos uses ~60–100 API calls (profile + repo list + trees + selective file fetches). Well within limits.

## Detection Rules Format

Stored as JSON on each `RoadmapSkill` entity:

```json
{
  "languages": ["go"],
  "files": ["go.mod", "go.sum"],
  "dependencies": {
    "go.mod": ["github.com/gin-gonic/gin"]
  },
  "config_patterns": [".github/workflows/*.yml"],
  "content_matches": {
    "Dockerfile": ["FROM golang"]
  }
}
```

Rules are composable — a skill like "REST APIs" checks for HTTP framework deps across multiple languages, route handler files, and OpenAPI specs.

### Proficiency Roll-Up

Skills form a tree. Parent proficiency is the weighted average of child raw scores (equal weights), mapped to the same proficiency thresholds. A parent node requires at least 2 children with evidence to display a rolled-up proficiency; otherwise it shows `none`.

Example: if "Docker" scores 80 (advanced) and "Kubernetes" scores 40 (intermediate), their parent "Containerization" scores (80+40)/2 = 60 → intermediate.

## Roadmap Data

### Approach: Curated Seed + Manual Maintenance

- Pre-populate `RoadmapPath` and `RoadmapSkill` entities from roadmap.sh's open-source data
- Hand-write `detection_rules` for each skill (this is the valuable/hard part)
- Update deliberately rather than auto-syncing from their repo (avoids brittleness)

### MVP Roadmaps

1. **Backend** — languages, databases, APIs, caching, testing, etc.
2. **Frontend** — HTML/CSS, JavaScript, frameworks, build tools, testing
3. **DevOps** — containers, CI/CD, infrastructure, monitoring, cloud

### Roadmap Detection

Auto-detect which roadmaps are relevant after a scan completes. A roadmap is considered relevant if >= 3 of its leaf skills have evidence (any proficiency above `none`). All relevant roadmaps are shown by default. The developer can manually add or remove roadmaps from their profile.

## Profile & Visualization

### Layout: Tree-First

The profile page uses a tree-first layout:

1. **Hero header** — avatar, name, GitHub username, last scan time, auto-detected roadmap badges
2. **Skill tree visualization** — interactive tree matching roadmap.sh's structure. Nodes colored by proficiency:
   - Green (■■■): Advanced
   - Yellow (■■□): Intermediate
   - Blue (■□□): Beginner
   - Gray (□□□): No evidence
3. **Proficiency chips** — quick-glance skill tags below the tree
4. **Stats footer** — repos analyzed, coverage percentage per roadmap
5. **Evidence drill-down** — click any skill node to see the specific repos, files, and findings

### Roadmap Tabs

When multiple roadmaps are relevant, tabs switch between roadmap trees.

## Sharing & Public Profiles

- **Public URL:** `/profile/{github_username}` — no auth required to view
- **Visible data:** Profile header, skill tree, proficiency levels, evidence summaries
- **Privacy:** Developers control visibility via the `is_public` field on `Developer`. Default is public. Per-roadmap hiding is out of scope for MVP — it's all-or-nothing.
- **No private repo data** shown unless the developer explicitly opts in (future feature)

## Authentication

GitHub OAuth for login. The OAuth token is also used for API access during scans.

## MVP Scope

### In Scope

- GitHub OAuth login
- On-demand scan trigger
- 3 roadmaps: Backend, Frontend, DevOps
- Hybrid GitHub analysis (API + targeted file fetches)
- Interactive skill tree visualization (tree-first layout)
- Evidence drill-down per skill
- Public shareable profile URL
- Built on Waaseyaa via `composer create-project`

### Out of Scope (Future)

- Auto-sync / webhook-driven updates
- Private repo analysis
- Additional roadmaps (Full Stack, Android, etc.)
- Embed badges (shields.io style)
- Comparison view (developer vs developer)
- Learning recommendations ("you should learn X next")
- AI-powered analysis (LLM-based code understanding)
- Per-roadmap visibility controls (hide individual roadmaps from public profile)
