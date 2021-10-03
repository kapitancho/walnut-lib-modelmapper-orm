<?php

use PHPUnit\Framework\TestCase;
use Walnut\Lib\DbDataModel\Attribute\Fields;
use Walnut\Lib\DbDataModel\Attribute\KeyField;
use Walnut\Lib\DbDataModel\Attribute\ModelRoot;
use Walnut\Lib\DbDataModel\Attribute\Table;
use Walnut\Lib\DbDataModel\DataModelBuilder;
use Walnut\Lib\DbOrm\DataModelFactory;
use Walnut\Lib\DbQuery\Pdo\PdoConnector;
use Walnut\Lib\DbQuery\Pdo\PdoQueryExecutor;
use Walnut\Lib\DbQueryBuilder\Quoter\SqliteQuoter;
use Walnut\Lib\ModelMapper\ModelBuilder;
use Walnut\Lib\ModelMapper\ModelBuilderFactory;
use Walnut\Lib\ModelMapper\ModelParser;
use Walnut\Lib\ModelMapper\ModelParserFactory;
use Walnut\Lib\ModelMapper\Orm\DbModelIdentityGeneratorFactory;
use Walnut\Lib\ModelMapper\Orm\OrmModelMapperConfiguration;
use Walnut\Lib\ModelMapper\Orm\OrmModelMapperFactory;

class DbIdentityGeneratorTest extends TestCase {

	public function testOk(): void {
		$configuration = new OrmModelMapperConfiguration(
		    [Client::class => ClientDbModel::class]
		);
		$sqlQuoter = new SqliteQuoter;
		$connector = new PdoConnector('sqlite::memory:', '', '');
		$connector->getConnection()->exec("CREATE TABLE clients (id integer, name varchar(255))");
		$queryExecutor = new PdoQueryExecutor($connector);

		$modelIdentityGeneratorFactory = new DbModelIdentityGeneratorFactory(
			$configuration,
			new DataModelBuilder,
			$queryExecutor
		);

		$generator = $modelIdentityGeneratorFactory->getIdentityGenerator(Client::class);

		$this->assertEquals('1', $generator->generateId());
	}

}

