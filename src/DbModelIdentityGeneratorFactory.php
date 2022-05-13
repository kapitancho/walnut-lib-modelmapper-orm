<?php

namespace Walnut\Lib\ModelMapper\Orm;

use Walnut\Lib\DbDataModel\DataModelBuilder;
use Walnut\Lib\DbOrm\RelationalStorageFactory;
use Walnut\Lib\DbQuery\QueryExecutor;
use Walnut\Lib\DbQueryBuilder\Quoter\SqlQuoter;
use Walnut\Lib\IdentityGenerator\IdentityGenerator;
use Walnut\Lib\ModelMapper\MappingNotAvailable;
use Walnut\Lib\ModelMapper\ModelBuilderFactory;
use Walnut\Lib\ModelMapper\ModelIdentityGeneratorFactory;
use Walnut\Lib\ModelMapper\ModelMapperFactory;
use Walnut\Lib\ModelMapper\ModelParserFactory;

final class DbModelIdentityGeneratorFactory implements ModelIdentityGeneratorFactory {
	/**
	 * @param OrmModelMapperConfiguration $configuration
	 * @param DataModelBuilder $dataModelBuilder
	 * @param QueryExecutor $queryExecutor
	 */
	public function __construct(
		private readonly OrmModelMapperConfiguration $configuration,
		private readonly DataModelBuilder $dataModelBuilder,
		private readonly QueryExecutor $queryExecutor,
	) {}

	public function getIdentityGenerator(string $className): IdentityGenerator {
		$ormModel = $this->configuration->ormModelOf($className);
		$dataModel = $this->dataModelBuilder->build($ormModel);
		$root = $dataModel->part($dataModel->modelRoot->modelRoot);
		return new DbIdentityGenerator(
			$this->queryExecutor,
			$root->table->tableName,
			$root->keyField->name
		);
	}
}
