<?php

namespace Walnut\Lib\ModelMapper\Orm;

use Walnut\Lib\DbDataModel\DataModelBuilder;
use Walnut\Lib\DbOrm\RelationalStorageFactory;
use Walnut\Lib\DbQueryBuilder\Quoter\SqlQuoter;
use Walnut\Lib\ModelMapper\ModelBuilderFactory;
use Walnut\Lib\ModelMapper\ModelMapperFactory;
use Walnut\Lib\ModelMapper\ModelParserFactory;

final class OrmModelMapperFactory implements ModelMapperFactory {
	/**
	 * @param OrmModelMapperConfiguration $configuration
	 * @param DataModelBuilder $dataModelBuilder
	 * @param RelationalStorageFactory $dataModelFactory
	 * @param ModelBuilderFactory $modelBuilderFactory
	 * @param ModelParserFactory $modelParserFactory
	 * @param SqlQuoter $sqlQuoter
	 */
	public function __construct(
		private readonly OrmModelMapperConfiguration $configuration,
		private readonly DataModelBuilder $dataModelBuilder,
		private readonly RelationalStorageFactory $dataModelFactory,
		private readonly ModelBuilderFactory $modelBuilderFactory,
		private readonly ModelParserFactory $modelParserFactory,
		private readonly SqlQuoter $sqlQuoter
	) {}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return OrmModelMapper<T>
	 */
	//public function getMapper(string $model, string $ormModel, ModelParser $modelParser): OrmModelMapper {
	public function getMapper(string $className): OrmModelMapper {
		$ormModel = $this->configuration->ormModelOf($className);
		$dataModel = $this->dataModelBuilder->build($ormModel);
		return new OrmModelMapper(
			$this->dataModelFactory->getFetcher($dataModel),
			$this->dataModelFactory->getSynchronizer($dataModel),
			$this->modelBuilderFactory->getBuilder($className),
			$this->modelParserFactory->getParser($className),
			$this->sqlQuoter,
			$dataModel->part($dataModel->modelRoot->modelRoot)->keyField->name
		);
	}
}
