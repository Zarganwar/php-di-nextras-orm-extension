<?php


namespace Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\Attributes;

use Attribute;
use Nextras\Orm\Repository\IRepository;

#[Attribute(Attribute::TARGET_CLASS)]
final class RepositoryMapper
{

	/**
	 * @param class-string<IRepository> $mapperClass
	 */
	public function __construct(
		public readonly string $mapperClass
	) {}

}