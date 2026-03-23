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
    private FileDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new FileDetector();
    }

    #[Test]
    public function matchFilesFindsExactMatch(): void
    {
        $tree = ['src/main.go', 'Taskfile.yml', 'README.md'];
        $rules = ['files' => ['Taskfile.yml']];

        $result = $this->detector->matchFiles($tree, $rules);

        $this->assertContains('Taskfile.yml', $result);
    }

    #[Test]
    public function matchFilesReturnsEmptyWhenNoMatch(): void
    {
        $tree = ['src/main.go', 'README.md'];
        $rules = ['files' => ['Taskfile.yml']];

        $result = $this->detector->matchFiles($tree, $rules);

        $this->assertEmpty($result);
    }

    #[Test]
    public function matchFilesMatchesBasename(): void
    {
        $tree = ['some/nested/Dockerfile', 'src/main.go'];
        $rules = ['files' => ['Dockerfile']];

        $result = $this->detector->matchFiles($tree, $rules);

        $this->assertContains('some/nested/Dockerfile', $result);
    }

    #[Test]
    public function matchConfigPatternsMatchesGlob(): void
    {
        $tree = [
            '.github/workflows/ci.yml',
            '.github/workflows/deploy.yml',
            'src/main.go',
        ];
        $rules = ['config_patterns' => ['.github/workflows/*.yml']];

        $result = $this->detector->matchConfigPatterns($tree, $rules);

        $this->assertCount(2, $result);
        $this->assertContains('.github/workflows/ci.yml', $result);
        $this->assertContains('.github/workflows/deploy.yml', $result);
    }

    #[Test]
    public function matchConfigPatternsReturnsEmptyWhenNoPatterns(): void
    {
        $tree = ['src/main.go'];
        $rules = [];

        $result = $this->detector->matchConfigPatterns($tree, $rules);

        $this->assertEmpty($result);
    }

    #[Test]
    public function matchesLanguageDetectsGoFiles(): void
    {
        $tree = ['cmd/main.go', 'go.mod', 'README.md'];
        $rules = ['languages' => ['go']];

        $this->assertTrue($this->detector->matchesLanguage($tree, $rules));
    }

    #[Test]
    public function matchesLanguageDetectsPhpFiles(): void
    {
        $tree = ['src/App.php', 'composer.json'];
        $rules = ['languages' => ['php']];

        $this->assertTrue($this->detector->matchesLanguage($tree, $rules));
    }

    #[Test]
    public function matchesLanguageReturnsFalseWhenNoMatch(): void
    {
        $tree = ['README.md', 'LICENSE'];
        $rules = ['languages' => ['go']];

        $this->assertFalse($this->detector->matchesLanguage($tree, $rules));
    }

    #[Test]
    public function hasTestsDetectsGoTestFiles(): void
    {
        $tree = ['main.go', 'main_test.go'];

        $this->assertTrue($this->detector->hasTests($tree));
    }

    #[Test]
    public function hasTestsDetectsPhpTestFiles(): void
    {
        $tree = ['src/App.php', 'tests/AppTest.php'];

        $this->assertTrue($this->detector->hasTests($tree));
    }

    #[Test]
    public function hasTestsDetectsTypeScriptTestFiles(): void
    {
        $tree = ['src/app.ts', 'src/app.test.ts'];

        $this->assertTrue($this->detector->hasTests($tree));
    }

    #[Test]
    public function hasTestsDetectsTestsDirectory(): void
    {
        $tree = ['src/app.py', 'tests/test_app.py'];

        $this->assertTrue($this->detector->hasTests($tree));
    }

    #[Test]
    public function hasTestsDetectsJestDirectory(): void
    {
        $tree = ['src/index.js', '__tests__/index.test.js'];

        $this->assertTrue($this->detector->hasTests($tree));
    }

    #[Test]
    public function hasTestsReturnsFalseWhenNoTests(): void
    {
        $tree = ['src/main.go', 'README.md'];

        $this->assertFalse($this->detector->hasTests($tree));
    }
}
