<?php
declare(strict_types=1);

namespace App\Domain\GitHub;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

final class GitHubClient implements GitHubClientInterface
{
    private const string BASE_URL = 'https://api.github.com';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly ?string $token = null,
    ) {}

    public function getUser(string $username): array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . "/users/{$username}", [
            'headers' => $this->headers(),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getUserRepos(string $username): array
    {
        $repos = [];
        $page = 1;

        do {
            $response = $this->httpClient->request('GET', self::BASE_URL . "/users/{$username}/repos", [
                'headers' => $this->headers(),
                'query' => [
                    'per_page' => 100,
                    'sort' => 'pushed',
                    'type' => 'owner',
                    'page' => $page,
                ],
            ]);

            $batch = json_decode($response->getBody()->getContents(), true);
            if ($batch === []) {
                break;
            }

            $repos = array_merge($repos, $batch);
            $page++;
        } while (count($batch) === 100);

        return $repos;
    }

    public function getRepoTree(string $fullName, string $branch = 'HEAD'): array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . "/repos/{$fullName}/git/trees/{$branch}", [
            'headers' => $this->headers(),
            'query' => ['recursive' => '1'],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return array_values(array_map(
            static fn(array $item): string => $item['path'],
            array_filter(
                $data['tree'] ?? [],
                static fn(array $item): bool => $item['type'] === 'blob',
            ),
        ));
    }

    public function getFileContent(string $fullName, string $path): ?string
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . "/repos/{$fullName}/contents/{$path}", [
                'headers' => $this->headers(),
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            return base64_decode($data['content'] ?? '', true) ?: null;
        } catch (GuzzleException) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'me-vs-roadmap',
        ];

        if ($this->token !== null) {
            $headers['Authorization'] = "Bearer {$this->token}";
        }

        return $headers;
    }
}
