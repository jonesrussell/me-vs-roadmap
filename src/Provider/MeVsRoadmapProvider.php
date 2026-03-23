<?php

declare(strict_types=1);

namespace App\Provider;

use App\Command\SeedRoadmapsCommand;
use App\Domain\GitHub\GitHubClient;
use App\Domain\GitHub\GitHubClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Waaseyaa\Database\DatabaseInterface;
use Waaseyaa\Entity\EntityTypeManager;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;
use Waaseyaa\Foundation\ServiceProvider\ServiceProvider;
use Waaseyaa\Routing\RouteBuilder;
use Waaseyaa\Routing\WaaseyaaRouter;

final class MeVsRoadmapProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(GitHubClientInterface::class, static function (): GitHubClient {
            $token = getenv('GITHUB_TOKEN') ?: null;

            return new GitHubClient(new GuzzleClient(), $token ?: null);
        });
    }

    public function routes(WaaseyaaRouter $router, ?EntityTypeManager $entityTypeManager = null): void
    {
        $router->addRoute('me_vs_roadmap.auth.redirect', RouteBuilder::create('/auth/github')
            ->controller('App\Controller\AuthController::redirect')
            ->methods('GET')
            ->allowAll()
            ->build());

        $router->addRoute('me_vs_roadmap.auth.callback', RouteBuilder::create('/auth/github/callback')
            ->controller('App\Controller\AuthController::callback')
            ->methods('GET')
            ->allowAll()
            ->build());

        $router->addRoute('me_vs_roadmap.scan.trigger', RouteBuilder::create('/scan')
            ->controller('App\Controller\ScanController::trigger')
            ->methods('POST')
            ->requireAuthentication()
            ->jsonApi()
            ->build());

        $router->addRoute('me_vs_roadmap.scan.status', RouteBuilder::create('/scan/{id}')
            ->controller('App\Controller\ScanController::status')
            ->methods('GET')
            ->requireAuthentication()
            ->requirement('id', '\d+')
            ->build());

        $router->addRoute('me_vs_roadmap.profile.view', RouteBuilder::create('/profile/{username}')
            ->controller('App\Controller\ProfileController::view')
            ->methods('GET')
            ->allowAll()
            ->build());
    }

    /**
     * @return list<\Symfony\Component\Console\Command\Command>
     */
    public function commands(
        EntityTypeManager $entityTypeManager,
        DatabaseInterface $database,
        EventDispatcherInterface $eventDispatcher,
    ): array {
        /** @var EntityRepositoryInterface $repository */
        $repository = $this->resolve(EntityRepositoryInterface::class);

        return [new SeedRoadmapsCommand($repository)];
    }
}
