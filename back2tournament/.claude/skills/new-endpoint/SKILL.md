---
name: new-endpoint
description: Add an HTTP endpoint (route, controller, command or query, handler, domain method, OpenAPI, tests, Deptrac layers, Miro flow board) to this Symfony DDD API. Use whenever the task is to create, expose, or wire a new API route in back2tournament, or to review one someone else wrote. Covers the bounded-context layering, the JWT identity rule, cross-context wiring, keeping the business-flow diagram in sync, and the definition of done.
---

# Adding an endpoint to back2tournament

`AGENTS.md` at the repository root is the authoritative recipe. **Read it before writing
anything** — sections 1 to 10 list the files to create, the naming, the OpenAPI
requirements and the definition of done. This skill does not repeat it. It adds the parts
that a checklist alone did not prevent, each one grounded in a defect that actually
shipped.

## Work in this order

The order matters: three of the seven defects below were type mismatches that only show up
once the whole chain exists, and a green unit test never caught any of them.

1. Decide the **shape of the flow** before writing a class. See "One command, or a chain?"
2. Write the **domain method** on the aggregate, and its test.
3. Write the **command or query**, the **handler**, and the handler test.
4. Write the **controller** and its OpenAPI attributes.
5. Add the **Deptrac layers and grants** in the same commit.
6. Draw the new chain on the **Miro flow board**.
7. Run the definition of done, then **exercise the route for real**.

## One command, or a chain?

Default to a single command on the message bus, with the controller returning the
handler's result through `HandleTrait::handle()`.

Reach for an application event only when another context must *react* to something that
already happened — sending a mail after registration, creating a competitor after players
are verified. Never use an event chain to assemble the data the current request needs.

Why this is not a style preference: a chain dispatched through `EventDispatcherInterface`
cannot return a value. Seven handlers in this codebase work around that by writing the
serialized entity into the HTTP session and having the controller read it back, across
firewalls declared `stateless: true`. Two concurrent requests from one session then swap
bodies, and if the handler throws before the write the controller hands `null` to a
signature that demands a `string`. Do not add an eighth.

When the current request needs a fact owned by another context, read the published
contract in `<BC>/Shared/` directly. `CurrentUserProviderInterface` and
`CompetitorIdProviderInterface` are the two that exist.

## The seven traps

**1. Identity comes from the JWT, never from the payload.** If your `#[OA\RequestBody]`
has a `user`, `author`, `email` or any other key naming the caller, you have written a
privilege escalation. Four endpoints did exactly this: they loaded the account named in
the body and checked *its* roles, so anyone who knew an editor's UUID could publish as
them. Use `CurrentUserProviderInterface::getUser()`.

**2. Every write needs an authorisation rule, and the rule is rarely just a role.** Three
endpoints had no gate at all. Ask two questions: which role may do this, and is this
caller entitled to *this particular* object. Opening a fight requires owning one of the
two players. Confirming a result requires being the side that did not declare it. A role
check alone would have let a bystander settle other people's matches.

**3. Read each aggregate from its own repository, with a mapped field name.** A `Result`
was twice fetched from `FightRepositoryInterface`, and a `Fight` was looked up by a
`fight` criterion when its identifier is `id`. Doctrine answers both with
`Unrecognized field`. New repositories carry
`@extends ServiceEntityRepository<Entity>`; without it `findOneBy()` returns a bare
`object` and static analysis can check nothing downstream.

**4. One concept keeps one type across every link.** A team's single player travelled
through an event typed `array $players` into a command exposing only `setPlayer()`. A
result status travelled as a `string` into accessors typed `ResultStatus` over a `string`
property. Both were fatal on the first real request. When you add a field, grep every
class between the controller and the aggregate and confirm they agree.

**5. A scalar carrying a business rule becomes a value object, never a private validation
helper on the handler.** The rule belongs to the domain, where it can be tested and reused
on its own. Put a `<Concept>ValueObject` in `src/Shared/ValueObject/` that checks in an
`ensureIsValid<Concept>()` method and throws `ValidationException`. Add a
`final class <Concept>` in the owning context when the rule is universal and the context
only needs its own type, as `ScoreValueObject` and `Score` do; keep it to the one shared
class when the rule already names the concept, as `DeclaredStatusValueObject` does.

Build them before the first repository read, so an invalid payload costs no query, and
unwrap them at the call into the domain the way `new Score(...)->getValue()` does. Never
call an enum's `from()` on unvalidated request data: it raises a `ValueError` and a 500
instead of a 400.

**6. Assert where carried data lands.** `reportedStatus` is documented as the outcome the
declaring side claims; the code stored the literal transport state instead, and the
documented contract was a lie for as long as no test looked. If a value is worth threading
through four classes, one test must assert its final resting place.

**7. Inject nothing you do not use.** The confirmation handler injected exactly the two
services needed for the guard it was missing. An unused constructor argument is usually a
rule someone intended to write.

