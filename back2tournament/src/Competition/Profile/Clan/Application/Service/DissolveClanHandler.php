<?php

declare(strict_types=1);

namespace App\Competition\Profile\Clan\Application\Service;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Profile\Clan\Application\Model\DissolveClanCommand;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Repository\ClanMemberRepositoryInterface;
use App\Competition\Profile\Clan\Domain\Repository\ClanRepositoryInterface;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Repository\PlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Repository\TeamPlayerRepositoryInterface;
use App\Competition\Profile\Team\Domain\Repository\TeamRepositoryInterface;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class DissolveClanHandler
{
    private ClanRepositoryInterface $clanRepository;
    private ClanMemberRepositoryInterface $clanMemberRepository;
    private PlayerRepositoryInterface $playerRepository;
    private TeamRepositoryInterface $teamRepository;
    private TeamPlayerRepositoryInterface $teamPlayerRepository;
    private CompetitorRegistryProviderInterface $competitorRegistryProvider;
    private CurrentUserProviderInterface $currentUserProvider;
    private EventDispatcherInterface $eventDispatcher;
    private NormalizerInterface $serializer;

    public function __construct(
        ClanRepositoryInterface $clanRepository,
        ClanMemberRepositoryInterface $clanMemberRepository,
        PlayerRepositoryInterface $playerRepository,
        TeamRepositoryInterface $teamRepository,
        TeamPlayerRepositoryInterface $teamPlayerRepository,
        CompetitorRegistryProviderInterface $competitorRegistryProvider,
        CurrentUserProviderInterface $currentUserProvider,
        EventDispatcherInterface $eventDispatcher,
        NormalizerInterface $serializer,
    ) {
        $this->clanRepository = $clanRepository;
        $this->clanMemberRepository = $clanMemberRepository;
        $this->playerRepository = $playerRepository;
        $this->teamRepository = $teamRepository;
        $this->teamPlayerRepository = $teamPlayerRepository;
        $this->competitorRegistryProvider = $competitorRegistryProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
    }

    public function __invoke(DissolveClanCommand $dissolveClanCommand): string
    {
        $clanId = new ClanId($dissolveClanCommand->getClanId());

        $clan = $this->clanRepository->findOneBy(['id' => $clanId->getValue(), 'dissolvedAt' => null]);
        if (!$clan instanceof Clan) {
            throw new NotFoundException('clan not found');
        }

        $leader = $this->playerRepository->findOneBy(['id' => $clan->getLeader()->getValue()]);
        if (!$leader instanceof Player
            || $leader->getUser()->getValue() !== (string) $this->currentUserProvider->getUser()->getId()) {
            throw new PermissionDeniedException('only the clan leader dissolves the clan');
        }

        $this->currentUserProvider->confirmPassword($dissolveClanCommand->getPassword());

        Clan::dissolve($clan);

        foreach ($this->clanMemberRepository->findBy(['clan' => $clanId->getValue()]) as $membership) {
            $this->clanMemberRepository->remove($membership);
        }

        foreach ($this->teamRepository->findBy(['clan' => $clanId->getValue()]) as $team) {
            if (!$this->competitorRegistryProvider->teamHasCompeted($team->getId()->getValue())) {
                $this->disband($team);
            }
        }

        $this->clanRepository->save($clan);

        foreach ($clan->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }

        return json_encode($this->serializer->normalize($clan), JSON_THROW_ON_ERROR);
    }

    private function disband(Team $team): void
    {
        foreach ($this->teamPlayerRepository->findBy(['team' => $team->getId()->getValue()]) as $teamPlayer) {
            $this->teamPlayerRepository->remove($teamPlayer);
        }

        Team::disband($team);
        $this->teamRepository->remove($team);

        foreach ($team->pullDomainEvents() as $domainEvent) {
            $this->eventDispatcher->dispatch($domainEvent);
        }
    }
}
