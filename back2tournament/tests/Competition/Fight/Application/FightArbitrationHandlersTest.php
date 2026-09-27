<?php

declare(strict_types=1);

namespace App\Tests\Competition\Fight\Application;

use App\Authentication\User\Domain\Security\CurrentUserProviderInterface;
use App\Competition\Fight\Application\Model\ChangeFightStatusCommand;
use App\Competition\Fight\Application\Model\FindFightsQuery;
use App\Competition\Fight\Application\Service\ChangeFightStatusHandler;
use App\Competition\Fight\Application\Service\FindFightsHandler;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Entity\Score;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Fight\Domain\Event\FightSettledEvent;
use App\Competition\Fight\Domain\Repository\FightRepositoryInterface;
use App\Competition\Fight\Domain\Repository\ResultRepositoryInterface;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Provider\CompetitorRegistryProviderInterface;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\PermissionDeniedException;
use App\Shared\Exception\ValidationException;
use App\Shared\ValueObject\TeamSizeValueObject;
use App\Tests\Support\RepositoryStubs;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class FightArbitrationHandlersTest extends TestCase
{
    use RepositoryStubs;

    private const FIGHT_ID = '11111111-1111-4111-8111-111111111111';
    private const GAME_ID = '22222222-2222-4222-8222-222222222222';
    private const ONE = '33333333-3333-4333-8333-333333333333';
    private const TWO = '44444444-4444-4444-8444-444444444444';
    private const STRANGER = '55555555-5555-4555-8555-555555555555';
    private const RESULT_ONE = '66666666-6666-4666-8666-666666666666';
    private const RESULT_TWO = '77777777-7777-4777-8777-777777777777';

    /** @var list<object> */
    private array $saved = [];

    /** @var list<object> */
    private array $dispatched = [];

    public function test_an_administrator_settles_a_fight_in_dispute_on_the_scores_they_impose(): void
    {
        [$fight, $one, $two] = $this->declared();

        $read = $this->read($this->changeHandler($fight, $one, $two)(new ChangeFightStatusCommand(self::FIGHT_ID, 'finished', [self::ONE => 0, self::TWO => 2])));

        $this->assertSame([ResultStatus::LOSS, ResultStatus::WIN], [$one->getStatus(), $two->getStatus()]);
        $this->assertSame([$one, $two, $fight], $this->saved);
        $this->assertSame('finished', $read['status']);
        $this->assertTrue($read['arbitrated']);
        $this->assertSame(['value' => self::TWO], $read['winner']);
        $this->assertSame([['Alpha', 0], ['Bravo', 2]], array_map(static fn (array $side): array => [$side['name'], $side['score']], $read['sides']));

        // The bracket and the rankings hear of it as of a confirmation.
        $settled = array_values(array_filter($this->dispatched, static fn (object $event): bool => $event instanceof FightSettledEvent));
        $this->assertCount(1, $settled);
        $this->assertSame(self::TWO, $settled[0]->getWinner()?->getValue());
    }

    public function test_an_administrator_sets_a_declaration_aside(): void
    {
        [$fight, $one, $two] = $this->declared();

        $read = $this->read($this->changeHandler($fight, $one, $two)(new ChangeFightStatusCommand(self::FIGHT_ID, 'pending', null)));

        $this->assertSame([ResultStatus::PENDING, ResultStatus::PENDING], [$one->getStatus(), $two->getStatus()]);
        $this->assertSame(['pending', null, false], [$read['status'], $read['declaredBy'], $read['arbitrated']]);
        $this->assertSame([], array_filter($this->dispatched, static fn (object $event): bool => $event instanceof FightSettledEvent));
    }

    public function test_only_an_administrator_settles_a_dispute(): void
    {
        [$fight, $one, $two] = $this->declared();

        try {
            $this->changeHandler($fight, $one, $two, admin: false)(new ChangeFightStatusCommand(self::FIGHT_ID, 'finished', [self::ONE => 0, self::TWO => 2]));
            $this->fail('a player settled a dispute');
        } catch (PermissionDeniedException) {
        }

        $this->assertSame([], $this->saved);
        $this->assertSame(ResultStatus::REPORTING, $one->getStatus());
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>|null}>
     */
    public static function invalidChanges(): iterable
    {
        yield 'an unknown status' => ['reporting', null];
        yield 'no scores' => ['finished', null];
        yield 'one score only' => ['finished', [self::ONE => 1]];
        yield 'a score that is no whole number' => ['finished', [self::ONE => 1, self::TWO => '2']];
        yield 'a negative score' => ['finished', [self::ONE => -1, self::TWO => 2]];
        yield 'a side that is not in the fight' => ['finished', [self::ONE => 1, self::STRANGER => 2]];
    }

    /**
     * @param array<string, mixed>|null $scores
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidChanges')]
    public function test_an_invalid_change_is_refused_and_nothing_is_saved(string $status, ?array $scores): void
    {
        [$fight, $one, $two] = $this->declared();

        try {
            $this->changeHandler($fight, $one, $two)(new ChangeFightStatusCommand(self::FIGHT_ID, $status, $scores));
            $this->fail('an invalid change went through');
        } catch (ValidationException) {
        }

        $this->assertSame([], $this->saved);
    }

    public function test_a_settled_fight_stays_as_it_is(): void
    {
        [$fight, $one, $two] = $this->declared();
        Fight::arbitrate($fight, $one, new Score(1), $two, new Score(0));

        $this->expectException(ConflictException::class);

        $this->changeHandler($fight, $one, $two)(new ChangeFightStatusCommand(self::FIGHT_ID, 'pending', null));
    }

    public function test_an_unknown_fight_is_not_found(): void
    {
        $this->expectException(NotFoundException::class);

        $this->changeHandler(null, null, null)(new ChangeFightStatusCommand(self::FIGHT_ID, 'pending', null));
    }

    public function test_the_disputes_are_the_fights_waiting_for_a_confirmation(): void
    {
        [$fight, $one, $two] = $this->declared();

        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->once())->method('findPage')
            ->with([ResultStatus::REPORTING], self::GAME_ID, null, null, 20, 0)
            ->willReturn([$fight]);
        $fightRepository->method('countPage')->willReturn(1);

        $page = $this->read($this->findHandler($fightRepository, [$one, $two])(new FindFightsQuery('reporting', self::GAME_ID, null, 1, 20)));

        $this->assertSame([1, 1, 1], [$page['total'], $page['pages'], \count($page['items'])]);
        $item = $page['items'][0];
        $this->assertSame(['reporting', ['value' => self::ONE], false, null], [$item['status'], $item['declaredBy'], $item['arbitrated'], $item['mySide']]);
        $this->assertSame([['Alpha', 3, 'win'], ['Bravo', 1, 'loss']], array_map(static fn (array $side): array => [$side['name'], $side['score'], $side['reportedStatus']], $item['sides']));
    }

    public function test_a_name_finds_the_fights_of_whoever_bears_it(): void
    {
        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->once())->method('findPage')
            ->with(null, null, [self::ONE], null, 20, 0)
            ->willReturn([]);

        $this->findHandler($fightRepository, [], named: [self::ONE])(new FindFightsQuery('', '', ' alph ', 1, 20));
    }

    public function test_a_fight_id_finds_that_fight(): void
    {
        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->once())->method('findPage')
            ->with(null, null, [], self::FIGHT_ID, 20, 0)
            ->willReturn([]);

        $this->findHandler($fightRepository, [])(new FindFightsQuery('all', null, strtoupper(self::FIGHT_ID), 1, 20));
    }

    public function test_a_name_nobody_bears_finds_nothing_without_reading_the_fights(): void
    {
        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->never())->method('findPage');

        $page = $this->read($this->findHandler($fightRepository, [])(new FindFightsQuery('all', null, 'nobody', 1, 20)));

        $this->assertSame([[], 0, 0], [$page['items'], $page['total'], $page['pages']]);
    }

    public function test_only_an_administrator_lists_every_fight(): void
    {
        $fightRepository = $this->createMock(FightRepositoryInterface::class);
        $fightRepository->expects($this->never())->method('findPage');

        $this->expectException(PermissionDeniedException::class);

        $this->findHandler($fightRepository, [], admin: false)(new FindFightsQuery('reporting', null, null, 1, 20));
    }

    public function test_an_unknown_status_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->findHandler($this->createStub(FightRepositoryInterface::class), [])(new FindFightsQuery('disputed', null, null, 1, 20));
    }

    /**
     * A 1v1 fight between ONE and TWO, ONE having declared 3 to 1.
     *
     * @return array{Fight, Result, Result}
     */
    private function declared(): array
    {
        $fight = Fight::create(new FightId(self::FIGHT_ID), new CompetitorId(self::ONE), new CompetitorId(self::TWO), new GameId(self::GAME_ID), new TeamSizeValueObject(1));
        $one = Fight::createResult($fight, new ResultId(self::RESULT_ONE), new CompetitorId(self::ONE));
        $two = Fight::createResult($fight, new ResultId(self::RESULT_TWO), new CompetitorId(self::TWO));
        Fight::declareOutcome($fight, new CompetitorId(self::ONE), $one, new Score(3), $two, new Score(1));
        $fight->pullDomainEvents();

        return [$fight, $one, $two];
    }

    private function changeHandler(?Fight $fight, ?Result $one, ?Result $two, bool $admin = true): ChangeFightStatusHandler
    {
        $fightRepository = $this->repositoryStub(FightRepositoryInterface::class, null === $fight ? [] : [$fight]);
        $fightRepository->method('save')->willReturnCallback(function (Fight $fight): void {
            $this->saved[] = $fight;
        });
        $resultRepository = $this->repositoryStub(ResultRepositoryInterface::class, array_values(array_filter([$one, $two])));
        $resultRepository->method('save')->willReturnCallback(function (Result $result): void {
            $this->saved[] = $result;
        });

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->dispatched[] = $event;

            return $event;
        });

        return new ChangeFightStatusHandler($fightRepository, $resultRepository, $this->registry([]), $this->currentUser($admin), $eventDispatcher);
    }

    /**
     * @param list<Result> $results
     * @param list<string> $named
     */
    private function findHandler(FightRepositoryInterface $fightRepository, array $results, array $named = [], bool $admin = true): FindFightsHandler
    {
        return new FindFightsHandler(
            $fightRepository,
            $this->repositoryStub(ResultRepositoryInterface::class, $results),
            $this->registry($named),
            $this->currentUser($admin),
        );
    }

    /**
     * @param list<string> $named
     */
    private function registry(array $named): CompetitorRegistryProviderInterface
    {
        $registry = $this->createStub(CompetitorRegistryProviderInterface::class);
        $registry->method('named')->willReturn($named);
        $registry->method('describe')->willReturn([
            self::ONE => ['type' => 'player', 'reference' => self::STRANGER, 'name' => 'Alpha'],
            self::TWO => ['type' => 'player', 'reference' => self::STRANGER, 'name' => 'Bravo'],
        ]);

        return $registry;
    }

    private function currentUser(bool $admin): CurrentUserProviderInterface
    {
        $currentUserProvider = $this->createStub(CurrentUserProviderInterface::class);
        $currentUserProvider->method('isGranted')->willReturnCallback(static fn (string $role): bool => $admin && 'ROLE_ADMIN' === $role);

        return $currentUserProvider;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
