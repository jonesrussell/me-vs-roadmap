<?php

declare(strict_types=1);

namespace App\Seed;

use App\Entity\RoadmapPath;
use App\Entity\RoadmapSkill;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;

final class RoadmapSeeder
{
    public function __construct(
        private readonly EntityRepositoryInterface $repository,
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
            'name' => 'Backend Development',
            'description' => 'Server-side programming, databases, APIs, and infrastructure for web applications.',
        ]);
        $this->repository->save($path);

        $this->seedSkillTree($path->id(), [
            [
                'slug' => 'languages',
                'name' => 'Languages',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'go',
                        'name' => 'Go',
                        'detection_rules' => [
                            'languages' => ['go'],
                            'files' => ['go.mod', 'go.sum'],
                            'extensions' => ['.go'],
                        ],
                    ],
                    [
                        'slug' => 'php',
                        'name' => 'PHP',
                        'detection_rules' => [
                            'languages' => ['php'],
                            'files' => ['composer.json', 'composer.lock'],
                            'extensions' => ['.php'],
                        ],
                    ],
                    [
                        'slug' => 'python',
                        'name' => 'Python',
                        'detection_rules' => [
                            'languages' => ['python'],
                            'files' => ['requirements.txt', 'setup.py', 'pyproject.toml', 'Pipfile'],
                            'extensions' => ['.py'],
                        ],
                    ],
                    [
                        'slug' => 'javascript-nodejs',
                        'name' => 'JavaScript/Node.js',
                        'detection_rules' => [
                            'languages' => ['javascript', 'typescript'],
                            'files' => ['package.json'],
                            'dependencies' => [
                                'package.json' => ['express', 'fastify', 'koa', 'hapi', 'nestjs'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'rust',
                        'name' => 'Rust',
                        'detection_rules' => [
                            'languages' => ['rust'],
                            'files' => ['Cargo.toml', 'Cargo.lock'],
                            'extensions' => ['.rs'],
                        ],
                    ],
                    [
                        'slug' => 'java',
                        'name' => 'Java',
                        'detection_rules' => [
                            'languages' => ['java'],
                            'files' => ['pom.xml', 'build.gradle', 'build.gradle.kts'],
                            'extensions' => ['.java'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'databases',
                'name' => 'Databases',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'sql-relational',
                        'name' => 'SQL/Relational',
                        'detection_rules' => [
                            'files' => ['schema.sql', 'migrations/'],
                            'extensions' => ['.sql'],
                            'dependencies' => [
                                'package.json' => ['pg', 'mysql2', 'knex', 'sequelize', 'prisma', 'typeorm'],
                                'composer.json' => ['doctrine/dbal', 'illuminate/database'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'nosql',
                        'name' => 'NoSQL',
                        'detection_rules' => [
                            'dependencies' => [
                                'package.json' => ['mongoose', 'mongodb', 'redis', 'ioredis'],
                                'composer.json' => ['mongodb/mongodb', 'predis/predis'],
                            ],
                            'files' => ['mongod.conf'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'apis',
                'name' => 'APIs',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'rest-apis',
                        'name' => 'REST APIs',
                        'detection_rules' => [
                            'files' => ['openapi.yaml', 'openapi.json', 'swagger.yaml', 'swagger.json'],
                            'content_matches' => [
                                '*.go' => ['http.HandleFunc', 'gin.Default', 'echo.New', 'mux.NewRouter'],
                                '*.php' => ['Route::get', 'Route::post', 'Route::apiResource'],
                                '*.py' => ['@app.route', 'FastAPI()', 'APIRouter'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'graphql',
                        'name' => 'GraphQL',
                        'detection_rules' => [
                            'files' => ['schema.graphql', '.graphqlrc', 'codegen.yml'],
                            'extensions' => ['.graphql', '.gql'],
                            'dependencies' => [
                                'package.json' => ['graphql', 'apollo-server', '@apollo/server', 'type-graphql'],
                                'composer.json' => ['webonyx/graphql-php', 'nuwave/lighthouse'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'testing-backend',
                'name' => 'Testing',
                'detection_rules' => [
                    'files' => ['phpunit.xml', 'phpunit.xml.dist', 'pytest.ini', 'conftest.py'],
                    'config_patterns' => ['*_test.go', '**/*Test.php', 'test_*.py'],
                    'dependencies' => [
                        'composer.json' => ['phpunit/phpunit', 'pestphp/pest'],
                        'package.json' => ['mocha', 'jest', 'vitest', 'supertest'],
                    ],
                ],
            ],
            [
                'slug' => 'caching',
                'name' => 'Caching',
                'detection_rules' => [
                    'files' => ['redis.conf', 'memcached.conf'],
                    'dependencies' => [
                        'package.json' => ['redis', 'ioredis', 'node-cache', 'memcached'],
                        'composer.json' => ['predis/predis', 'symfony/cache'],
                    ],
                    'content_matches' => [
                        '*.go' => ['go-redis', 'groupcache'],
                    ],
                ],
            ],
            [
                'slug' => 'authentication',
                'name' => 'Authentication',
                'detection_rules' => [
                    'dependencies' => [
                        'package.json' => ['passport', 'jsonwebtoken', 'bcrypt', 'next-auth', '@auth/core'],
                        'composer.json' => ['laravel/sanctum', 'laravel/fortify', 'tymon/jwt-auth', 'league/oauth2-server'],
                    ],
                    'content_matches' => [
                        '*.go' => ['golang-jwt', 'oauth2', 'bcrypt'],
                    ],
                ],
            ],
        ]);
    }

    private function seedFrontend(): void
    {
        $path = new RoadmapPath([
            'slug' => 'frontend',
            'name' => 'Frontend Development',
            'description' => 'Client-side UI frameworks, styling, build tooling, and browser-based testing.',
        ]);
        $this->repository->save($path);

        $this->seedSkillTree($path->id(), [
            [
                'slug' => 'frameworks',
                'name' => 'Frameworks',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'react',
                        'name' => 'React',
                        'detection_rules' => [
                            'dependencies' => [
                                'package.json' => ['react', 'react-dom', 'next', 'gatsby', '@remix-run/react'],
                            ],
                            'extensions' => ['.jsx', '.tsx'],
                        ],
                    ],
                    [
                        'slug' => 'vuejs',
                        'name' => 'Vue.js',
                        'detection_rules' => [
                            'dependencies' => [
                                'package.json' => ['vue', 'nuxt', '@vitejs/plugin-vue', 'vuetify', 'pinia'],
                            ],
                            'extensions' => ['.vue'],
                        ],
                    ],
                    [
                        'slug' => 'angular',
                        'name' => 'Angular',
                        'detection_rules' => [
                            'files' => ['angular.json', '.angular-cli.json'],
                            'dependencies' => [
                                'package.json' => ['@angular/core', '@angular/cli'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'svelte',
                        'name' => 'Svelte',
                        'detection_rules' => [
                            'files' => ['svelte.config.js', 'svelte.config.ts'],
                            'dependencies' => [
                                'package.json' => ['svelte', '@sveltejs/kit'],
                            ],
                            'extensions' => ['.svelte'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'css',
                'name' => 'CSS',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'tailwind-css',
                        'name' => 'Tailwind CSS',
                        'detection_rules' => [
                            'files' => ['tailwind.config.js', 'tailwind.config.ts', 'tailwind.config.cjs'],
                            'dependencies' => [
                                'package.json' => ['tailwindcss', '@tailwindcss/forms', '@tailwindcss/typography'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'sass-scss',
                        'name' => 'Sass/SCSS',
                        'detection_rules' => [
                            'extensions' => ['.scss', '.sass'],
                            'dependencies' => [
                                'package.json' => ['sass', 'node-sass', 'sass-loader'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'typescript',
                'name' => 'TypeScript',
                'detection_rules' => [
                    'files' => ['tsconfig.json', 'tsconfig.base.json', 'tsconfig.app.json'],
                    'extensions' => ['.ts', '.tsx'],
                    'dependencies' => [
                        'package.json' => ['typescript'],
                    ],
                ],
            ],
            [
                'slug' => 'build-tools',
                'name' => 'Build Tools',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'vite',
                        'name' => 'Vite',
                        'detection_rules' => [
                            'files' => ['vite.config.js', 'vite.config.ts', 'vite.config.mts'],
                            'dependencies' => [
                                'package.json' => ['vite', '@vitejs/plugin-react', '@vitejs/plugin-vue'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'webpack',
                        'name' => 'Webpack',
                        'detection_rules' => [
                            'files' => ['webpack.config.js', 'webpack.config.ts', 'webpack.mix.js'],
                            'dependencies' => [
                                'package.json' => ['webpack', 'webpack-cli', 'webpack-dev-server'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'testing-frontend',
                'name' => 'Testing',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'jest',
                        'name' => 'Jest',
                        'detection_rules' => [
                            'files' => ['jest.config.js', 'jest.config.ts', 'jest.config.mjs'],
                            'dependencies' => [
                                'package.json' => ['jest', '@jest/core', 'ts-jest', '@testing-library/jest-dom'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'vitest',
                        'name' => 'Vitest',
                        'detection_rules' => [
                            'files' => ['vitest.config.js', 'vitest.config.ts'],
                            'dependencies' => [
                                'package.json' => ['vitest', '@vitest/ui', '@vitest/coverage-v8'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'playwright',
                        'name' => 'Playwright',
                        'detection_rules' => [
                            'files' => ['playwright.config.js', 'playwright.config.ts'],
                            'dependencies' => [
                                'package.json' => ['@playwright/test', 'playwright'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function seedDevOps(): void
    {
        $path = new RoadmapPath([
            'slug' => 'devops',
            'name' => 'DevOps',
            'description' => 'Containers, CI/CD pipelines, infrastructure as code, monitoring, and system administration.',
        ]);
        $this->repository->save($path);

        $this->seedSkillTree($path->id(), [
            [
                'slug' => 'containers',
                'name' => 'Containers',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'docker',
                        'name' => 'Docker',
                        'detection_rules' => [
                            'files' => ['Dockerfile', 'docker-compose.yml', 'docker-compose.yaml', '.dockerignore'],
                            'content_matches' => [
                                'Dockerfile' => ['FROM', 'RUN', 'COPY', 'ENTRYPOINT', 'CMD'],
                            ],
                        ],
                    ],
                    [
                        'slug' => 'kubernetes',
                        'name' => 'Kubernetes',
                        'detection_rules' => [
                            'files' => ['k8s/', 'kubernetes/', 'helmfile.yaml', 'Chart.yaml'],
                            'config_patterns' => ['k8s/*.yml', 'k8s/*.yaml', 'kubernetes/*.yml'],
                            'content_matches' => [
                                '*.yaml' => ['apiVersion:', 'kind: Deployment', 'kind: Service', 'kind: Pod'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ci-cd',
                'name' => 'CI/CD',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'github-actions',
                        'name' => 'GitHub Actions',
                        'detection_rules' => [
                            'config_patterns' => ['.github/workflows/*.yml', '.github/workflows/*.yaml'],
                            'files' => ['.github/workflows/'],
                        ],
                    ],
                    [
                        'slug' => 'gitlab-ci',
                        'name' => 'GitLab CI',
                        'detection_rules' => [
                            'files' => ['.gitlab-ci.yml'],
                            'config_patterns' => ['.gitlab-ci.yml'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'infrastructure',
                'name' => 'Infrastructure',
                'detection_rules' => [],
                'children' => [
                    [
                        'slug' => 'terraform',
                        'name' => 'Terraform',
                        'detection_rules' => [
                            'files' => ['main.tf', 'variables.tf', 'terraform.tfvars', '.terraform.lock.hcl'],
                            'extensions' => ['.tf', '.tfvars'],
                        ],
                    ],
                    [
                        'slug' => 'ansible',
                        'name' => 'Ansible',
                        'detection_rules' => [
                            'files' => ['ansible.cfg', 'playbook.yml', 'inventory.yml', 'site.yml'],
                            'config_patterns' => ['roles/*/tasks/main.yml', 'playbooks/*.yml'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'monitoring',
                'name' => 'Monitoring',
                'detection_rules' => [
                    'files' => ['prometheus.yml', 'grafana/', 'datadog.yaml', '.env.sentry'],
                    'dependencies' => [
                        'package.json' => ['@sentry/node', '@sentry/browser', 'prom-client', 'newrelic'],
                        'composer.json' => ['sentry/sentry-laravel', 'sentry/sentry-symfony'],
                    ],
                    'content_matches' => [
                        'docker-compose.yml' => ['prometheus', 'grafana', 'jaeger'],
                    ],
                ],
            ],
            [
                'slug' => 'linux-shell',
                'name' => 'Linux/Shell',
                'detection_rules' => [
                    'files' => ['Makefile', 'Taskfile.yml', 'Justfile'],
                    'extensions' => ['.sh', '.bash', '.zsh'],
                    'config_patterns' => ['scripts/*.sh', 'bin/*'],
                ],
            ],
        ]);
    }

    /**
     * @param array<int, array{slug: string, name: string, detection_rules: array<string, mixed>, children?: array<int, mixed>}> $nodes
     */
    private function seedSkillTree(string $pathId, array $nodes, ?string $parentId = null): void
    {
        foreach ($nodes as $node) {
            $skill = new RoadmapSkill([
                'slug' => $node['slug'],
                'name' => $node['name'],
                'roadmap_path_id' => $pathId,
                'parent_skill_id' => $parentId,
                'detection_rules' => $node['detection_rules'],
            ]);
            $this->repository->save($skill);

            if (isset($node['children']) && \is_array($node['children'])) {
                $this->seedSkillTree($pathId, $node['children'], $skill->id());
            }
        }
    }
}
