<?php


namespace Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\RepositoryFinders;


use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\Attributes\ModelRepository;
use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\RepositoryFinder;
use Nextras\Orm\Exception\RuntimeException;
use Nextras\Orm\Model\IModel;
use Nextras\Orm\Repository\IRepository;
use ReflectionClass;
use ReflectionException;
use function assert;
use function class_exists;
use function sprintf;

final class AttributeRepositoryFinder implements RepositoryFinder
{

	/**
	 * @param class-string<IModel> $modelClass
	 * @return array<string, class-string>
	 * @throws ReflectionException
	 */
	public function findRepositories(string $modelClass): array
	{
		$modelReflection = new ReflectionClass($modelClass);
		assert($modelReflection->getFileName() !== false);

		$repositories = [];

		foreach ($modelReflection->getAttributes(ModelRepository::class) as $attribute) {
			$instance = $attribute->newInstance();

			assert($instance instanceof ModelRepository);

			if (!class_exists($instance->repositoryClass)) {
				throw new RuntimeException(sprintf(
					"Repository class '%s' defined in model '%s' does not exist.",
					$instance->repositoryClass,
					$modelClass,
				));
			}

			$rc = new ReflectionClass($instance->repositoryClass);

			assert($rc->implementsInterface(IRepository::class), sprintf(
				"Repository class '%s' defined in model '%s' does not implement interface %s.",
				$instance->repositoryClass, $modelClass, IRepository::class
			));

			$repositories[$instance->name] = $instance->repositoryClass;
		}

		return $repositories;
	}

}