
This is a minimal wrapper around the [Clickhouse HTTP Interface](https://clickhouse.com/docs/en/interfaces/http) for PHP. It supports sessions, select and inserts, and queries with parameters.

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
    https: false,
    user: 'default',
    password: '',
    database: 'default',
);
```

## HTTP Client

Starting from version 2.0, this library is HTTP client agnostic. By default, it uses [php-http Discovery](https://docs.php-http.org/en/latest/discovery.html) to find an HTTP client from the installed packages. You can also pass your own PSR-18 HTTP client.

```php
$clickhouse = new Clickhouse(
    httpClient: new MyCustomPsr18HttpClient(),
    
    // and also
    httpRequestFactory: new MyCustomPsr17HttpRequestFactory(),
    httpStreamFactory: new MyCustomPsr17StreamFactory(),
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

// properties
$results->rows; // int (same as $results->count())
$results->rowsBeforeLimitAtLeast; // null | int
$results->elapsedTimeSeconds; // float
$results->rowsRead; // int
$results->bytesRead; // int
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
    [
        'id' => 1, 
        'name' => 'John', 
        'age' => 42
    ]
)
```

In SQL, this would be:

```sql
INSERT INTO users (id, name, age) VALUES ({id: Int64}, {name: String}, {age: Int64})
```

### Insert multiple rows

To insert multiple rows, pass multiple arguments (arrays) at the end:

```php
$clickhouse->insert(
    'users',
    [
        'id' => 'UInt64',
        'name' => 'String',
        'age' => 'UInt8',
    ],
    ['id' => 1, 'name' => 'John', 'age' => 42],
    ['id' => 2, 'name' => 'Jane', 'age' => 37],
    ['id' => 3, 'name' => 'Bob', 'age' => 21],
)
```

### Insert Raw

Clickhouse is notably slow when inserting a large number of rows with parameters. In such cases, you can use the `insertRaw` method, which inserts rows without parameters. Be careful with SQL injection.

```php
$clickhouse->insertRaw(
    'users',
    ['id', 'created_at', 'name', 'age'],
    [
        [1, '2021-01-01 00:00:00', 'John', 30],
        [2, '2021-01-02 00:00:00', 'Jane', 25],
        [3, '2021-01-03 00:00:00', 'Doe', 35],
    ],
    asyncInsert: true,
    waitForAsyncInsert: true,
)
```

This method sets `async_insert` and `wait_for_async_insert` settings true by default. [Read more about these settings](https://clickhouse.com/docs/en/cloud/bestpractices/asynchronous-inserts).

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