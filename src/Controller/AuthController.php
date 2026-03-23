<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Developer;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;

final class AuthController
{
    public function __construct(
        private readonly EntityRepositoryInterface $repository,
        private readonly ClientInterface $httpClient,
    ) {}

    public function redirect(Request $request): Response
    {
        $clientId = getenv('GITHUB_CLIENT_ID') ?: '';
        $redirectUri = getenv('GITHUB_REDIRECT_URI') ?: 'http://localhost:8080/auth/github/callback';

        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('oauth_state', $state);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'read:user',
            'state' => $state,
        ]);

        return new RedirectResponse("https://github.com/login/oauth/authorize?{$params}");
    }

    public function callback(Request $request): Response
    {
        $code = $request->query->get('code', '');
        if ($code === '') {
            return new RedirectResponse('/auth/github');
        }

        // Verify OAuth state parameter to prevent CSRF
        $session = $request->getSession();
        $expectedState = $session->get('oauth_state', '');
        $session->remove('oauth_state');
        $receivedState = $request->query->get('state', '');

        if ($expectedState === '' || !hash_equals($expectedState, $receivedState)) {
            return new RedirectResponse('/auth/github');
        }

        $clientId = getenv('GITHUB_CLIENT_ID') ?: '';
        $clientSecret = getenv('GITHUB_CLIENT_SECRET') ?: '';

        // Exchange code for access token
        try {
            $tokenResponse = $this->httpClient->request('POST', 'https://github.com/login/oauth/access_token', [
                'json' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (\Throwable) {
            return new RedirectResponse('/auth/github');
        }

        $tokenData = json_decode($tokenResponse->getBody()->getContents(), true);
        $accessToken = $tokenData['access_token'] ?? '';

        if ($accessToken === '') {
            return new RedirectResponse('/auth/github');
        }

        // Fetch GitHub user profile
        try {
            $userResponse = $this->httpClient->request('GET', 'https://api.github.com/user', [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'User-Agent' => 'me-vs-roadmap',
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ]);
        } catch (\Throwable) {
            return new RedirectResponse('/auth/github');
        }

        $githubUser = json_decode($userResponse->getBody()->getContents(), true);
        $username = $githubUser['login'] ?? '';

        if ($username === '') {
            return new RedirectResponse('/auth/github');
        }

        // Find or create Developer entity
        $developers = $this->repository->findBy(['github_username' => $username]);
        if ($developers !== []) {
            $developer = $developers[0];
            $developer->set('github_token', $accessToken);
            $this->repository->save($developer);
        } else {
            $developer = new Developer([
                'github_username' => $username,
                'display_name' => $githubUser['name'] ?? $username,
                'avatar_url' => $githubUser['avatar_url'] ?? '',
                'github_token' => $accessToken,
                'is_public' => true,
            ]);
            $this->repository->save($developer);
        }

        // Store developer ID in session
        $session->set('developer_id', $developer->id());

        return new RedirectResponse("/profile/{$username}");
    }
}
