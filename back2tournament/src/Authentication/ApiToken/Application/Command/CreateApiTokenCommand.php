<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Application\Command;

use App\Authentication\ApiToken\Application\Service\ApiTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:create',
    description: 'Issues the API token a tool, such as Hermes, writes drafts with through the /api/bot routes.',
)]
final class CreateApiTokenCommand extends Command
{
    private ApiTokenService $apiTokenService;

    public function __construct(ApiTokenService $apiTokenService)
    {
        $this->apiTokenService = $apiTokenService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the tool the token is for, such as hermes')
            ->addOption('days', null, InputOption::VALUE_REQUIRED, 'How many days the token lasts; without it, the token lasts until it is revoked')
            ->setHelp(<<<'HELP'
                Prints the token once, on the last line: store it in the tool, nothing can show it
                again, as only its hash is kept. The tool sends it in an `Authorization: Bearer`
                header to the routes under /api/bot, and they are all it opens. With --quiet, the
                token is the only line printed. Revoke it with app:api-token:revoke.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = $input->getOption('days');
        if (null !== $days && !ctype_digit($days)) {
            $io->error('--days takes a whole number of days.');

            return Command::INVALID;
        }

        $secret = $this->apiTokenService->issue($input->getArgument('name'), null === $days ? null : (int) $days);

        $io->success('API token issued. It is shown this once: store it in the tool now.');
        $output->writeln($secret, OutputInterface::VERBOSITY_QUIET);

        return Command::SUCCESS;
    }
}
