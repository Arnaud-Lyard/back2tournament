<?php

declare(strict_types=1);

namespace App\Authentication\ApiToken\Application\Command;

use App\Authentication\ApiToken\Application\Service\ApiTokenService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:api-token:list',
    description: 'Lists the API tokens issued to tools, when they expire and when they were last used.',
)]
final class ListApiTokensCommand extends Command
{
    private const DATE_FORMAT = 'Y-m-d H:i';

    private ApiTokenService $apiTokenService;

    public function __construct(ApiTokenService $apiTokenService)
    {
        $this->apiTokenService = $apiTokenService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $apiTokens = $this->apiTokenService->all();
        if ([] === $apiTokens) {
            $io->info('No API token is issued. Issue one with app:api-token:create.');

            return Command::SUCCESS;
        }

        $now = new \DateTimeImmutable('now');
        $rows = [];
        foreach ($apiTokens as $apiToken) {
            $expiresAt = $apiToken->getExpiresAt();
            $rows[] = [
                $apiToken->getName(),
                $apiToken->getCreatedAt()->format(self::DATE_FORMAT),
                match (true) {
                    null === $expiresAt => 'never',
                    $apiToken->isValidAt($now) => $expiresAt->format(self::DATE_FORMAT),
                    default => $expiresAt->format(self::DATE_FORMAT).' (expired)',
                },
                $apiToken->getLastUsedAt()?->format(self::DATE_FORMAT) ?? 'never',
            ];
        }

        $io->table(['Name', 'Issued', 'Expires', 'Last used'], $rows);

        return Command::SUCCESS;
    }
}
