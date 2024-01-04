<?php


namespace Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class ModelRepository
{

	public function __construct(
		public readonly string $repositoryClass,
		public readonly string $name,
	) {}

}