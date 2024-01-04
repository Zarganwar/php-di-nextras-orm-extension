<?php

namespace Zarganwar\PhpDiNextrasOrmExtension\NextrasOrmPhpDiExtension;

use Nextras\Orm\Model\IModel;
use Nextras\Orm\Repository\IRepository;

interface RepositoryFinder
{
	/**
	 * @param class-string<IModel> $modelClass
	 * @return array<string, class-string<IRepository>>
	 */
	public function findRepositories(string $modelClass): array;

}