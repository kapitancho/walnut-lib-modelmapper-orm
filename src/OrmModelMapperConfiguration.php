<?php

namespace Walnut\Lib\ModelMapper\Orm;

final class OrmModelMapperConfiguration {
	/**
	 * @param class-string[] $ormModels
	 */
	public function __construct(
		private /*readonly*/ array $ormModels
	) {}

	/**
	 * @param class-string $model
	 * @return class-string
	 */
	public function ormModelOf(string $model): string {
		return $this->ormModels[$model] ?? throw new \RuntimeException(
			sprintf("ORM model for %s is missing", $model));
	}

}
