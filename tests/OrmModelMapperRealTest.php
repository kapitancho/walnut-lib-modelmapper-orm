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
use Walnut\Lib\ModelMapper\Orm\OrmModelMapperConfiguration;
use Walnut\Lib\ModelMapper\Orm\OrmModelMapperFactory;

class Client {
	public function __construct(
		public /*readonly*/ string $id,
		public /*readonly*/ string $name
	) {}
}

#[ModelRoot('clients')]
class ClientDbModel {
    public function __construct(
        #[Table("clients")]
        #[KeyField('id'), Fields('name')]
        public array $clients
    ) {}
}


/**
 * @implements ModelBuilder<Client>
 * @implements ModelParser<Client>
 */
final class ClientSerializer implements ModelBuilder, ModelParser {

	/**
	 * @param array $source
	 * @return Client
	 */
	public function build(array $source): object {
		return new Client(
			$source['id'] ?? '',
			$source['name'] ?? ''
		);
	}

	/**
	 * @param Client $source
	 * @return array
	 */
	public function parse(object $source): array {
		return [
			'id' => $source->id,
			'name' => $source->name
		];
	}
}

final class MapperFactory implements ModelBuilderFactory, ModelParserFactory {

	/**
	 * @param string $className
	 * @return ModelBuilder&ModelParser
	 */
	private function getSerializer(string $className): object /*ModelBuilder&ModelParser*/ {
		return match($className) {
			Client::class => new ClientSerializer,
			default => throw new RuntimeException("Unknown class $className")
		};
	}

	public function getBuilder(string $className): ModelBuilder {
		return $this->getSerializer($className);
	}

	public function getParser(string $className): ModelParser {
		return $this->getSerializer($className);
	}

}

class OrmModelMapperRealTest extends TestCase {

	public function testReal(): void {
		$configuration = new OrmModelMapperConfiguration(
		    [Client::class => ClientDbModel::class]
		);
		$sqlQuoter = new SqliteQuoter;
		$connector = new PdoConnector('sqlite::memory:', '', '');
		$connector->getConnection()->exec("CREATE TABLE clients (id integer, name varchar(255))");
		$queryExecutor = new PdoQueryExecutor($connector);
		$serializerFactory = new MapperFactory;
		$dataModelFactory = new DataModelFactory($sqlQuoter, $queryExecutor);

		$modelMapperFactory = new OrmModelMapperFactory(
			$configuration,
			new DataModelBuilder,
			$dataModelFactory,
			$serializerFactory,
			$serializerFactory,
			$sqlQuoter
		);

		$mapper = $modelMapperFactory->getMapper(Client::class);

		$firstClient = new Client('cl-1', 'Client 1');
		$secondClient = new Client('cl-2', 'Client 2');

		$this->assertCount(0, $mapper->all());

		$mapper->store($firstClient->id, $firstClient);
		$this->assertCount(1, $mapper->all());

		$mapper->store($secondClient->id, $secondClient);
		$this->assertCount(2, $mapper->all());

		$updatedSecondClient = new Client('cl-2', 'Client 2 new name');

		$mapper->store($updatedSecondClient->id, $updatedSecondClient);
		$this->assertCount(2, $mapper->all());
		$this->assertCount(1,  $mapper->byCondition(
			fn(object $target): bool => $target->id === 'cl-1'
		));

		$this->assertTrue( $mapper->exists($firstClient->id));
		$this->assertNotNull($mapper->byId($firstClient->id));
		$this->assertInstanceOf(Client::class, $mapper->byId($firstClient->id));
		$this->assertEquals('Client 1', $mapper->byId($firstClient->id)->name);
		$this->assertEquals('Client 2 new name', $mapper->byId($updatedSecondClient->id)->name);
		$mapper->remove($firstClient->id);
		$this->assertNull($mapper->byId($firstClient->id));
		$this->assertFalse( $mapper->exists($firstClient->id));
		$this->assertCount(1, $mapper->all());
	}

}

