<?php
/**
 * This PHP script continues the previous test4.php script.
 * 
 * Triple somersault:
 * In this example table3 is linked to table2 which is linked to table1 which is linked to table0.
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
// the following code operates over four tables: table0, table1, table2 and table3
// table1 is linked to table0 (table2[id = table1.table2_id])
// and table2 is linked to table1 (table2[id = table1.table2_id])
// and table3 is linked to table2 (table3[id = table2.table3_id])
echo "### Creates a new record\n";
$r = new DbRecord($db, "table0");
$r->save([
    "title" => "Title",
    "created_at" => date("Y-m-d H:i:s"),
    // a shorthand of 'table1[id = table1_id].title'
    "table1.title" => "Title 1",
    // a shorthand of 'table2[id = table1.table2_id].title'
    "table2[table1.table2_id].title" => "Title 12",
    // a shorthand of 'table3[id = table2[id = table1.table2_id].table3_id]'
    "table3[table2[table1.table2_id].table3_id].title" => "Title 123"
]);

// And now prints table titles
// table3 is linked to table2 by 'table3[id = table2.table3_id]'
// table2 is linked to table1 by 'table2[id = table1.table2_id]'
// table1 is linked to table0 by 'table0[id = table1_id]'
echo "\n### General example\n";
list($table1Title, $table2Title, $table3Title) = $r->fetch([
    "table1[id = table1_id].title",
    "table2[id = table1[id = table1_id].table2_id].title",
    "table3[id = table2[id = table1.table2_id].table3_id].title"
]);
echo "table1.title: $table1Title, table2.title: $table2Title, table3.title: $table3Title\n";

// AS THE PREVIOUS EXAMPLE IS VERY COMMON, it can be written as follows:
// note that id and <table>_id can be ommited
echo "\n### Shorthand example\n";
list($table1Title, $table2Title, $table3Title) = $r->fetch([
    "table1.title",
    "table2[table1.table2_id].title",
    "table3[table2[table1.table2_id].table3_id].title"
]);
echo "table1.title: $table1Title, table2.title: $table2Title, table3.title: $table3Title\n";
