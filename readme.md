[BETA]

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
$results->rows(); // [[1, 'John'], [2, 'Jane']]

// get the first row
$results->first(); // [1, 'John']

// get the first column of the first row
// useful for aggregations like COUNT(*)
$results->value(); // 2
```


## Insert

### Insert a single row

To prevent SQL injection, all data is passed as parameters. Type conversions are done automatically as explained [below](#type-conversion).

```php
$clickhouse->insert('users', [
    'id' => 1,
    'name' => 'John',
    'age' => 42,
]);
```

In SQL, this would be:

```sql
INSERT INTO users (id, name, age) VALUES ({id: Int64}, {name: String}, {age: Int64})
```

If you need to change the parameter type, wrap the value with a `Type`:

```php
use Hyvor\Clickhouse\Types\UInt64;

$clickhouse->insert('users', [
    'id' => UInt64::from(1),
    'name' => Nullable::from(String::from('John')),
    'age' => UInt64::from(42),
]);
```

In this example, SQL would be:

```sql
INSERT INTO users (id, name) VALUES ({id: UInt64}, {name: Nullable(String)}, {age: UInt64})
```

### Insert multiple rows

To insert multiple rows, pass an array of rows:

```php
$clickhouse->insert('users', [
    [
        'id' => 1,
        'name' => 'John',
        'age' => 42,
    ],
    [
        'id' => 2,
        'name' => 'Jane',
        'age' => 37,
    ],
]);
```

## Other Queries

You can run any other query with `query()`:

```php
$clickhouse->query('DROP TABLE users');
// with params
$clickhouse->query('QUERY', ['param' => 1]);
```

## Session

Each `Hyvor\Clickhouse\Clickhouse` object creates a new session ID. You can use this to share the session between multiple requests.

```php
$clickhouse = new Clickhouse();

// by default, Clickhouse update mutations are async
// here, we set mutations to sync
$clickhouse->query('SET mutations_sync = 1');

// all queries in this session (using the same $clickhouse object) will be sync
$clickhouse->query(
    'ALTER TABLE users UPDATE name = {name: String} WHERE id = 1', 
    ['name' => 'John']
);
```

```php

## Type conversion

Clickhouse has a [very flexible type system](https://clickhouse.com/docs/en/data_types/). This library tries to convert PHP types to Clickhouse types automatically. The following table shows the conversion rules:

PHP type | Clickhouse type
---------|----------------
`null` | `Nullable(Nothing)`
`bool` | `Boolean`
`int` | `Int64`
`float` | `Float64`
`string` | `String`
`DateTimeInterface` | `DateTime`