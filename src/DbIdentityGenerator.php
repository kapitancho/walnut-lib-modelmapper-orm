<?php

namespace Walnut\Lib\ModelMapper\Orm;

use Walnut\Lib\IdentityGenerator\IdentityGenerator;
use Walnut\Lib\DbQuery\QueryExecutor;

final class DbIdentityGenerator implements IdentityGenerator {

	private ?int $lastIdentity = null;

	/**
	 * @param QueryExecutor $queryExecutor
	 * @param string $tableName
	 * @param string $columnName
	 */
	public function __construct(
		private readonly QueryExecutor $queryExecutor,
		private readonly string $tableName,
		private readonly string $columnName
	) {}

	private function syncIdentity(): void {
		$this->lastIdentity ??= (int)$this->queryExecutor->execute(
			"SELECT MAX($this->columnName) FROM $this->tableName"
		)->singleValue();
	}

	public function generateId(): string {
		$this->syncIdentity();
		/**
		 * @var int $this->lastIdentity
		 */
		return (string)(++$this->lastIdentity);
	}
}
