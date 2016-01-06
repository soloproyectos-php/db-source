<?php
/**
 * This PHP script illustrates how to operate over a single table (INSERT, SELECT and DELETE).
 * 
 * For more complex examples see test2.php.
 */
header("Content-Type: text/plain; charset=utf-8");
require_once "../vendor/autoload.php";
use soloproyectos\db\DbConnector;
use soloproyectos\db\record\DbRecord;

// creates a new connector instance and prints each SQL statement (debugging)
$db = new DbConnector("test", "test", "test");
$db->addDebugListener(function ($sql) {
    echo "--$sql\n";
});

// creates a new record (INSERT)
echo "### Creates a new record\n";
$r = new DbRecord($db, "table0");
$r->save(["title" => "Title", "created_at" => date("Y-m-d H:i:s")]);

// fetches column values (SELECT)
echo "\n### Fetches column values\n";
list($id, $title, $createdAt) = $r->fetch(["id", "title", "created_at"]);
echo "id: $id, title: $title, created_at: $createdAt\n";

// selects a record and prints its column values (SELECT)
echo "\n### Selects a record and prints its column values\n";
$r = new DbRecord($db, "table0", $id);
list($title, $createdAt) = $r->fetch(["title", "created_at"]);
echo "title: $title, created_at: $createdAt\n";

// deletes the previous record (DELETE)
echo "\n### Deletes the previous record\n";
$r->delete();
