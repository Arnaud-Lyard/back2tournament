<?php

declare(strict_types=1);

namespace App\Seeding\Command;

use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Authentication\User\Domain\Entity\Password;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Entity\Username;
use App\Authentication\User\Domain\Security\PasswordHasherInterface;
use App\Blog\Article\Domain\Entity\Article;
use App\Blog\Article\Domain\Entity\ArticleId;
use App\Blog\Article\Domain\Entity\AuthorId;
use App\Blog\Article\Domain\Entity\CommentId;
use App\Blog\Category\Domain\Entity\Category;
use App\Blog\Shared\Domain\Entity\ValueObject\CategoryId;
use App\Competition\Competitor\Domain\Entity\Competitor;
use App\Competition\Competitor\Domain\Enum\CompetitorType;
use App\Competition\Fight\Domain\Entity\Fight;
use App\Competition\Fight\Domain\Entity\FightId;
use App\Competition\Fight\Domain\Entity\Result;
use App\Competition\Fight\Domain\Entity\ResultId;
use App\Competition\Fight\Domain\Enum\ResultStatus;
use App\Competition\Profile\Clan\Domain\Entity\Clan;
use App\Competition\Profile\Clan\Domain\Entity\ClanId;
use App\Competition\Profile\Clan\Domain\Entity\ClanMemberId;
use App\Competition\Profile\Clan\Domain\Entity\ClanName;
use App\Competition\Profile\Clan\Domain\Entity\ClanTag;
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\GameId as PlayerGameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Profile\Team\Domain\Entity\Team;
use App\Competition\Profile\Team\Domain\Entity\TeamId;
use App\Competition\Profile\Team\Domain\Entity\TeamName;
use App\Competition\Profile\Team\Domain\Entity\TeamPlayerId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
use App\Competition\Shared\Domain\Entity\ValueObject\TeamSize;
use App\Competition\Tournament\Domain\Entity\MatchupId;
use App\Competition\Tournament\Domain\Entity\OrganizerId;
use App\Competition\Tournament\Domain\Entity\Participant;
use App\Competition\Tournament\Domain\Entity\ParticipantId;
use App\Competition\Tournament\Domain\Entity\Tournament;
use App\Competition\Tournament\Domain\Entity\TournamentId;
use App\Competition\Tournament\Domain\Entity\TournamentName;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:seed',
    description: 'Seeds games, players, clans and their teams, fights, tournaments, articles and comments, sized so every paginated list spans several pages.',
)]
final class SeedCommand extends Command
{
    private const DEFAULT_EMAIL = 'demo@seed.local';

    private const DEFAULT_PASSWORD = 'Seed1234!';

    private const RIVAL_USERS = 20;

    private const PLAYERS_PER_GAME = 14;

    private const MINE_PENDING = 6;

    private const MINE_REPORTING = 2;

    private const MINE_FINISHED = 6;

    private const RIVAL_PENDING = 6;

    private const RIVAL_FINISHED = 6;

    private const ARTICLES = 25;

    private const COMMENTED_ARTICLES = 5;

    private const COMMENTS_PER_ARTICLE = 3;

    private const PAGE = 10;

    private const RANDOM_SEED = 20260922;

    /**
     * Each game and the formats it is played in, as players per side.
     */
    private const GAMES = [
        'Street Fighter 6' => [1],
        'Rocket League' => [1, 2, 3],
        'Valorant' => [1, 5],
    ];

    private const TOURNAMENT_REGISTERED = 5;

    private const TOURNAMENT_BRACKET = 4;

    private const CATEGORIES = [
        'esport-news' => 'Esport news',
        'tournaments' => 'Tournaments',
        'guides' => 'Guides',
        'patch-notes' => 'Patch notes',
    ];

    private const NICKNAMES = [
        'Blitz', 'Vortex', 'Nova', 'Echo', 'Raven',
        'Cinder', 'Quasar', 'Drift', 'Onyx', 'Pulse',
        'Rogue', 'Sable', 'Tempest', 'Umbra', 'Vertex',
        'Wraith', 'Zenith', 'Apex', 'Basilisk', 'Comet',
    ];

