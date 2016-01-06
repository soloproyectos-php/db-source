<?php
/**
 * This PHP script illustrates the 'column path' concept. A 'column path' is a way to access columns
 * from 'linked' or 'left joined' tables.
 * 
 * In this example table2 is linked to table1 which is linked to table0.
 * 
 * For a triple somersault see test5.php.
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
// the following code operates over three tables: table0, table1 and table2
// table1 is linked to table0 (table2[id = table1.table2_id])
// and table2 is linked to the table1 (table2[id = table1.table2_id])
echo "### Creates a new record\n";
$r = new DbRecord($db, "table0");
$r->save([
    "title" => "Title",
    "created_at" => date("Y-m-d H:i:s"),
    // a shorthand of 'table1[id = table1_id].title'
    "table1.title" => "Title 1",
    // a shorthand of 'table2[id = table1.table2_id].title'
    "table2[table1.table2_id].title" => "Title 12"
]);

// And now prints table2[table1.table2_id].title
// table2 is linked to table1 by 'table2[id = table1.table2_id]'
// table1 is linked to table0 by 'table0[id = table1_id]'
echo "\n### General example: table2[id = table1.table2_id].title\n";
list($table2Title) = $r->fetch(["table2[id = table1.table2_id].title"]);
echo "table2.title: $table2Title\n";

// AS THE PREVIOUS EXAMPLE IS VERY COMMON, it can be written as follows:
// note that id and <table>_id can be ommmited
echo "\n### Shorthand example: table2[table1.table2_id].title\n";
list($table2Title) = $r->fetch(["table2[table1.table2_id].title"]);
echo "table2.title: $table2Title\n";
