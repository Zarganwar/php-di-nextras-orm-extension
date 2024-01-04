<?php


namespace Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension;


use Nextras\Orm\Model\IRepositoryLoader;
use Nextras\Orm\Repository\IRepository;
use Psr\Container\ContainerInterface;

final class RepositoryLoader implements IRepositoryLoader
{


	public function __construct(private readonly ContainerInterface $container) {}


	public function hasRepository(string $className): bool
	{
		return $this->container->has($className);
	}


	public function getRepository(string $className): IRepository
	{
		$repository = $this->container->get($className);

		assert($repository instanceof IRepository);

		return $repository;
	}


	public function isCreated(string $className): bool
	{
		return $this->container->has($className);
	}

}