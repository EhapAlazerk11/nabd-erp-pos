# Contributing to NabdAI

## Branches

Three long-lived branches. Never commit directly to any of them.

| Environment | Branch | Host | Database | Deploys |
| --- | --- | --- | --- | --- |
| Development | `dev` | dev.nabdai.net | `nabd_dev` | automatic on push |
| Staging | `staging` | staging.nabdai.net | `nabd_staging` | automatic on push |
| Production | `main` | nabdai.net | `nabd_prod` | manual approval |

Work happens on short-lived branches cut from `dev`:

```
feat/CORE-12-business-switcher
fix/POS-04-refund-rounding
chore/DS-01-design-tokens
```

Flow: feature branch → PR into `dev` → PR `dev` → `staging` → PR `staging` → `main`.

Hotfixes branch from `main`, merge to `main`, then get cherry-picked back to `dev`.

## Commits

Conventional Commits, so the changelog writes itself:

```
feat(auth): add OTP resend cooldown
fix(pos): correct VAT rounding on refunds
chore(ci): cache composer dependencies
```

## Pull requests

- Keep them small. A PR touching more than ~400 lines is usually two PRs.
- Fill in the template — what changed, why, how you tested it.
- CI must be green before review.
- Any PR touching business-owned data must say how tenant isolation holds.

## Multi-tenancy — the rule that cannot be broken

A user may belong to several businesses. The tenant boundary is `business_id`
on every business-owned row.

1. `business_id` comes from the authenticated session. Never from the request
   body, never from a query parameter.
2. Every query against a business-owned table is scoped by it — through a
   scoped Prisma client, a Sequelize default scope, or an Eloquent global
   scope. Not through a `where` clause a developer has to remember.
3. Every new business-owned model ships with a test proving business A cannot
   read or write business B's rows.

Isolation across all 75 models is the go-live gate. Nothing reaches production
until that suite is green.

## The schema rule

One PostgreSQL database serves all four products, and three different ORMs
talk to it: Eloquent (`nabd-erp-pos`), Prisma (`nabd-platform-core`) and
Sequelize (`nabd-store`). That only works because exactly one of them is
allowed to change the shape of the database.

**Laravel owns the commerce schema.** `nabd-erp-pos` migrations are the only
thing that creates or alters products, inventory, orders, customers,
suppliers and accounting tables. Prisma introspects them (`prisma db pull`)
and Sequelize models them with `{ freezeTableName: true }` — neither migrates
them, ever.

Each Node service still migrates the tables it alone owns:
`nabd-platform-core` for users, businesses, roles, subscriptions and AI
artefacts; `nabd-store` for cart, wishlist and review tables that have no
NexoPOS equivalent.

Before adding a table, ask who owns it:

- Products, stock, orders, suppliers, accounting → a Laravel migration.
- Accounts, businesses, permissions, billing, AI → a Prisma migration.
- Storefront-only concepts → a Sequelize migration.

If two services would both migrate a table, that table belongs to Laravel.

## Local setup

See this repo's README, and [`nabd-infra`](https://github.com/EhapAlazerk11/nabd-infra)
for the shared docker-compose (PostgreSQL + Redis) every service develops against.
