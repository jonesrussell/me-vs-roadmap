<?php

declare(strict_types=1);

namespace App\Command;

use App\Seed\RoadmapSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Waaseyaa\Entity\Repository\EntityRepositoryInterface;

#[AsCommand(name: 'app:seed-roadmaps', description: 'Seed the 3 MVP roadmaps with skills and detection rules')]
final class SeedRoadmapsCommand extends Command
{
    public function __construct(private readonly EntityRepositoryInterface $repository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $seeder = new RoadmapSeeder($this->repository);
        $seeder->seed();
        $output->writeln('<info>Seeded 3 roadmaps: Backend, Frontend, DevOps</info>');

        return Command::SUCCESS;
    }
}
