<?php

namespace Walnut\Lib\ModelMapper\Orm;

use Walnut\Lib\DbOrm\RelationalStorageFetcher;
use Walnut\Lib\DbOrm\RelationalStorageSynchronizer;
use Walnut\Lib\DbQueryBuilder\Expression\RawExpression;
use Walnut\Lib\DbQueryBuilder\QueryPart\QueryFilter;
use Walnut\Lib\DbQueryBuilder\Quoter\SqlQuoter;
use Walnut\Lib\ModelMapper\ModelBuilder;
use Walnut\Lib\ModelMapper\ModelMapper;
use Walnut\Lib\ModelMapper\ModelParser;

/**
 * @template T
 * @implements ModelMapper<T>
 */
final class OrmModelMapper implements ModelMapper {
	/**
	 * @param RelationalStorageFetcher $fetcher
	 * @param RelationalStorageSynchronizer $synchronizer
	 * @param ModelBuilder<T> $modelBuilder
	 * @param ModelParser<T> $modelParser
	 * @param SqlQuoter $sqlQuoter
	 * @param string $keyColumnName
	 */
	public function __construct(
		private /*readonly*/ RelationalStorageFetcher $fetcher,
		private /*readonly*/ RelationalStorageSynchronizer $synchronizer,
		private /*readonly*/ ModelBuilder $modelBuilder,
		private /*readonly*/ ModelParser $modelParser,
		private /*readonly*/ SqlQuoter $sqlQuoter,
		private /*readonly*/ string $keyColumnName,
	) {}

	private function fetch(string $entryId): ?array { //TODO
		/**
		 * @var ?array
		 */
		return $this->fetcher->fetchData(new QueryFilter(
			new RawExpression(
				$this->sqlQuoter->quoteIdentifier($this->keyColumnName) .
				" = " .
				$this->sqlQuoter->quoteValue($entryId))
		))[0] ?? null;
	}

	/**
	 * @param string $entryId
	 * @return T|null
	 */
	public function byId(string $entryId): ?object {
		$entry = $this->fetch($entryId);
		return $entry ? $this->modelBuilder->build($entry) : null;
	}

	/**
	 * @return T[]
	 */
	public function all(): array {
		return array_map(fn(array $entry): mixed =>
			$this->modelBuilder->build($entry),
			$this->fetcher->fetchData(new QueryFilter(
				new RawExpression("1")
			))
		);
	}

	/**
	 * @param T $entry
	 */
	public function store(string $entryId, object $entry): void {
		$oldEntry = $this->fetch($entryId);
		$this->synchronizer->synchronizeData(
			$oldEntry ? [$oldEntry] : [],
			[$this->modelParser->parse($entry)]
		);
	}

	/**
	 * @param string $entryId
	 */
	public function remove(string $entryId): void {
		$oldEntry = $this->fetch($entryId);
		if ($oldEntry) {
			$this->synchronizer->synchronizeData([$oldEntry], []);
		}
	}

	public function byCondition(callable $conditionChecker): array {
		return array_values(
			array_filter(
				$this->all(),
				/**
			      * @param T $item
			      */
				static fn(object $item) => $conditionChecker($item)
			)
		);
	}

	public function exists(string $entryId): bool {
		return (bool)$this->fetcher->fetchData(new QueryFilter(
			new RawExpression(
				$this->sqlQuoter->quoteIdentifier($this->keyColumnName) .
				" = " .
				$this->sqlQuoter->quoteValue($entryId))
		));
	}
}