    private const HEADLINES = [
        'The season opener everyone underestimated',
        'How the underdog roster reached the final',
        'Five habits that separate ranked from tournament play',
        'Reading the patch notes like a coach',
        'The bracket that rewrote the standings',
    ];

    private const TABLES = [
        'tournament_matchup', 'tournament_participant', 'tournament',
        'result', 'fight', 'competitor', 'team_player', 'team', 'clan_member', 'clan',
        'player', 'comment', 'article', 'category', 'game', 'users',
    ];

    private EntityManagerInterface $entityManager;

    private PasswordHasherInterface $passwordHasher;

    private \DateTimeImmutable $now;

    private int $moment = 0;

    public function __construct(
        EntityManagerInterface $entityManager,
        PasswordHasherInterface $passwordHasher,
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Empty every table before seeding')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email of the account the challenges are opened for', self::DEFAULT_EMAIL)
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password of every seeded account', self::DEFAULT_PASSWORD)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getOption('email');
        $password = (string) $input->getOption('password');

        if (true === $input->getOption('reset')) {
            if ($input->isInteractive() && !$io->confirm(\sprintf('Empty every table of "%s" first?', $this->databaseName()), false)) {
                $io->warning('Nothing was written.');

                return Command::FAILURE;
            }

            $this->truncate();
            $io->text('Tables emptied.');
        }

        mt_srand(self::RANDOM_SEED);
        $this->now = new \DateTimeImmutable('now');
        $this->moment = 0;

        $games = $this->seedGames();
        $categories = $this->seedCategories();
        $mine = $this->seedUser($email, 'demo', $password, ['ROLE_ADMIN']);
        $rivals = $this->seedRivals($password);

        $fights = 0;
        $clans = 0;
        $firstPlayers = [];
        foreach ($games as $index => $game) {
            [$me, $opponents] = $this->seedGamePlayers($game, $index, $mine, $rivals);
            $fights += $this->seedGamePlayground($game, $me, $opponents);
            [$teamClans, $teamFights] = $this->seedClans($game, $me, $opponents);
            $clans += $teamClans;
            $fights += $teamFights;

            if (0 === $index) {
                $firstPlayers = [$me, $opponents];
            }
        }

        $fights += $this->seedTournaments($games[0], $mine, ...$firstPlayers);

        $this->seedArticles($mine, $categories);
        $this->entityManager->flush();

        $challenges = \count($games) * (self::MINE_PENDING + self::MINE_REPORTING);
        $firstGame = (string) $games[0]->getId();

        $io->success('Database seeded.');
        $io->definitionList(
            ['Sign in' => \sprintf('%s / %s', $email, $password)],
            ['Games' => \count($games)],
            ['Categories' => \count($categories)],
            ['Users' => 1 + \count($rivals)],
            ['Players' => \count($games) * (1 + self::PLAYERS_PER_GAME)],
            ['Clans' => $clans],
            ['Fights' => $fights],
            ['Tournaments' => 2],
            ['Articles' => self::ARTICLES],
            ['Comments' => self::COMMENTED_ARTICLES * self::COMMENTS_PER_ARTICLE],
        );
        $io->listing([
            \sprintf('GET /api/results/users/fights — at least %d waiting, %d pages', $challenges, $this->pages($challenges)),
            \sprintf('GET /api/players/%s/games — %d pages', $firstGame, $this->pages(1 + self::PLAYERS_PER_GAME)),
            \sprintf('GET /api/games/%s/clans', $firstGame),
            'GET /api/tournaments/ — one upcoming, one ongoing',
            \sprintf('GET /api/articles/ — %d pages', $this->pages(self::ARTICLES)),
        ]);

