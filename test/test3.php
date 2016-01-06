<?php
/**
 * This PHP script illustrates the 'column path' concept.
 * 
 * A 'column path' is a way to access columns from linked tables (left joined tables).
 * 
 * For more complex examples see test4.php.
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

// First of all, let's create a new record
// The following code creates a new record and also a record for table1 (left joined table)
echo "### Creates a new record\n";
$r = new DbRecord($db, "table0");
$r->save([
    "title" => "Title",
    "created_at" => date("Y-m-d H:i:s"),
    "table1.title" => "Title 1"
]);

// And now prints a 'left joined' or 'linked' table column
// table1 is the 'left joined' or 'linked' table
// id is a column of table1 (not necessarily the primary key)
// table1_id is a column of table0
// title is a column of table1 (the column to print)
echo "\n### General example: table1[id = table1_id].title\n";
list($table1Title) = $r->fetch(["table1[id = table1_id].title"]);
echo "table1.title: $table1Title\n";

// AS THE PREVIOUS EXAMPLE IS VERY COMMON, it can be written as follows:
// note that 'id' and 'table1_id' have been omitted
echo "\n### Shorthand example: table1.title\n";
list($table1Title) = $r->fetch(["table1.title"]);
echo "table1.title: $table1Title\n";
