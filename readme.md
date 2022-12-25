> Cautions! This library is still in **BETA**. It is not recommended to use it in production.

This library is a minimal wrapper around the [Clickhouse HTTP Interface](https://clickhouse.com/docs/en/interfaces/http) for PHP. It supports sessions, safe select and inserts, and queries with parameters.

## Installation

```bash
composer require hyvor/clickhouse-php
```

## Connecting

```php
<?php
use Hyvor\Clickhouse\Clickhouse;

$clickhouse = new Clickhouse(
    host: 'localhost',
    port: 8123,
    user: 'default',
    password: '',
    database: 'default',
);
```

## Select

Selecting multiple rows

```php
$results = $clickhouse->select(
    'SELECT * FROM users WHERE id < {id: UInt32}',
     ['id' => 10]
);

// get rows as arrays
$results->all(); // [[1, 'John'], [2, 'Jane']]

// get the first row
$results->first(); // [1, 'John']

// get the first column of the first row
// useful for aggregations like COUNT(*)
$results->value(); // 2

// loop through the rows
foreach ($results as $row) {
    // $row is an array
}
```


## Insert

### Insert a single row

Use the `insert` method to insert a new row.

Arguments:

Argument 1: The table name
Argument 2: Key-value pairs for the [columns and values types](https://clickhouse.com/docs/en/interfaces/cli#cli-queries-with-parameters)
Argument 3...: Rows to insert

```php
$clickhouse->insert(
    'users',
    [
        'id' => 'UInt64',
        'name' => 'String',
        'age' => 'UInt8',
    ],
    [1, 'John', 42]
)
```

In SQL, this would be:

```sql
INSERT INTO users (id, name, age) VALUES ({id: Int64}, {name: String}, {age: Int64})
```

### Insert multiple rows

To insert multiple rows, pass an array of rows:

```php
$clickhouse->insert(
    'users',
    [
        'id' => 'UInt64',
        'name' => 'String',
        'age' => 'UInt8',
    ],
    [1, 'John', 42],
    [2, 'Jane', 37],
    [3, 'Bob', 21],
)
```

## Other Queries

You can run any other query with `query()`. The response is returned as JSON in Clickhouse's [JSONCompact format](https://clickhouse.com/docs/en/sql-reference/formats#jsoncompact).

```php
$clickhouse->query('DROP TABLE users');
// with params
$clickhouse->query('QUERY', ['param' => 1]);
```

## Session

Each `Hyvor\Clickhouse\Clickhouse` object creates a new session ID. You can use this to share the session between multiple requests.

```php
$clickhouse = new Clickhouse();

// example:
// by default, Clickhouse update mutations are async
// here, we set mutations to sync
$clickhouse->query('SET mutations_sync = 1');

// all queries in this session (using the same $clickhouse object) will be sync
$clickhouse->query(
    'ALTER TABLE users UPDATE name = {name: String} WHERE id = 1', 
    ['name' => 'John']
);
```