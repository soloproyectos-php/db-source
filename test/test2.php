<?php
/**
 * This PHP script illustrates how to operate over several tables at the same time.
 * 
 * There's a main table, called table0, and three linked tables (table1, table2 and table3).
 * The linked tables are 'left joined' to the main table by the fields
 * table1_id, table2_id and table3_id.
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
// The following code inserts a record into table0, but also into the
// tables table1, table2 and table3. Finally, the fields table1_id, table2_id and table3_id
// are updated with the primary keys of the previous tables.
echo "### Creates a new record\n";
$r = new DbRecord($db, "table0");
$r->save([
    "title" => "Title",
    "created_at" => date("Y-m-d H:i:s"),
    "table1.title" => "Title 1",
    "table2.title" => "Title 2",
    "table3.title" => "Title 3"
]);

// fetches column values (SELECT)
// The following codes fetches the column values of 'table0', but also of the
// tables 'table1', 'table2' and 'table3'.
echo "\n### Fetches column values\n";
list($id, $title, $createdAt, $t1Title, $t2Title, $t3Title) = $r->fetch([
    "id", "title", "created_at", "table1.title", "table2.title", "table3.title"
]);
echo "id: $id, title: $title, created_at: $createdAt, ";
echo "table1.title: $t1Title, table2.title: $t2Title, table3.title: $t3Title\n";

// selects a record and prints its column values (SELECT)
// The following code fetches column values from table0, but also from table1
echo "\n### Selects a record and prints its column values\n";
$r = new DbRecord($db, "table0", $id);
list($title, $createdAt, $t1Title, $t2Title, $t3Title) = $r->fetch([
    "title", "created_at", "table1.title", "table2.title", "table3.title"
]);
echo "title: $title, created_at: $createdAt, ";
echo "table1.title: $t1Title, table2.title: $t2Title, table3.title: $t3Title\n";

// deletes the previous record (DELETE)
// The following code deletes the previous created record, but also
// the linked records (table1, table2 and table3)
echo "\n### Deletes the previous record and its linked records (table1, table2 and table3)\n";
$r->delete();
