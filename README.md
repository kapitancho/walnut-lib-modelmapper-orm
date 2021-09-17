# ORM based Model Mapper
An adapter to use Relational Storage as a Model Mapper

# Example
This is a very simple data model - client.
```php
class Client {
	public function __construct(
		public /*readonly*/ string $id,
		public /*readonly*/ string $name
	) {}
}
```

A serialization/deserialization logic is required.
```php
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
```

All serializers can be aggregated into a factory class
```php
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
```

### Defining the relational model
```php
#[ModelRoot('clients')]
class ClientDbModel {
    public function __construct(
        #[Table("clients")]
        #[KeyField('id'), Fields('name')]
        public array $clients
    ) {}
}
```

Using a MySql storage
```php
$sqlQuoter = new MysqlQuoter;
$queryExecutor = new PdoQueryExecutor($connector);
$dataModelFactory = new DataModelFactory($sqlQuoter, $queryExecutor);

$serializerFactory = new MapperFactory;

//Mapping between the models and their storage keys:
$configuration = new OrmModelMapperConfiguration(
    [Client::class => ClientDbModel::class]
);

//Mapper factory covering all models
$modelMapperFactory = new OrmModelMapperFactory(
    $configuration,
    new DataModelBuilder,
    $dataModelFactory,
    $serializerFactory,
    $serializerFactory,
    $sqlQuoter
);

//Take the mapper for the Client model
$mapper = $modelMapperFactory->getMapper(Client::class);

//Prepare two records
$firstClient = new Client('cl-1', 'Client 1');
$secondClient = new Client('cl-2', 'Client 2');

//Using the storage
$mapper->all(); //0 entries

$mapper->store($firstClient->id, $firstClient);
$mapper->store($secondClient->id, $secondClient);

$mapper->all(); //2 entries

$updatedSecondClient = new Client('cl-2', 'Client 2 new name');

$mapper->store($updatedSecondClient->id, $updatedSecondClient);
$mapper->all(); //2 entries

$mapper->byId($firstClient->id)->name; //Client 1
$mapper->byId($updatedSecondClient->id)->name; //Client 2 new name

$mapper->remove($firstClient->id);
$mapper->byId($firstClient->id); //null

$mapper->all(); //1 entry

```
