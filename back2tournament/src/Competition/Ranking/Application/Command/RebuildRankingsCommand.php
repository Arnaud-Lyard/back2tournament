<?php

declare(strict_types=1);

namespace App\Competition\Ranking\Application\Command;

use App\Competition\Ranking\Application\Service\RebuildRankingsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rankings:rebuild',
    description: 'Counts every settled fight again into the Elo rankings of the players and the clans.',
)]
final class RebuildRankingsCommand extends Command
{
    private RebuildRankingsService $rebuildRankingsService;

    public function __construct(RebuildRankingsService $rebuildRankingsService)
    {
        $this->rebuildRankingsService = $rebuildRankingsService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(<<<'HELP'
            Empties the rankings, then replays every settled fight in the order it was settled.
            Run it once after the migration that creates the rankings, so that the fights
            settled before it count; running it again gives the same rankings.
            HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rebuilt = $this->rebuildRankingsService->rebuild();

        $io->success(\sprintf(
            '%d settled fights replayed: %d player profiles and clans are ranked.',
            $rebuilt['fights'],
            $rebuilt['ratings'],
        ));

        return Command::SUCCESS;
    }
}
