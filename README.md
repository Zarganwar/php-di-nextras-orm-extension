## How to use


## Create your model classes see [Nextras/Orm](https://nextras.org/orm)

### Entities

```php
use Nextras\Orm\Entity\Entity;

/**
 * @property-read int $id {primary}
 * @property string $name
 */
final class Account extends Entity
{

}
```


### Mappers

```php
use \Nextras\Orm\Mapper\Mapper;

final class AccountMapper extends Mapper
{

}
```


### Repositories

Use `RepositoryMapper` attribute to map repository to mapper class 

```php

use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\Attributes\RepositoryMapper;
use Nextras\Orm\Repository\Repository;

#[RepositoryMapper(AccountMapper::class)]
final class AccountRepository extends Repository
{

	public static function getEntityClassNames(): array
	{
		return [Account::class];
	}

}

```


### Model
- Use `ModelRepository` attribute to map repository to model class.
- Every repository must be mapped to model class!
- !Do not configure model by [Nextras/Orm - Nette](https://nextras.org/orm/docs/main/config-nette)!

```php

use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\Attributes\ModelRepository;

#[ModelRepository(AccountRepository::class, 'accounts')]
// ...
// ...
final class Model extends \Nextras\Orm\Model\Model
{

}
```


## Register extension

Use Config class to configure extension

```php
// config.php
use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\Config;
use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\OrmExtension;
use Psr\Container\ContainerInterface;

return [
    // Configure extension
	Config::class => fn(ContainerInterface $c) => new Config(
		cacheDirectory: __DIR__ . '/../var/cache',
		modelClass: Model::class,
		connection: [/* See class PhpDoc */]
	),

    // Register extension
	OrmExtension::class => fn(ContainerInterface $container) => new OrmExtension(
		$container, 
		$container->get(Config::class),
	),
];
```

After container build call `OrmExtension::register` method

```php
$containerBuilder = new DI\ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/config.php');
$build = $containerBuilder->build();

$build->call([OrmExtension::class, 'register']);
```

## Enjoy

```php
$container->get(AccountRepository::class)->findAll(); // Returns Nextras\Orm\Collection\ICollection
```