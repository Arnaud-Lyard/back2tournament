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

## Architecture

```
src/
  Shared/                shared kernel — dependency-free (AggregateRoot, base value objects)
  Authentication/User/   users, roles, password hashing, security
  Blog/                  Article, Category, Shared — editorial content
  Competition/Profile/   Game (and its formats), Player, Clan, Team — competition profiles
  Competition/Competitor/ the player-or-team that competes
  Competition/Fight/     fights, 1v1 to NvN, and their declare-then-confirm results
  Competition/Tournament/ single-elimination tournaments and their bracket
  Competition/Shared/    contracts shared by the Competition modules
```

Each context (except `Shared`) is layered `Domain / Application / Infrastructure`.
Full conventions and the Deptrac ruleset are documented in [AGENTS.md](AGENTS.md).

## Tests & quality

```bash
make test    # PHPUnit — fails on any deprecation, notice or warning
make ddd     # Deptrac — layer & bounded-context boundaries
```

CI (`.github/workflows/ci.yaml`) additionally runs `doctrine:schema:validate` and
super-linter on every push and pull request.
