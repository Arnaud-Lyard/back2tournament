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
use App\Competition\Profile\Game\Domain\Entity\Game;
use App\Competition\Profile\Game\Domain\Entity\GameId;
use App\Competition\Profile\Player\Domain\Entity\GameId as PlayerGameId;
use App\Competition\Profile\Player\Domain\Entity\Player;
use App\Competition\Profile\Player\Domain\Entity\PlayerId;
use App\Competition\Profile\Player\Domain\Entity\UserId;
use App\Competition\Shared\Domain\Entity\ValueObject\CompetitorId;
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
    description: 'Seeds games, players, fights, articles and comments, sized so every paginated list spans several pages.',
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

    private const GAMES = ['Street Fighter 6', 'Rocket League', 'Valorant'];

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
        'result', 'fight', 'competitor', 'team_player', 'team',
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
        foreach ($games as $index => $game) {
            $fights += $this->seedGamePlayground($game, $index, $mine, $rivals);
        }

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
            ['Fights' => $fights],
            ['Articles' => self::ARTICLES],
            ['Comments' => self::COMMENTED_ARTICLES * self::COMMENTS_PER_ARTICLE],
        );
        $io->listing([
            \sprintf('GET /api/challenges/ — %d waiting, %d pages', $challenges, $this->pages($challenges)),
            \sprintf('GET /api/games/%s/fights?status=pending — %d pages', $firstGame, $this->pages(self::MINE_PENDING + self::MINE_REPORTING + self::RIVAL_PENDING)),
            \sprintf('GET /api/games/%s/fights?status=finished — %d pages', $firstGame, $this->pages(self::MINE_FINISHED + self::RIVAL_FINISHED)),
            \sprintf('GET /api/games/%s/players — %d pages', $firstGame, $this->pages(1 + self::PLAYERS_PER_GAME)),
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
        foreach (self::GAMES as $title) {
            $existing = $this->entityManager->getRepository(Game::class)->findOneBy(['title' => $title]);
            if ($existing instanceof Game) {
                $games[] = $existing;

                continue;
            }

            $game = Game::create(new GameId(Uuid::v4()->toString()), $title);
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
     * @param list<User> $rivals
     */
    private function seedGamePlayground(Game $game, int $gameIndex, User $mine, array $rivals): int
    {
        $gameId = (string) $game->getId();

        $me = $this->seedCompetitor($mine, $gameId, \sprintf('Demo#%04d', 1000 + $gameIndex));

        $opponents = [];
        for ($slot = 0; $slot < self::PLAYERS_PER_GAME; ++$slot) {
            $rival = $rivals[($gameIndex * 7 + $slot) % \count($rivals)];
            $nickname = self::NICKNAMES[($gameIndex * 7 + $slot) % \count(self::NICKNAMES)];

            $opponents[] = $this->seedCompetitor(
                $rival,
                $gameId,
                \sprintf('%s#%04d', $nickname, 1000 + $gameIndex * 100 + $slot),
            );
        }

        $fights = 0;

        foreach ($this->states(self::MINE_PENDING, self::MINE_REPORTING, self::MINE_FINISHED) as $slot => $state) {
            $this->seedFight($me, $opponents[$slot], $state);
            ++$fights;
        }

        foreach ($this->states(self::RIVAL_PENDING, 0, self::RIVAL_FINISHED) as $slot => $state) {
            $left = $slot < self::RIVAL_PENDING ? 2 * $slot : 2 * ($slot - self::RIVAL_PENDING) + 1;

            $this->seedFight($opponents[$left], $opponents[$left + 1], $state);
            ++$fights;
        }

        $this->entityManager->flush();

        return $fights;
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

    private function seedCompetitor(User $user, string $gameId, string $battletag): CompetitorId
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

        $playerId = (string) $player->getId();

        $competitor = $this->entityManager->getRepository(Competitor::class)->findOneBy([
            'type' => CompetitorType::PLAYER,
            'reference' => $playerId,
        ]);

        if (!$competitor instanceof Competitor) {
            $competitor = Competitor::create(new CompetitorId(Uuid::v4()->toString()), CompetitorType::PLAYER, $playerId);
            $this->entityManager->persist($competitor);
            $this->entityManager->flush();
        }

        return new CompetitorId((string) $competitor->getId());
    }

    private function seedFight(CompetitorId $one, CompetitorId $two, ResultStatus $state): void
    {
        $at = $this->nextMoment();

        $fight = Fight::create(new FightId(Uuid::v4()->toString()), $one, $two);
        $fight->setCreatedAt($at);
        $fight->setUpdatedAt($at);

        $resultOne = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), $one);
        $resultTwo = Fight::createResult($fight, new ResultId(Uuid::v4()->toString()), $two);

        if (ResultStatus::PENDING !== $state) {
            $scoreOne = mt_rand(0, 3);
            $scoreTwo = mt_rand(0, 3);

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
