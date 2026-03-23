<?php

declare(strict_types=1);

namespace App\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\Roadmap\ProficiencyCalculator;
use App\Support\EvidenceType;

final class SkillMapper
{
    public function __construct(
        private readonly FileDetector $detector,
    ) {}

    /**
     * Map repo analysis results to skill assessments.
     *
     * @param list<array{id: int, slug: string, detection_rules: array<string, mixed>}> $skills
     * @param list<array{repo_name: string, tree: list<string>, contents: array<string, string>, pushed_at: string}> $repoResults
     * @param int $totalRepos
     * @return array<string, array{proficiency: \App\Support\Proficiency, confidence: float, evidence: list<array<string, mixed>>, raw_score: float}>
     */
    public function mapSkills(array $skills, array $repoResults, int $totalRepos): array
    {
        $assessments = [];
        $maxEvidenceCount = 0;

        foreach ($skills as $skill) {
            $slug = $skill['slug'];
            $rules = $skill['detection_rules'];
            $evidence = [];
            $evidenceRepos = [];

            foreach ($repoResults as $repo) {
                $repoEvidence = $this->detectEvidence($repo, $rules);

                if ($repoEvidence !== []) {
                    $evidenceRepos[$repo['repo_name']] = $repo;
                    $evidence = array_merge($evidence, $repoEvidence);
                }
            }

            $repoCount = count($evidenceRepos);

            $recentRatio = 0.0;
            if ($repoCount > 0) {
                $cutoff = new \DateTimeImmutable('-12 months');
                $recentCount = 0;
                foreach ($evidenceRepos as $repo) {
                    $pushedAt = new \DateTimeImmutable($repo['pushed_at']);
                    if ($pushedAt >= $cutoff) {
                        ++$recentCount;
                    }
                }
                $recentRatio = $recentCount / $repoCount;
            }

            $depthScore = 0.0;
            if ($repoCount > 0) {
                $totalDepth = 0.0;
                foreach ($evidenceRepos as $repoName => $repo) {
                    $repoEvidenceItems = array_filter(
                        $evidence,
                        static fn(array $e): bool => $e['source_repo'] === $repoName,
                    );
                    $contentMatches = count($repoEvidenceItems);
                    $totalDepth += min(0.5 + (0.25 * $contentMatches), 1.0);
                }
                $depthScore = $totalDepth / $repoCount;
            }

            $rawScore = $totalRepos > 0
                ? ProficiencyCalculator::calculateRawScore($repoCount, $totalRepos, $recentRatio, $depthScore)
                : 0.0;

            $proficiency = ProficiencyCalculator::fromScore($rawScore);

            $assessments[$slug] = [
                'proficiency' => $proficiency,
                'confidence' => 0.0, // calculated after all skills
                'evidence' => $evidence,
                'raw_score' => $rawScore,
            ];

            $evidenceCount = count($evidence);
            if ($evidenceCount > $maxEvidenceCount) {
                $maxEvidenceCount = $evidenceCount;
            }
        }

        // Calculate confidence for all skills
        foreach ($assessments as $slug => &$assessment) {
            $assessment['confidence'] = ProficiencyCalculator::calculateConfidence(
                count($assessment['evidence']),
                $maxEvidenceCount,
            );
        }
        unset($assessment);

        return $assessments;
    }

    /**
     * @param array{repo_name: string, tree: list<string>, contents: array<string, string>, pushed_at: string} $repo
     * @param array<string, mixed> $rules
     * @return list<array<string, mixed>>
     */
    private function detectEvidence(array $repo, array $rules): array
    {
        $evidence = [];
        $repoName = $repo['repo_name'];
        $tree = $repo['tree'];
        $contents = $repo['contents'];

        // matchFiles -> ConfigFile
        $matchedFiles = $this->detector->matchFiles($tree, $rules);
        foreach ($matchedFiles as $file) {
            $evidence[] = [
                'type' => EvidenceType::ConfigFile->value,
                'source_repo' => $repoName,
                'source_file' => $file,
                'details' => ['matched_file' => $file],
            ];
        }

        // matchConfigPatterns -> CiWorkflow
        $matchedPatterns = $this->detector->matchConfigPatterns($tree, $rules);
        foreach ($matchedPatterns as $file) {
            $evidence[] = [
                'type' => EvidenceType::CiWorkflow->value,
                'source_repo' => $repoName,
                'source_file' => $file,
                'details' => ['matched_pattern' => $file],
            ];
        }

        // matchesLanguage -> LanguageUsage
        if ($this->detector->matchesLanguage($tree, $rules)) {
            $evidence[] = [
                'type' => EvidenceType::LanguageUsage->value,
                'source_repo' => $repoName,
                'source_file' => '',
                'details' => ['languages' => $rules['languages'] ?? []],
            ];
        }

        // Check dependencies in file contents
        $dependencies = $rules['dependencies'] ?? [];
        foreach ($dependencies as $filename => $deps) {
            if (!isset($contents[$filename])) {
                continue;
            }
            $fileContent = $contents[$filename];
            foreach ($deps as $dep) {
                if (str_contains($fileContent, $dep)) {
                    $evidence[] = [
                        'type' => EvidenceType::Dependency->value,
                        'source_repo' => $repoName,
                        'source_file' => $filename,
                        'details' => ['dependency' => $dep],
                    ];
                }
            }
        }

        // hasTests -> TestPresence
        if ($this->detector->hasTests($tree)) {
            $evidence[] = [
                'type' => EvidenceType::TestPresence->value,
                'source_repo' => $repoName,
                'source_file' => '',
                'details' => [],
            ];
        }

        return $evidence;
    }
}
