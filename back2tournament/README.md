# back2tournament

Backend API for an e‑sports competition platform — games, player profiles, clans
and their teams, fights from 1v1 to NvN, tournaments, and editorial content.

Built with **Symfony 7.4 LTS** on **PHP 8.5**, served by
[FrankenPHP](https://frankenphp.dev) + [Caddy](https://caddyserver.com/) in Docker.
Scaffolded from the [`dunglas/symfony-docker`](https://github.com/dunglas/symfony-docker)
template.

The code follows Domain‑Driven Design with a strict hexagonal / clean architecture:
independent bounded contexts (`Authentication`, `Blog`, `Competition`), each split
into Domain / Application / Infrastructure layers, with dependencies enforced by
[Deptrac](https://github.com/deptrac/deptrac). See [AGENTS.md](AGENTS.md) for the
architecture rules and coding conventions.

## Requirements

- [Docker](https://docs.docker.com/get-docker/) with [Compose](https://docs.docker.com/compose/install/) v2.10+

## Getting started

```bash
make start
```

It also starts [Garage](https://garagehq.deuxfleurs.fr), the S3 storage the images
are uploaded to, and prepares its bucket (`docker/garage/init.sh`). Browsers read an
image on `http://localhost:3902/<key>`, and the bucket is browsed on
`http://localhost:3909` ([Garage Web UI](https://github.com/khairul169/garage-webui)).

Then:

1. Open `https://localhost` and accept the auto‑generated TLS certificate.
2. Create the database and run the migrations:
   ```bash
   make sf c="doctrine:database:create"
   make sf c="make:migration"
   make sf c="doctrine:migrations:migrate"
   ```
3. *(optional)* seed a development user: `make user`

Stop everything with `make down`.

## Common commands

A `makefile` wraps the day‑to‑day tasks (run `make` for the full list):

```bash
make up             # start containers (detached)
make down           # stop containers
make sh             # shell into the php container
make test           # run PHPUnit  (c="--filter Foo" for extra args)
make ddd            # Deptrac architecture check
make sf c=<cmd>     # bin/console <cmd>
make cc             # clear cache
make composer c=<>  # composer
make user           # seed a dev user
```

## Publishing from a tool (Hermes…)

A tool such as Hermes writes news
through the `/api/bot` routes, signed in with an API token instead of a user account.
What it writes is a draft: an editor reviews it in the backoffice and publishes it.

The token is the value of the `BOT_API_TOKEN` environment variable: generate one with
`openssl rand -hex 32`, set it in `.env.local` in development (in the environment of
the stack in production), and give the tool the same value. While the variable is
empty, `/api/bot` stays closed; changing it revokes the previous token. The token
opens `/api/bot` and nothing else:

```bash
curl -H "Authorization: Bearer $BOT_API_TOKEN" https://localhost/api/bot/categories/

curl -X POST https://localhost/api/bot/articles/ \
  -H "Authorization: Bearer $BOT_API_TOKEN" -H 'Content-Type: application/json' \
  -d '{"title": "Les résultats du week-end", "body": "…", "categorySlug": "esport-news",
       "titleEn": "The weekend results", "bodyEn": "…"}'
```

The English version is optional, but whole: `titleEn` with `bodyEn`, or neither. The
API documentation (`/api/doc`) describes both routes under the `apiToken` scheme.

## Architecture

```
src/
  Shared/                shared kernel — dependency-free (AggregateRoot, base value objects)
  Authentication/User/   users, roles, password hashing, security
  Authentication/ApiToken/ API tokens of the tools that write drafts (Hermes…)
  Blog/                  Article, Category, Shared — editorial content
  Competition/Profile/   Game (and its formats), Player, Clan, Team — competition profiles
  Competition/Competitor/ the player-or-team that competes
  Competition/Fight/     fights, 1v1 to NvN, and their declare-then-confirm results
  Competition/Tournament/ single-elimination tournaments and their bracket
  Competition/Shared/    contracts shared by the Competition modules
  Media/                 images: Imagick compression to WebP, S3 storage (Garage)
```

Each context (except `Shared`) is layered `Domain / Application / Infrastructure`.
Full conventions and the Deptrac ruleset are documented in [AGENTS.md](AGENTS.md).

## Deployment

The images live in the Garage service of `compose.yaml`. Before deploying (on
Coolify, for instance), set your own `GARAGE_*` and `S3_*` secrets and give Garage's
web endpoint a public domain: the steps are in [AGENTS.md](AGENTS.md#images). Set
`BOT_API_TOKEN` too if a tool such as Hermes publishes drafts.

## Tests & quality

```bash
make test    # PHPUnit — fails on any deprecation, notice or warning
make ddd     # Deptrac — layer & bounded-context boundaries
```

CI (`.github/workflows/ci.yaml`) additionally runs `doctrine:schema:validate` and
super-linter on every push and pull request.
