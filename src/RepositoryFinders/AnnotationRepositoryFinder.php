<?php


namespace Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\RepositoryFinders;


use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\RepositoryFinder;
use Nette\Utils\Reflection;
use Nextras\Orm\Exception\InvalidStateException;
use Nextras\Orm\Exception\RuntimeException;
use Nextras\Orm\Model\Model;
use Nextras\Orm\Repository\IRepository;
use ReflectionClass;
use ReflectionException;
use function assert;
use function class_exists;
use function preg_match_all;
use function sprintf;
use const PREG_SET_ORDER;

final class AnnotationRepositoryFinder implements RepositoryFinder
{

	/**
	 * @throws ReflectionException
	 */
	public function findRepositories(string $modelClass): array
	{
		if ($modelClass === Model::class) {
			throw new InvalidStateException('Your model has to inherit from ' . Model::class . '. Use compiler extension configuration - model key.');
		}

		$modelReflection = new ReflectionClass($modelClass);
		assert($modelReflection->getFileName() !== false);

		$repositories = [];
		preg_match_all(
			'~^  [ \t*]*  @property(?:|-read)  [ \t]+  ([^\s$]+)  [ \t]+  \$  (\w+)  ()~mx',
			(string) $modelReflection->getDocComment(), $matches, PREG_SET_ORDER
		);

		/**
		 * @var string $type
		 * @var string $name
		 */
		foreach ($matches as [, $type, $name]) {
			/** @phpstan-var class-string<IRepository> $type */
			$type = Reflection::expandClassName($type, $modelReflection);
			if (!class_exists($type)) {
				throw new RuntimeException("Repository '{$type}' does not exist.");
			}

			$rc = new ReflectionClass($type);
			assert($rc->implementsInterface(IRepository::class), sprintf(
				'Property "%s" of class "%s" with type "%s" does not implement interface %s.',
				$modelClass, $name, $type, IRepository::class
			));

			$repositories[$name] = $type;
		}

		return $repositories;
	}

}