## The shape to copy

`PostCommentController` and `CreateCommentHandler` are the current reference for a write
endpoint: attribute route, single `__invoke`, `HandleTrait`, no session, and the response
returned by the bus. When the handler needs the caller, `FindCurrentUserHandler` shows it
resolved through `CurrentUserProviderInterface`.

```php
final class PostCommentController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $createCommentCommand = new CreateCommentCommand();
        $createCommentCommand->setArticleId($parameters['articleId']);
        $createCommentCommand->setMessage($parameters['message']);

        return JsonResponse::fromJsonString($this->handle($createCommentCommand));
    }
}
```

The handler loads what it writes against, refuses when it is missing, then writes:

```php
$article = $this->articleRepository->findOneBy(['id' => $command->getArticleId()]);
if (!$article) {
    throw new NotFoundException('article not found');
}
```

No `try`/`catch` anywhere. `DomainExceptionListener` maps `ValidationException`,
`NotFoundException`, `ConflictException` and `PermissionDeniedException` to 400, 404, 409
and 403.

## Deptrac

Reading a contract from another context is a new grant. Add the layer name to that
ruleset in the same commit — `make ddd` runs with `--fail-on-uncovered`, so a new
namespace breaks the build until it is declared.

## Keep the Miro flow board in sync

The board is the only place a cross-context chain is visible end to end. The code shows one
hop at a time, and that is exactly how a chain came to dispatch an event nobody subscribed
to, and how three links came to disagree on whether a team has one player or many. A board
that no longer matches the code is worse than no board, so it moves in the same commit as
the endpoint.

Board `uXjVHnpRG1g=`, named Back2tournament. The token lives in `.env.local` under
`MIRO_ACCESS_TOKEN`, which is gitignored. Source it, never echo it:

```bash
set -a; . ./.env.local; set +a
```

**The board speaks business language, not PHP.** It is read by whoever needs to understand
the product, so a note says "Déclarer le résultat", never `UpdateFightResultsCommand`. An
action is an infinitive verb; the business event it produces is a past participle. The
event follows from the action and is written as a fact that has happened.

Seven kinds of note, one legend column on the far left:

| Colour | Kind | Written as |
|---|---|---|
| `light_yellow` | aggregate | one per bounded context, names the zone |
| `yellow` | actor | the role, or `Système` when the platform acts by itself |
| `light_blue` | action | infinitive verb |
| `orange` | business event | past participle, the fact that resulted |
| `pink` | business rule | the constraint, in one sentence |
| `violet` | external system | what the platform talks to |
| `green` | user data | the read the actor needs to act |

**Lay out in two dimensions.** One zone per bounded context, zones on a grid, columns at
`x` 0, 2400 and 4800 and rows 2900 apart. Inside a zone the columns are data −350, actor
0, action 350, event 700, rule 1050, system 1400, and rows are 360 apart. A stacked list
of every flow down one column is unreadable past the third chain.

Read the board before writing, so you extend the right zone instead of landing on top of
it:

```bash
curl -s -H "Authorization: Bearer ${MIRO_ACCESS_TOKEN}" \
  'https://api.miro.com/v2/boards/uXjVHnpRG1g%3D/items?limit=50'
```

Sticky notes go to `POST /v2/boards/{id}/sticky_notes` with `data.content`,
`style.fillColor` and `position`. The board centre is the origin and `x`/`y` place the
note's centre. Set width only: a square note asked for 240 wide renders **275 tall**, which
is why the row pitch is 360 and not 240. Connectors go to
`POST /v2/boards/{id}/connectors` with `startItem.id` and `endItem.id` from the creation
responses, and only for hops that leave a zone. The dashed box around a zone is
`POST /v2/boards/{id}/shapes` with `round_rectangle`; `fillColor` must be a hex string, so
transparency is `fillColor: "#ffffff"` plus `fillOpacity: "0"`, and `borderStyle: "dashed"`.

Rate limits allow a thousand of these calls a minute, so a whole zone builds in one script.
Keep the created ids in memory for the connectors instead of re-reading the board.

## Before calling it done

Run everything in `AGENTS.md` section 10, plus static analysis by hand:

```bash
php vendor/bin/phpstan analyse src tests --level=5 --no-progress
```

Then the part no command covers. **The suite contains no functional test — nothing in it
crosses the HTTP layer.** That is precisely why the defects above sat in `main`. A green
unit test proves your handler works when called with the arguments you imagined, not that
the route, the firewall, the serializer and the exception listener agree. Start the stack
and call the endpoint with a real token: once for the happy path, and once with a caller
who must be refused.

Last, re-read the zone you drew on the board against the code you just wrote. Every orange
note must correspond to a state the code actually reaches, every pink rule to a check that
really refuses, and every arrow to a real dispatch or subscriber. A rule on the board that
no code enforces is the most expensive kind of wrong.
