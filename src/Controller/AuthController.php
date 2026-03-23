<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Developer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;

final class AuthController
{
    public function __construct(
        private readonly EntityRepositoryInterface $repository,
    ) {}

    public function redirect(): Response
    {
        $clientId = getenv('GITHUB_CLIENT_ID') ?: '';
        $redirectUri = getenv('GITHUB_REDIRECT_URI') ?: 'http://localhost:8080/auth/github/callback';

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'read:user',
        ]);

        return new RedirectResponse("https://github.com/login/oauth/authorize?{$params}");
    }

    public function callback(Request $request): Response
    {
        $code = $request->query->get('code', '');
        if ($code === '') {
            return new RedirectResponse('/auth/github');
        }

        $clientId = getenv('GITHUB_CLIENT_ID') ?: '';
        $clientSecret = getenv('GITHUB_CLIENT_SECRET') ?: '';

        // Exchange code for access token
        $tokenResponse = file_get_contents('https://github.com/login/oauth/access_token', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Accept: application/json\r\nContent-Type: application/json\r\n",
                'content' => json_encode([
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                ]),
            ],
        ]));

        if ($tokenResponse === false) {
            return new RedirectResponse('/auth/github');
        }

        $tokenData = json_decode($tokenResponse, true);
        $accessToken = $tokenData['access_token'] ?? '';

        if ($accessToken === '') {
            return new RedirectResponse('/auth/github');
        }

        // Fetch GitHub user profile
        $userResponse = file_get_contents('https://api.github.com/user', false, stream_context_create([
            'http' => [
                'header' => "Authorization: Bearer {$accessToken}\r\nUser-Agent: me-vs-roadmap\r\nAccept: application/vnd.github.v3+json\r\n",
            ],
        ]));

        if ($userResponse === false) {
            return new RedirectResponse('/auth/github');
        }

        $githubUser = json_decode($userResponse, true);
        $username = $githubUser['login'] ?? '';

        if ($username === '') {
            return new RedirectResponse('/auth/github');
        }

        // Find or create Developer entity
        $developers = $this->repository->findBy(['github_username' => $username]);
        if ($developers !== []) {
            $developer = $developers[0];
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
        $session = $request->getSession();
        $session->set('developer_id', $developer->id());

        return new RedirectResponse("/profile/{$username}");
    }
}
