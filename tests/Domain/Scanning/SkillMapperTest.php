<?php

declare(strict_types=1);

namespace App\Tests\Domain\Scanning;

use App\Domain\GitHub\FileDetector;
use App\Domain\Scanning\SkillMapper;
use App\Support\EvidenceType;
use App\Support\Proficiency;
use PHPUnit\Framework\TestCase;

final class SkillMapperTest extends TestCase
{
    private SkillMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new SkillMapper(new FileDetector());
    }

    public function testMapsDockerfileToDockerSkill(): void
    {
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
                'repo_name' => 'my-app',
                'tree' => ['Dockerfile', 'src/index.php'],
                'contents' => [],
                'pushed_at' => date('c'),
            ],
            [
                'repo_name' => 'api-service',
                'tree' => ['docker-compose.yml', 'main.go'],
                'contents' => [],
                'pushed_at' => date('c'),
            ],
        ];

        $result = $this->mapper->mapSkills($skills, $repoResults, 2);

        self::assertArrayHasKey('docker', $result);
        $assessment = $result['docker'];
        self::assertNotSame(Proficiency::None, $assessment['proficiency']);

        $repos = array_unique(array_column($assessment['evidence'], 'source_repo'));
        self::assertContains('my-app', $repos);
        self::assertContains('api-service', $repos);
    }

    public function testReturnsNoneForNoEvidence(): void
    {
        $skills = [
            [
                'id' => 2,
                'slug' => 'kubernetes',
                'detection_rules' => [
                    'files' => ['k8s.yml', 'kubernetes.yml'],
                    'config_patterns' => ['.kube/*'],
                ],
            ],
        ];

        $repoResults = [
            [
                'repo_name' => 'simple-app',
                'tree' => ['index.html', 'style.css'],
                'contents' => [],
                'pushed_at' => date('c'),
            ],
        ];

        $result = $this->mapper->mapSkills($skills, $repoResults, 1);

        self::assertArrayHasKey('kubernetes', $result);
        self::assertSame(Proficiency::None, $result['kubernetes']['proficiency']);
        self::assertEmpty($result['kubernetes']['evidence']);
    }

    public function testChecksDependenciesInFileContents(): void
    {
        $skills = [
            [
                'id' => 3,
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
                'repo_name' => 'frontend-app',
                'tree' => ['package.json', 'src/App.tsx'],
                'contents' => [
                    'package.json' => '{"dependencies": {"react": "^18.0.0", "react-dom": "^18.0.0"}}',
                ],
                'pushed_at' => date('c'),
            ],
        ];

        $result = $this->mapper->mapSkills($skills, $repoResults, 1);

        self::assertArrayHasKey('react', $result);
        $assessment = $result['react'];
        self::assertNotSame(Proficiency::None, $assessment['proficiency']);

        $depEvidence = array_filter(
            $assessment['evidence'],
            static fn(array $e): bool => $e['type'] === EvidenceType::Dependency->value,
        );
        self::assertNotEmpty($depEvidence);
    }
}
