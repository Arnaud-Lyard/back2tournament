<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Application\Command;

use App\Authentication\ApiToken\Application\Service\ApiTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:revoke',
    description: 'Revokes an API token: the next request the tool sends with it is refused.',
)]
final class RevokeApiTokenCommand extends Command
{
    private ApiTokenService $apiTokenService;

    public function __construct(ApiTokenService $apiTokenService)
    {
        $this->apiTokenService = $apiTokenService;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name the token was issued under, as app:api-token:list shows it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->apiTokenService->revoke($input->getArgument('name'));

        $io->success(\sprintf('The API token %s is revoked.', $input->getArgument('name')));

        return Command::SUCCESS;
    }
}
