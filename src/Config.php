<?php


namespace Zarganwar\PhpDiNextrasOrmExtension\Extensions\NextrasOrmPhpDiExtension;


use Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\RepositoryFinders\AttributeRepositoryFinder;

final class Config
{
	/**
	 * Connection configuration example:
	 *        'driver' => 'mysqli',
	 *        'host' => 'localhost',
	 *        'username' => 'username',
	 *        'password' => 'password',
	 *        'database' => 'database-name',
	 * @param array<string, mixed> $connection
	 */
	public function __construct(
		public readonly string $cacheDirectory,
		public readonly string $modelClass,
		public readonly array $connection = [],
		public readonly string $repositoryLoaderClass = RepositoryLoader::class,
		public readonly string $repositoryFinderClass = AttributeRepositoryFinder::class,
	) {}

}