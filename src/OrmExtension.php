<?php


namespace Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension;


use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\Attributes\RepositoryMapper;
use Exception;
use InvalidArgumentException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Caching\Storages\FileStorage;
use Nextras\Dbal\Connection;
use Nextras\Dbal\IConnection;
use Nextras\Orm\Entity\IEntity;
use Nextras\Orm\Entity\Reflection\IMetadataParserFactory;
use Nextras\Orm\Entity\Reflection\MetadataParserFactory;
use Nextras\Orm\Mapper\Dbal\DbalMapperCoordinator;
use Nextras\Orm\Mapper\IMapper;
use Nextras\Orm\Mapper\Mapper;
use Nextras\Orm\Model\IModel;
use Nextras\Orm\Model\IRepositoryLoader;
use Nextras\Orm\Model\MetadataStorage;
use Nextras\Orm\Model\Model;
use Nextras\Orm\Repository\IRepository;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use function assert;
use function class_exists;
use function str_replace;

final class OrmExtension
{


	public function __construct(
		private readonly ContainerInterface $container,
		private readonly Config $config,
	) {}


	/**
	 * @return void
	 * @throws Exception
	 */
	public function register(): void
	{
		$this->validateConfig();
		$this->setupCache();
		$this->setupConnection();
		$this->setupDbalMapperDependencies();
		$this->setupMetadataParserFactory();
		$this->setupRepositoryLoader();

		$repositories = $this->findRepositories();

		if ($repositories !== []) {
			$repositoriesConfig = Model::getConfiguration($repositories);
			$this->setupMetadataStorage($repositoriesConfig[2]);
			$this->setupModel($repositoriesConfig);
			$this->registerRepositories($repositories);
		}
	}


	private function validateConfig(): void
	{
		if (!class_exists($this->config->modelClass)) {
			throw new InvalidArgumentException("Model class {$this->config->modelClass} does not exist.");
		}

		if (!class_exists($this->config->repositoryLoaderClass)) {
			throw new InvalidArgumentException("Repository loader class {$this->config->repositoryLoaderClass} does not exist.");
		}
	}


	private function setupCache(): void
	{
		$this->container->set(Storage::class, fn() => new FileStorage(
			$this->config->cacheDirectory)
		);

		$this->container->set(Cache::class, fn() => new Cache(
			$this->container->get(Storage::class),
			'Nextras.Orm'
		));
	}


	private function setupConnection(): void
	{
		if ($this->config->connection !== []) {
			$this->container->set(
				IConnection::class,
				fn() => new Connection($this->config->connection)
			);
		}
	}


	private function setupDbalMapperDependencies(): void
	{
		$this->container->set(
			DbalMapperCoordinator::class,
			fn(ContainerInterface $c) => new DbalMapperCoordinator(
				$c->get(IConnection::class),
			)
		);
	}


	private function setupMetadataParserFactory(): void
	{
		$this->container->set(
			IMetadataParserFactory::class,
			fn(ContainerInterface $c) => new MetadataParserFactory()
		);
	}


	private function setupRepositoryLoader(): void
	{
		$this->container->set(
			IRepositoryLoader::class,
			fn(ContainerInterface $c) => new $this->config->repositoryLoaderClass($c)
		);
	}


	/**
	 * @return array<string, string>
	 * @phpstan-return array<string, class-string<IRepository>>
	 */
	protected function findRepositories(): array
	{
		if (!class_exists($this->config->repositoryFinderClass)) {
			throw new InvalidArgumentException("Repository finder class {$this->config->repositoryFinderClass} does not exist.");
		}

		$finder = new $this->config->repositoryFinderClass();

		assert($finder instanceof RepositoryFinder);

		return $finder->findRepositories($this->config->modelClass);
	}


	/**
	 * @param array<class-string<IEntity>, class-string<IRepository>> $entityClassMap
	 */
	private function setupMetadataStorage(array $entityClassMap): void
	{
		$this->container->set(
			MetadataStorage::class,
			fn(ContainerInterface $c) => new MetadataStorage(
				$entityClassMap,
				$c->get(Cache::class),
				$c->get(IMetadataParserFactory::class),
				$c->get(IRepositoryLoader::class),
			)
		);
	}


	private function setupModel(array $repositoriesConfig): void
	{
		/** @var class-string<IModel> $modelClass */
		$modelClass = $this->config->modelClass;

		$this->container->set(
			IModel::class,
			fn(ContainerInterface $c) => new $modelClass(
				$repositoriesConfig,
				$c->get(IRepositoryLoader::class),
				$c->get(MetadataStorage::class),
			)
		);
	}


	private function registerRepositories(array $repositories): void
	{
		foreach ($repositories as $repositoryClass) {
			$mapperClass = null;
			$repositoryAttributes = (new ReflectionClass($repositoryClass))->getAttributes(RepositoryMapper::class);

			if ($repositoryAttributes) {
				// RECOMMENDED way using attribute
				foreach ($repositoryAttributes as $attribute) {
					$instance = $attribute->newInstance();

					assert($instance instanceof RepositoryMapper);

					if (!class_exists($instance->mapperClass)) {
						throw new Exception("Mapper {$instance->mapperClass} class does not exists");
					}

					$mapperClass = $instance->mapperClass;
					break;
				}
			} else {
				// Fallback to XXXRepository in namespace as XXXMapper
				$mapperClass = str_replace('Repository', 'Mapper', $repositoryClass);
			}

			$rc = new ReflectionClass($mapperClass);

			if (!$rc->implementsInterface(IMapper::class)) {
				throw new Exception("Mapper {$mapperClass} class must implements " . IMapper::class);
			}

			if ($rc->isSubclassOf(Mapper::class)) {
				// Only Dbal mappers need connection, ...
				$this->container->set(
					$mapperClass,
					fn(ContainerInterface $c) => new $mapperClass(
						$c->get(IConnection::class),
						$c->get(DbalMapperCoordinator::class),
						$c->get(Cache::class)
					)
				);
			}

			$this->container->set(
				$repositoryClass,
				function (ContainerInterface $c) use ($repositoryClass, $mapperClass): IRepository {
					/** @var IRepository $repository */
					$repository = new $repositoryClass($c->get($mapperClass));
					$repository->setModel($c->get(IModel::class));

					return $repository;
				}
			);
		}
	}

}