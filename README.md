# MysqlAsyncBundle

A Symfony bundle around the [`amphp/mysql`](https://amphp.org/mysql/) 3 connection pool.
`Pool::executeQuery()` sends the query and returns a `Result` immediately, so one request can
have several queries in flight at the same time without blocking on any of them.

## Requirements

- PHP 8.3 or newer
- Symfony 6.4 (LTS), 7.4 (LTS) or 8.1

## Installation

```bash
composer require crazy-goat/mysql-async-bundle
```

Register the bundle, unless Symfony Flex did it for you:

```php
// config/bundles.php
return [
    // ...
    CrazyGoat\MysqlAsyncBundle\MysqlAsyncBundle::class => ['all' => true],
];
```

## Configuration

```yaml
# config/packages/mysql_async.yaml
mysql_async:
  pool:
    default:
      url: '%env(MYSQL_URL)%'
```

The url is parsed by `amphp/mysql`. These query parameters are understood: `charset`, `collate`,
`sqlMode`, `key`, `useCompression` and `useLocalInfile`. The configuration is validated when the
container is compiled, so a typo fails at boot rather than at the first query.

The pool is registered under `CrazyGoat\MysqlAsyncBundle\Connection\Pool` and can be autowired:

```php
use CrazyGoat\MysqlAsyncBundle\Connection\Pool;

final class ReportController extends AbstractController
{
    public function __construct(private readonly Pool $asyncConnection)
    {
    }

    #[Route('/report')]
    public function report(): Response
    {
        $slow = $this->asyncConnection->executeQuery('SELECT SLEEP(1)');
        $quick = $this->asyncConnection->executeQuery('SELECT 1');

        // Both queries are already running; reading the results does not start them.
        return new JsonResponse([
            'slow' => $slow->fetchScalar(),
            'quick' => $quick->fetchScalar(),
        ]);
    }
}
```

## Running the example application

`example/` is a small Symfony application that compares the async pool with a normal Doctrine
connection. It needs Docker and PHP 8.3 or newer.

```bash
cd example
composer install
docker compose up -d --wait
php -S 127.0.0.1:8000 -t public
```

Then open:

- <http://127.0.0.1:8000/async> — three queries through the bundle at the same time
- <http://127.0.0.1:8000/sync> — the same queries through Doctrine, one after another

Each page runs three queries that sleep for 50 milliseconds. `/async` answers in about the time of
one query, because the three run at the same time; `/sync` answers in about the time of three.

Shut everything down with:

```bash
docker compose down -v
```

If port 3306 is already taken, choose another one. The port has to be set both for Docker and for
the application:

```bash
MYSQL_PORT=3399 docker compose up -d --wait
echo 'MYSQL_PORT=3399' >> .env.local
```

## Development

[AGENTS.md](AGENTS.md) has the build, lint and test commands, [docs/workflow.md](docs/workflow.md)
the development process and [CHANGELOG.md](CHANGELOG.md) the changes.

## License

[MIT](LICENSE)