# db

A simple MySQL library for PHP (PDO wrapper)

## Installation

Install the latest version with

```bash
$ composer require hengeb/db
```

## Basic Usage

```php
<?php

use Hengeb\Db\Db;

$db = new Db([
    "host" => "example.org",
    "port" => 3306,
    "database" => "my_database",
    "user" => "johndoe",
    "password" => "secret"
]);

// query() and execute() return insert id if a row was inserted
$id = $db->query("INSERT INTO contacts SET name=:name, phone=:phone", [
    "name" => "Jane",
    "phone" => "555-123",
])->getInsertId();
// e.g. $id === 4

// get single value
$phone = $db->query("SELECT phone FROM contacts WHERE id=:id", ["id" => $id])->get();
// $phone === "555-123"

// get single key for multiple rows
$allNames = $db->query("SELECT name FROM contacts ORDER BY name")->getColumn();
// $allNames === ["Alice", "Bob", "Jane", "Joe"]

// get single row
$contact = $db->query("SELECT name, phone FROM contacts WHERE id=:id", ["id" => $id])->getRow();
// $contact === ["name" => "Jane", "phone" => "555-123"]

// get associative array with the datra
$contacts = $db->query("SELECT name, phone FROM contacts ORDER BY name")->getAll();
// $contacts === [["name" => "Alice", "phone" => "555-987"], ...]

// reuse prepared statement
$names = [];
$statement = $db->prepare("SELECT name FROM contacts WHERE id=:id");
for ([1, 2, 3, 4] as $id) {
    $names[] = $statement->bind(["id" => $id])->execute()->get();
}
// $names === ["Bob", "Alice", "Joe", "Jane"]

// transaction:
$db->beginTransaction();
$db->query("INSERT INTO contacts SET name=:name, phone=:phone", [
    "name" => "Claire",
    "phone" => "555-222",
]);
$db->commit(); // or $db->rollback()
```

### Author

Henrik Gebauer - <code@henrik-gebauer.de> - <https://www.henrik-gebauer.de>

### License

This software is licensed under the MIT License - see the [LICENSE](LICENSE) file for details
