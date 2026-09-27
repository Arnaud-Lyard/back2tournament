# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project

**back2tournament** — a Symfony backend/API for an e‑sports competition platform.
Bootstrapped from the [`dunglas/symfony-docker`](https://github.com/dunglas/symfony-docker)
template (FrankenPHP + Caddy + Mercure). The code is organized with Domain‑Driven
Design and a strict hexagonal / clean architecture: independent **bounded contexts**,
each split into **Domain / Application / Infrastructure** layers, with the allowed
dependencies enforced by **Deptrac**.

## Tech stack

- PHP **8.5**, Symfony **7.4 LTS**
- Doctrine ORM 3 + Migrations — **XML mapping only**
- PostgreSQL 16
- PHPUnit 13
- Deptrac 4
- Docker Compose

## Running commands

Everything runs inside the `php` container. The `makefile` wraps the common tasks:

```bash
make up             # start containers (detached)
make down           # stop containers
make sh             # shell into the php container
make test           # run PHPUnit  (pass c="--filter Foo" for extra args)
make ddd            # run Deptrac architecture check
make sf c=<cmd>     # run bin/console <cmd>
make cc             # clear cache
make composer c=<>  # run composer
make user           # seed a dev user via app:create-user
```

## Architecture

### Bounded contexts (`src/`)

- `Shared/` — shared kernel. **Must stay dependency-free** (no Symfony, no Doctrine).
  Holds `AggregateRoot`, `DomainEventInterface`, and base value objects
  (`AggregateRootId`, `EmailValueObject`, `PasswordValueObject`, …).
- `Authentication/User/` — users, roles, password hashing, Symfony Security integration.
  Publishes `Shared/Provider/CurrentUserProviderInterface`, the only sanctioned way for
  any context to learn who the caller is.
- `Blog/Article/`, `Blog/Category/`, `Blog/Shared/` — blog content and its taxonomy. An
  `Article` is a `draft` until an editor publishes it: whoever publishes it becomes its
  `author` — not whoever wrote it — and taking it back to draft clears the author. Only
  a published article is public and takes comments; a comment records who wrote it.
  An article is written in French and may carry an English version (`titleEn`,
  `bodyEn`): both or neither, which the site shows when it is read in English.
  `Blog/Shared/Domain/Provider/` holds `CategoryIdProviderInterface` (a category by its
  slug) and `AuthorProviderInterface` (the usernames behind author and commenter ids).
- `Competition/Profile/Game/`, `Competition/Profile/Player/`, `Competition/Profile/Clan/`,
  `Competition/Profile/Team/` — competition profiles. A `Game` lists the formats it is
  played in (`teamSizes`: 1 for 1v1, 5 for 5v5, up to 64). A `Player` is one user in one
  game. A `Clan` groups players of one game under a leader; players join by invitation
  and belong to one clan at most. A `Team` is a lineup a clan fields in one format:
  exactly `size` active members, one of them the leader who speaks for the team.
- `Competition/Competitor/` — the polymorphic player-or-team that actually competes.
  Enlisted lazily, through `CompetitorRegistryProviderInterface`, when a fight is
  opened or a tournament registration made; never through an endpoint of its own.
- `Competition/Fight/` — fights between two competitors of the same game and format,
  and their results: one side declares the scores, the other confirms them. A settled
  fight records `FightSettledEvent`. When the sides disagree, an administrator settles
  the fight on the scores they impose (`Fight::arbitrate()`, the fight then reads
  `arbitrated`) or sets the declaration aside so that it is declared again
  (`Fight::reopen()`). Until both sides agree, the result may change: the declaring
  side corrects its declaration, an administrator arbitrates or reopens. Once settled,
  confirmed by the other side or arbitrated, a fight is final for everyone,
  administrators included.
- `Competition/Tournament/` — single-elimination tournaments: registrations, the
  bracket (`Matchup`, one per slot, seeded 1 v last with byes for the top seeds), and
  winners moving on as `FightSettledEvent` comes in.
- `Competition/Ranking/` — Elo ratings, one per player profile and one per clan, in the
  ranking of their game. A `Rating` starts at 1000 on its first settled fight and moves
  by up to `K_FACTOR` (32) points per fight, zero-sum between the two sides: player
  profiles rate on their 1v1 fights, clans on the fights of their teams (two teams of
  one clan leave it as it is). Moved as `FightSettledEvent` comes in; a `RatingChange`
  per rating and fight records the move and keeps a fight from counting twice.
  `bin/console app:rankings:rebuild` empties the rankings and replays every settled
  fight in the order it was settled — run it once after deploying the rankings.
- `Competition/Shared/` — `CompetitorId` and the contracts every
  Competition module reads directly, in `Domain/Provider/`:
  `CompetitorIdProviderInterface`, `PlayerProfileProviderInterface`,
  `CompetitorRegistryProviderInterface` (enlist a player or a team, who a user speaks
  for, which competitors a player profile or a clan plays as, name the sides, what a
  competitor ranks as, which competitors bear a name) and
  `FightSchedulerProviderInterface` (open a fight with its two pending results). Each
  `…ProviderInterface` has its `…Provider` implementation next to it, in the same
  folder.

Each context (except `Shared`) has three layers:

```
<Context>/
  Domain/          entities, value objects, domain events, repository INTERFACES, domain-service interfaces
  Application/      controllers, command/query models, handlers, event subscribers, application events, services
  Infrastructure/   Doctrine repository implementations, DoctrineMapping/*.orm.xml, security adapters
```

## Tests (`tests/`)

- Mirror the `src/` namespace under `App\Tests\`.
- `final class <Subject>Test extends PHPUnit\Framework\TestCase`.
- Test methods named `test_snake_case_description`.
- Pure unit tests with mocked collaborators (`$this->createMock(SomeInterface::class)`);
  the current suite touches neither the container nor the database.
- PHPUnit is configured strictly: it fails on any deprecation, notice, or warning.

## Migrations

- Generate: `docker compose exec php bin/console make:migration`, then review the file
  in `migrations/`.
- Apply: `docker compose exec php bin/console doctrine:migrations:migrate`.
- Keep `doctrine:schema:validate` green.

## Commit conventions

Short `Type: summary` subjects, matching git history: `Feat:`, `Fix:`, `Refactor:`,
`Test:`, plus bare `Add ...` / `Update ...`. Dependabot uses the `chore` prefix.

---

# Adding an endpoint

This is the checklist to follow whenever a new route is added. Deptrac catches only
a fraction of these rules, so walk the list by hand — a green `make ddd` does not
mean the endpoint is correct.

## 1. Files to create

For a bounded context `<BC>`, a resource `<Resource>` and an action `<Action>`:

```
src/<BC>/<Resource>/
  Application/Controller/Api/<Verb><Resource><Suffix>Controller.php   # HTTP + OpenAPI
  Application/Model/<Action><Resource>Command.php                     # write
  Application/Model/<Action><Resource>Query.php                       # read
  Application/Service/<Action><Resource>Handler.php                   # #[AsMessageHandler]
  Domain/Entity/<Resource>.php                                        # behaviour method
  Domain/Event/<Resource><Action>edEvent.php                          # domain event
  Domain/Repository/<Resource>RepositoryInterface.php                 # if a new read is needed
  Infrastructure/DoctrineMapping/<Resource>.orm.xml                   # if the schema moves
migrations/VersionYYYYMMDDHHMMSS.php                                  # if the schema moves
tests/<BC>/<Resource>/Domain/<Resource>Test.php                       # domain rules
tests/<BC>/<Resource>/Application/<Action><Resource>HandlerTest.php    # handler behaviour
deptrac.yaml                                                          # layers + ruleset
```

Names line up across the four files: `PostFightResultsConfirmationController` →
`ConfirmFightResultsCommand` → `ConfirmFightResultsHandler` → `Fight::confirmOutcome()`.

## 2. Controller rules

- `final class`, extends `AbstractController`, a single `__invoke`.
- `#[Route('/api/…', name: 'api_…', methods: ['…'])]`. Route names follow
  `api_<resource>_<action>`; keep the same shape across a resource's routes.
- Every `{placeholder}` in the path is a `__invoke` argument **and** an
  `#[OA\Parameter(in: 'path')]`. Never document a parameter the route does not have.
- Body: `json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)`.
- **Never read the caller's identity from the body or the query string.** The user is
  the JWT identity, read through `CurrentUserProviderInterface`. A `user` key in a
  request payload is always a bug and usually a privilege escalation.
- **Never read a handler's output back from the session.** A query handler returns its
  payload through `HandleTrait::handle()`; a command handler that must answer with the
  written resource returns it the same way, or on the events of a User verification
  chain (§5). Do not count on Symfony to catch a session round-trip: the
  `stateless: true` firewalls under `^/api` keep the token out of the session, but they
  do not mark the request stateless, so nothing warns.
- No `try`/`catch`. Handlers throw the typed exceptions from `App\Shared\Exception\*`
  and `DomainExceptionListener` maps them to 400 / 403 / 404 / 409.
- Controllers hold no business logic and touch no repository.

## 3. Handler rules

- `#[AsMessageHandler]`, one `__invoke(<Action><Resource>Command $command)`.
- Depend on **interfaces** only: `…RepositoryInterface`, `CurrentUserProviderInterface`,
  `NormalizerInterface`. Injecting a concrete class is an architecture violation even
  when Deptrac stays silent about it.
- Inject nothing you do not use. An unused `MessageBusInterface` or `RequestStack` in a
  constructor is a review finding.
- Read each aggregate from **its own** repository. `ResultRepositoryInterface` returns
  results, `FightRepositoryInterface` returns fights — mixing them is a `TypeError`
  waiting for the first request.
- `findOneBy()` takes an **array of criteria**, matching Doctrine's
  `ServiceEntityRepository`. Passing a bare id string is a `TypeError`.
- State transitions live on the entity, never in the handler. The handler orchestrates:
  load, call the domain method, save, dispatch the recorded domain events.
- A scalar that carries a business rule gets a value object, built at the top of the
  handler before any repository read, so an invalid payload costs no query. Follow the
  house shape: one `final class <Concept>ValueObject` per concept in
  `Shared/ValueObject/`, doing the checking in `ensureIsValid<Concept>()`, and used as
  is by every context — never subclassed in the owning context. Two concepts with
  different rules get two value objects, even when they look alike
  (`TeamNameValueObject`, `TournamentNameValueObject`). The older value objects
  (`EmailValueObject`, `BattletagValueObject`, `ScoreValueObject`…) are still abstract,
  with a `final class <Concept>` in their context. Keep the entity's getter returning
  the raw scalar unless you mean to change the JSON: a getter that returns the value
  object serializes as `{"value": …}` and rewrites the published contract.
- Authorisation happens before any write: resolve which side of the aggregate the
  current user is, and throw `PermissionDeniedException` when they are on neither.
  A resolver that silently falls back to "the first one" is a security hole.

## 4. Serialization

- Entities keep private properties, so `json_encode($entity)` yields `{}`. Always go
  through `SerializerInterface` / `NormalizerInterface`.
- Value objects serialize as `{"value": "<uuid>"}`. Document that shape in the OpenAPI
  response schema.
- The handler builds the response it returns: normalize the entity, or, when the
  response combines several aggregates, build the array in a private method of the
  handler itself. No shared view class between handlers.
- POST create endpoints answer **200**, not 201, and carry only the primary resource —
  no secondary entities padded in.
- Request bodies are read with `json_decode($content, true)`, so a JSON number arrives
  as a PHP `int` and a JSON string as a `string`. Type the value for what it already is
  and carry that type unchanged to the handler. A `(int)` cast halfway down a chain means
  two links disagree on the contract; fix the types instead of casting.

## 5. Cross-bounded-context wiring

Two mechanisms, and they are not interchangeable.

**A contract in `<BC>/Shared/`, read directly.** This is the default when the current
request needs a fact owned by another context. The owning context publishes an
interface under `src/<BC>/Shared/…`, implements it against its own repositories, and
the caller injects the interface. `CurrentUserProviderInterface`,
`CompetitorIdProviderInterface`, `PlayerProfileProviderInterface`,
`CompetitorRegistryProviderInterface`, `FightSchedulerProviderInterface`,
`CategoryIdProviderInterface` and `AuthorProviderInterface` are the ones in place. Prefer this over chaining finder services, and over events.

**A domain or application event.** Use it only when another context must *react* to
something that already happened — sending a mail after a user registers, moving a
tournament winner on and the ratings of both sides once a fight is settled
(`FightSettledEvent`). Do not use an
event chain to assemble the data one request needs: each hop adds an event class, a
subscriber, a constructor signature and a silent `ArgumentCountError` when one of them
drifts, and the response then has nowhere to go but the session.

**The User context's verification chain** is the one sanctioned exception, kept on
purpose: creating a player profile, a game or a team, writing an article, and
declaring or confirming a fight result go through the User context. The controller dispatches `On<Thing>RequestedEvent` (owning
context); a subscriber of the User context checks the role and dispatches
`On<Thing>…VerifiedEvent` carrying the verified user id; a subscriber of the owning
context runs the command for that user through `HandleTrait`. The handler's JSON goes
back the same way: the owning subscriber sets it on the verified event, the User
subscriber copies it onto the requested event, and the controller reads it from the
event `dispatch()` returned — never from the session. Player creation still reads its
own from the session: align it when it is next touched.

Naming, when an event really is warranted: `On<Thing><PastParticiple>Event` in
`Application/Event/`, subscriber `<Thing><PastParticiple>EventSubscriber` in
`Application/EventSubscriber/`. Domain events are `<Resource><PastParticiple>Event` in
`Domain/Event/`, recorded with `recordDomainEvent()` and dispatched by the handler
after the save.

## 6. Value objects

One concept, one class. `CompetitorId` belongs in
`Competition/Shared/Domain/Entity/ValueObject/` and every context uses that one. A
per-context copy of the same id compiles fine and then throws `TypeError` the first
time two contexts meet in a single call — Deptrac reports it as a layer violation, PHP
reports it at runtime.

## 7. OpenAPI

Written in English, on the controller, next to the route. Required on every endpoint:

- `#[OA\Tag(name: '<Resource>')]`
- one `#[OA\Parameter]` per path parameter
- `#[OA\RequestBody]` listing **exactly** the keys the controller reads, with `required`
- a 200 response with a `content` schema, not a bare description
- the shared error responses that apply, by ref:
  `#/components/responses/BadRequest`, `Unauthorized`, `Forbidden`, `NotFound`,
  `Conflict`, defined in `config/packages/nelmio_api_doc.yaml`

The rule of thumb: the documented body and the `$parameters[...]` reads in `__invoke`
must be the same set of keys, and the documented status codes must be the ones the
handler's exceptions actually produce.

## 8. Tests

Two files per endpoint, both pure unit tests with mocked collaborators:

- **Domain** — the state machine and its refusals: the happy transition, each illegal
  transition, each invalid input. One `test_` method per rule.
- **Handler** — authorisation, the not-found paths, and that nothing is written when a
  rule is broken (`$repository->expects($this->never())->method('save')`).

`final class`, `extends TestCase`, methods `test_snake_case_description`. Build entities
through their factories, never with reflection. When a test names a class, that class
must exist: PHPUnit is configured to fail on deprecations, notices and warnings, so a
suite written against a design that was later changed fails loudly rather than skipping.

## 9. Deptrac

Add the new namespace to `layers` and its allowed dependencies to `ruleset` in the same
commit as the code. `make ddd` must be green before the endpoint is considered done.

Its coverage has holes — it checks class references, not constructor contracts, not
interface-versus-implementation, not whether the route matches the documentation. Treat
a green run as necessary, never as sufficient.

## 10. Definition of done

```bash
make test                                     # green, no skipped, no warning
make ddd                                      # zero violations
make sf c=lint:container                      # green
make sf c=doctrine:schema:validate            # mapping and database both in sync
make sf c=debug:router                        # the new route is listed as intended
```

PHPStan is not wired into the project, but the binary ships as a transitive dependency of
Deptrac. Run it by hand before calling an endpoint done — `src` and `tests` are clean at
level 5, so any output is yours:

```bash
php vendor/bin/phpstan analyse src tests --level=5 --no-progress
```

## 11. Defects this checklist was written from

Every rule above is here because the codebase broke it. These are the real cases, all
fixed. Recognise the shape before you write the same thing again.

**The caller's identity was read from the request body.** Four endpoints — article, game,
player, team — took a user UUID out of the payload, loaded that account and checked *its*
roles. Any authenticated caller who knew an editor's id could publish under their name.
The fix was `CurrentUserProviderInterface` in every case. The tell: a `user` or `author`
key in an `#[OA\RequestBody]`.

**The commenter's email came from the payload** on an authenticated route, so one account
could comment under another's address. Same shape, same fix.

**Authorisation was missing entirely** on category, comment and fight creation. Fight
creation also let a bystander open a fight between two strangers; the guard is that the
caller owns one of the two players. Confirming a fight result had no guard at all, so the
declaring side could confirm its own claim, and a fight nobody had declared could be
settled as a nil-all draw.

**A `Result` was loaded from `FightRepositoryInterface`,** twice, and the fight itself was
looked up by a `fight` field when its identifier is `id`. Doctrine answers both with
`Unrecognized field`. Adding `@extends ServiceEntityRepository<Entity>` to every
repository is what makes this visible to static analysis — without it `findOneBy()`
returns a bare `object` and nothing downstream can be checked.

**Three links disagreed on one concept.** The team chain carried a single player through an
event typed `array $players` into a command exposing only `setPlayer()`. The fight result
chain carried a `string` status into accessors typed `ResultStatus` over a `string`
property. Both were fatal at the first real request, and both went unnoticed because no
test crosses the HTTP layer.

**Declared data travelled the whole chain and was then dropped.** `reportedStatus` is
documented as the outcome the declaring side claims, and the code stored the literal
`reporting` transport state instead. If a value is worth carrying through four classes, a
test must assert where it lands.

**Injected dependencies went unused.** The confirmation handler injected exactly the two
services needed for the guard it was missing. An unused constructor argument is usually a
rule someone meant to write.