        return Command::SUCCESS;
    }

    /**
     * @return list<Game>
     */
    private function seedGames(): array
    {
        $games = [];
        foreach (self::GAMES as $title => $sizes) {
            $teamSizes = array_map(static fn (int $size): TeamSize => new TeamSize($size), $sizes);

            $existing = $this->entityManager->getRepository(Game::class)->findOneBy(['title' => $title]);
            if ($existing instanceof Game) {
                $games[] = Game::update($existing, null, $teamSizes);

                continue;
            }

            $game = Game::create(new GameId(Uuid::v4()->toString()), $title, $teamSizes);
            $this->entityManager->persist($game);
            $games[] = $game;
        }

        $this->entityManager->flush();

        return $games;
    }

    /**
     * @return list<Category>
     */
    private function seedCategories(): array
    {
        $categories = [];
        foreach (self::CATEGORIES as $slug => $name) {
            $existing = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);
            if ($existing instanceof Category) {
                $categories[] = $existing;

                continue;
            }

            $category = Category::create(new CategoryId(Uuid::v4()->toString()), $name, (string) $slug);
            $this->entityManager->persist($category);
            $categories[] = $category;
        }

        $this->entityManager->flush();

        return $categories;
    }

    /**
     * @return list<User>
     */
    private function seedRivals(string $password): array
    {
        $rivals = [];
        for ($index = 0; $index < self::RIVAL_USERS; ++$index) {
            $name = \sprintf('rival%02d', $index + 1);
            $rivals[] = $this->seedUser($name.'@seed.local', $name, $password, []);
        }

        return $rivals;
    }

    /**
     * @param list<string> $roles
     */
    private function seedUser(string $email, string $username, string $password, array $roles): User
    {
        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing instanceof User) {
            return $existing;
        }

        $user = User::registerUser(
            new Email($email),
            new Username($username),
            $roles,
            new Password($password),
            new Locale('fr'),
        );
        $user->setPassword($this->passwordHasher->hash($user, $password));
        $user->setVerified(true);
        $user->setVerificationToken(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * The demo account's profile in the game, and its rivals'.
     *
     * @param list<User> $rivals
     *
     * @return array{Player, list<Player>}
     */
    private function seedGamePlayers(Game $game, int $gameIndex, User $mine, array $rivals): array
    {
        $gameId = (string) $game->getId();

        $me = $this->seedPlayer($mine, $gameId, \sprintf('Demo#%04d', 1000 + $gameIndex));

        $opponents = [];
        for ($slot = 0; $slot < self::PLAYERS_PER_GAME; ++$slot) {
            $rival = $rivals[($gameIndex * 7 + $slot) % \count($rivals)];
            $nickname = self::NICKNAMES[($gameIndex * 7 + $slot) % \count(self::NICKNAMES)];

            $opponents[] = $this->seedPlayer(
                $rival,
                $gameId,
                \sprintf('%s#%04d', $nickname, 1000 + $gameIndex * 100 + $slot),
            );
        }

        return [$me, $opponents];
    }

    /**
     * @param list<Player> $opponents
     */
    private function seedGamePlayground(Game $game, Player $mePlayer, array $opponents): int
    {
        $gameId = (string) $game->getId();

        $me = $this->enlist(CompetitorType::PLAYER, (string) $mePlayer->getId());
        $opponents = array_map(fn (Player $player): CompetitorId => $this->enlist(CompetitorType::PLAYER, (string) $player->getId()), $opponents);

        $fights = 0;

        foreach ($this->states(self::MINE_PENDING, self::MINE_REPORTING, self::MINE_FINISHED) as $slot => $state) {
            $this->seedFight($me, $opponents[$slot], $gameId, 1, $state);
            ++$fights;
        }

        foreach ($this->states(self::RIVAL_PENDING, 0, self::RIVAL_FINISHED) as $slot => $state) {
            $left = $slot < self::RIVAL_PENDING ? 2 * $slot : 2 * ($slot - self::RIVAL_PENDING) + 1;

            $this->seedFight($opponents[$left], $opponents[$left + 1], $gameId, 1, $state);
            ++$fights;
        }

        $this->entityManager->flush();

        return $fights;
    }

    /**
     * Two clans per game: the demo account leads one, a rival the other. Each
     * fields a team in every format of the game above 1v1, and the two teams
     * of a format meet in a fight the rivals declared, for the demo to confirm.
     *
     * @param list<Player> $opponents
     *
     * @return array{int, int} how many clans and team fights were seeded
     */
    private function seedClans(Game $game, Player $me, array $opponents): array
    {
        $gameId = (string) $game->getId();
        $largest = max($game->getTeamSizes());
        $squad = max(3, $largest);

        if (null !== $this->entityManager->getRepository(Clan::class)->findOneBy(['game' => $gameId, 'tag' => 'DMO'])) {
            return [0, 0];
        }

        $mine = $this->seedClan($game, 'Demo Squad', 'DMO', $me, \array_slice($opponents, 0, $squad - 1));
        $theirs = $this->seedClan($game, 'Rival Crew', 'RIV', $opponents[$squad], \array_slice($opponents, $squad + 1, $squad - 1));

        // One invitation left for the demo account to see pending.
        $invited = $opponents[\count($opponents) - 1];
        $this->entityManager->persist(Clan::invite($mine, new ClanMemberId(Uuid::v4()->toString()), $invited->getId()));

        $fights = 0;
        foreach ($game->getTeamSizes() as $size) {
            if (1 === $size) {
                continue;
            }

            $myTeam = $this->seedTeam($mine, \sprintf('Demo %1$dv%1$d', $size), array_merge([$me], \array_slice($opponents, 0, $size - 1)));
            $theirTeam = $this->seedTeam($theirs, \sprintf('Rival %1$dv%1$d', $size), array_merge([$opponents[$squad]], \array_slice($opponents, $squad + 1, $size - 1)));

            // The rivals declared: the demo account, leading its team, confirms.
            $this->seedFight(
                $this->enlist(CompetitorType::TEAM, (string) $theirTeam->getId()),
                $this->enlist(CompetitorType::TEAM, (string) $myTeam->getId()),
                $gameId,
                $size,
                ResultStatus::REPORTING,
            );
            ++$fights;
        }

        $this->entityManager->flush();

        return [2, $fights];
    }

    /**
     * @param list<Player> $members
     */
    private function seedClan(Game $game, string $name, string $tag, Player $leader, array $members): Clan
    {
        $clan = Clan::create(new ClanId(Uuid::v4()->toString()), new ClanName($name), new ClanTag($tag), $game->getId(), $leader->getId());
        $this->entityManager->persist($clan);
        $this->entityManager->persist(Clan::createLeaderMembership($clan, new ClanMemberId(Uuid::v4()->toString())));

        foreach ($members as $member) {
            $membership = Clan::invite($clan, new ClanMemberId(Uuid::v4()->toString()), $member->getId());
            $this->entityManager->persist(Clan::join($clan, $membership));
        }

        return $clan;
    }

    /**
     * @param list<Player> $lineup the leader first
     */
    private function seedTeam(Clan $clan, string $name, array $lineup): Team
    {
        $team = Team::create(
            new TeamId(Uuid::v4()->toString()),
            new TeamName($name),
            $clan->getId(),
            $clan->getGame(),
            new TeamSize(\count($lineup)),
            $lineup[0]->getId(),
            array_map(static fn (Player $player): PlayerId => $player->getId(), $lineup),
        );
        $this->entityManager->persist($team);

        foreach ($lineup as $player) {
            $this->entityManager->persist(Team::createTeamPlayer($team, new TeamPlayerId(Uuid::v4()->toString()), $player->getId()));
        }

        $this->entityManager->flush();

        return $team;
    }

    /**
     * Two 1v1 tournaments organized by the demo account: one open for
     * registrations, one whose bracket is drawn and first round under way.
     *
     * @param list<Player> $opponents
     *
     * @return int how many fights the bracket opened
     */
    private function seedTournaments(Game $game, User $organizer, Player $me, array $opponents): int
    {
        if (null !== $this->entityManager->getRepository(Tournament::class)->findOneBy(['name' => 'Seed Cup'])) {
            return 0;
        }

        $players = array_merge([$me], $opponents);

        $this->seedTournament($game, $organizer, 'Seed Cup', \array_slice($players, 0, self::TOURNAMENT_REGISTERED));

        $masters = $this->seedTournament($game, $organizer, 'Seed Masters', \array_slice($players, 0, self::TOURNAMENT_BRACKET));
        $participants = $this->entityManager->getRepository(Participant::class)->findBy(['tournament' => (string) $masters->getId()]);

        $matchupIds = [];
        for ($count = Tournament::bracketSize(\count($participants)) - 1; $count > 0; --$count) {
            $matchupIds[] = new MatchupId(Uuid::v4()->toString());
        }

        $bracket = Tournament::start($masters, $participants, $matchupIds);

        $fights = 0;
        foreach (Tournament::readyForFight($bracket) as $matchup) {
            $fight = $this->seedFight(
                $matchup->getCompetitorOne(),
                $matchup->getCompetitorTwo(),
                (string) $game->getId(),
                1,
                ResultStatus::PENDING,
                $masters->getId(),
            );
            Tournament::attachFight($masters, $matchup, $fight->getId());
            ++$fights;
        }

        foreach ($bracket as $matchup) {
            $this->entityManager->persist($matchup);
        }
        $this->entityManager->flush();

        return $fights;
    }

    /**
     * @param list<Player> $players registered in this order
     */
    private function seedTournament(Game $game, User $organizer, string $name, array $players): Tournament
    {
        $tournament = Tournament::create(
            new TournamentId(Uuid::v4()->toString()),
            new TournamentName($name),
            $game->getId(),
            new TeamSize(1),
            8,
            new OrganizerId((string) $organizer->getId()),
            $this->now->modify('+3 days'),
        );
        $this->entityManager->persist($tournament);

        foreach ($players as $registered => $player) {
            $this->entityManager->persist(Tournament::register(
                $tournament,
                new ParticipantId(Uuid::v4()->toString()),
                $this->enlist(CompetitorType::PLAYER, (string) $player->getId()),
                $registered,
            ));
        }

        $this->entityManager->flush();

        return $tournament;
    }

    /**
     * @return list<ResultStatus>
     */
    private function states(int $pending, int $reporting, int $finished): array
    {
        return array_merge(
            array_fill(0, $pending, ResultStatus::PENDING),
            array_fill(0, $reporting, ResultStatus::REPORTING),
            array_fill(0, $finished, ResultStatus::WIN),
        );
    }

    private function seedPlayer(User $user, string $gameId, string $battletag): Player
    {
        $userId = (string) $user->getId();

        $player = $this->entityManager->getRepository(Player::class)->findOneBy(['user' => $userId, 'game' => $gameId]);
        if (!$player instanceof Player) {
            $player = Player::create(
                new PlayerId(Uuid::v4()->toString()),
                $battletag,
                new PlayerGameId($gameId),
                new UserId($userId),
            );
            $this->entityManager->persist($player);
            $this->entityManager->flush();
        }

        return $player;
    }

    private function enlist(CompetitorType $type, string $reference): CompetitorId
    {
        $competitor = $this->entityManager->getRepository(Competitor::class)->findOneBy([
            'type' => $type,
            'reference' => $reference,
        ]);

        if (!$competitor instanceof Competitor) {
            $competitor = Competitor::create(new CompetitorId(Uuid::v4()->toString()), $type, $reference);
            $this->entityManager->persist($competitor);
            $this->entityManager->flush();
        }

        return new CompetitorId((string) $competitor->getId());
    }

    /**
     * $one declares whatever is past pending.
     */
    private function seedFight(
        CompetitorId $one,
        CompetitorId $two,
        string $gameId,
        int $teamSize,
        ResultStatus $state,
        ?TournamentId $tournamentId = null,
    ): Fight {
        $at = $this->nextMoment();

        $fight = Fight::create(
            new FightId(Uuid::v4()->toString()),
            $one,
            $two,
            new GameId($gameId),
            new TeamSize($teamSize),
            $tournamentId,
        );
        $fight->setCreatedAt($at);
        $fight->setUpdatedAt($at);

        $resultOne = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), $one);
        $resultTwo = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), $two);

        if (ResultStatus::PENDING !== $state) {
            $scoreOne = mt_rand(0, 3);
            $scoreTwo = mt_rand(0, 3);
            if (null !== $tournamentId && $scoreOne === $scoreTwo) {
                ++$scoreOne;
            }

            $outcomeOne = $this->outcome($scoreOne, $scoreTwo);
            $outcomeTwo = $this->outcome($scoreTwo, $scoreOne);

            Fight::updateResult($fight, $resultOne, $scoreOne, $one, $outcomeOne);
            Fight::updateResult($fight, $resultTwo, $scoreTwo, $two, $outcomeTwo);
            $fight->setDeclaredBy($one);

            if (ResultStatus::REPORTING !== $state) {
                Fight::confirmResult($fight, $resultOne, $outcomeOne);
                Fight::confirmResult($fight, $resultTwo, $outcomeTwo);
            }
        }

        $this->stamp($resultOne, $at);
        $this->stamp($resultTwo, $at);

        $this->entityManager->persist($fight);
        $this->entityManager->persist($resultOne);
        $this->entityManager->persist($resultTwo);

        return $fight;
    }

    private function outcome(int $score, int $against): ResultStatus
    {
        return match (true) {
            $score > $against => ResultStatus::WIN,
            $score < $against => ResultStatus::LOSS,
            default => ResultStatus::DRAW,
        };
    }

    private function stamp(Result $result, \DateTimeImmutable $at): void
    {
        $result->setCreatedAt($at);
        $result->setUpdatedAt($at);
    }

    /**
     * @param list<Category> $categories
     */
    private function seedArticles(User $author, array $categories): void
    {
        $authorId = new AuthorId((string) $author->getId());

        for ($index = 0; $index < self::ARTICLES; ++$index) {
            $headline = self::HEADLINES[$index % \count(self::HEADLINES)];
            $category = $categories[$index % \count($categories)];
            $at = $this->nextMoment();

            $article = Article::create(
                new ArticleId(Uuid::v4()->toString()),
                \sprintf('%s (%02d)', $headline, $index + 1),
                $this->body($headline),
                $authorId,
                new CategoryId($category->getId()),
            );
            $article->setCreatedAt($at);
            $article->setUpdatedAt($at);
            $this->entityManager->persist($article);

            if ($index >= self::COMMENTED_ARTICLES) {
                continue;
            }

            for ($reply = 0; $reply < self::COMMENTS_PER_ARTICLE; ++$reply) {
                $comment = Article::createComment(
                    $article,
                    new CommentId(Uuid::v4()->toString()),
                    \sprintf('Seeded comment %d on "%s".', $reply + 1, $headline),
                );
                $comment->setCreatedAt($at);
                $comment->setUpdatedAt($at);
                $this->entityManager->persist($comment);
            }
        }

        $this->entityManager->flush();
    }

    private function body(string $headline): string
    {
        return implode("\n\n", [
            $headline.'.',
            'Seeded content, long enough for an excerpt to be cut out of it and for a list row to wrap onto a second line.',
            'The numbers here mean nothing: this article exists so the blog list holds more rows than one page shows.',
        ]);
    }

    private function nextMoment(): \DateTimeImmutable
    {
        return $this->now->modify(\sprintf('-%d minutes', 37 * $this->moment++));
    }

    private function pages(int $total): int
    {
        return (int) ceil($total / self::PAGE);
    }

    private function truncate(): void
    {
        $tables = implode(', ', array_map(static fn (string $table): string => '"'.$table.'"', self::TABLES));

        $this->entityManager->getConnection()->executeStatement(
            \sprintf('TRUNCATE TABLE %s RESTART IDENTITY CASCADE', $tables),
        );
    }

    private function databaseName(): string
    {
        return $this->entityManager->getConnection()->getDatabase() ?? 'unknown';
    }
}
