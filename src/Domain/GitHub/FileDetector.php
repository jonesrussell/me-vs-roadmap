<?php
declare(strict_types=1);

namespace App\Domain\GitHub;

final class FileDetector
{
    private const array EXTENSION_MAP = [
        'go' => ['.go', 'go.mod'],
        'php' => ['.php', 'composer.json'],
        'typescript' => ['.ts', '.tsx', 'tsconfig.json'],
        'javascript' => ['.js', '.jsx', 'package.json'],
        'python' => ['.py', 'requirements.txt', 'pyproject.toml'],
        'rust' => ['.rs', 'Cargo.toml'],
        'java' => ['.java', 'pom.xml', 'build.gradle'],
        'csharp' => ['.cs', '.csproj'],
        'ruby' => ['.rb', 'Gemfile'],
    ];

    /**
     * Match exact filenames from rules against tree paths.
     *
     * @param list<string> $tree
     * @param array{files?: list<string>} $rules
     * @return list<string>
     */
    public function matchFiles(array $tree, array $rules): array
    {
        $filesToMatch = $rules['files'] ?? [];
        if ($filesToMatch === []) {
            return [];
        }

        $matched = [];
        foreach ($tree as $path) {
            $basename = basename($path);
            foreach ($filesToMatch as $file) {
                if ($path === $file || $basename === $file) {
                    $matched[] = $path;
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * Match glob patterns from rules against tree paths.
     *
     * @param list<string> $tree
     * @param array{config_patterns?: list<string>} $rules
     * @return list<string>
     */
    public function matchConfigPatterns(array $tree, array $rules): array
    {
        $patterns = $rules['config_patterns'] ?? [];
        if ($patterns === []) {
            return [];
        }

        $matched = [];
        foreach ($tree as $path) {
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $path)) {
                    $matched[] = $path;
                    break;
                }
            }
        }

        return $matched;
    }

    /**
     * Check if tree contains files matching any of the specified languages.
     *
     * @param list<string> $tree
     * @param array{languages?: list<string>} $rules
     */
    public function matchesLanguage(array $tree, array $rules): bool
    {
        $languages = $rules['languages'] ?? [];

        foreach ($languages as $language) {
            $indicators = self::EXTENSION_MAP[strtolower($language)] ?? [];
            foreach ($tree as $path) {
                $basename = basename($path);
                foreach ($indicators as $indicator) {
                    if (str_starts_with($indicator, '.')) {
                        if (str_ends_with($basename, $indicator)) {
                            return true;
                        }
                    } elseif ($basename === $indicator) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if the tree contains test files or directories.
     *
     * @param list<string> $tree
     */
    public function hasTests(array $tree): bool
    {
        foreach ($tree as $path) {
            $basename = basename($path);

            // Go test files
            if (str_ends_with($basename, '_test.go')) {
                return true;
            }

            // PHP test files
            if (str_ends_with($basename, 'Test.php')) {
                return true;
            }

            // JS/TS test files
            if (preg_match('/\.test\.[jt]sx?$/', $basename)) {
                return true;
            }

            // tests/ or __tests__/ directory
            if (str_starts_with($path, 'tests/') || str_contains($path, '__tests__/')) {
                return true;
            }
        }

        return false;
    }
}
