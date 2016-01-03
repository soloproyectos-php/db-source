<?php
/**
 * This file is part of Soloproyectos common library.
 *
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
namespace soloproyectos\db\record;
use soloproyectos\db\Db;

/**
 * DbRecordUpdate class.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
class DbRecordInsert extends DbRecordAbstract
{
    /**
     * Constructor.
     * 
     * @param DbConnector $db        Database connector
     * @param string      $tableName Table name
     * @param string      $pkName    Primary key name
     */
    public function __construct($db, $tableName, $pkName)
    {
        parent::__construct($db, $tableName, $pkName, "");
    }
    
    /**
     * Saves the current record.
     * 
     * @implement DbRecordInterface::save()
     * @return void
     */
    public function save()
    {
        // first saves the 'left join' tables
        foreach ($this->leftJoins as $leftJoin) {
            $leftJoin->save();
        }
        
        $columns = $this->getChangedColumns();
        $this->db->exec($this->_getInsertStatement($columns));
    }
    
    /**
     * Fetches column values from database.
     * 
     * It is not possible to fetch the column values, since the record still does not exist physically
     * in the database table. So this method is actually a placebo function.
     * 
     * @implement DbRecordAbstract::fetch()
     * @return void
     */
    public function fetch()
    {
        $this->isUpdated = true;
    }
    
    /**
     * Gets the SQL INSERT statement.
     * 
     * @param DbRecordColumn $columns List of columns
     * 
     * @return string
     */
    private function _getInsertStatement($columns)
    {
        // list of column and values separated by commas
        $colNames = [];
        $colValues = [];
        foreach ($columns as $column) {
            array_push($colNames, Db::quoteId($column->getName()));
            array_push($colValues, $this->db->quote($column->getValue()));
        }
        $cols = implode(", ", $colNames);
        $vals = implode(", ", $colValues);
        
        $tableName = Db::quoteId($this->tableName);
        return "insert into $tableName($cols) values($vals)";
    }
}
