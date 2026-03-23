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
