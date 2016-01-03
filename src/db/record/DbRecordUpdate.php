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
class DbRecordUpdate extends DbRecordAbstract
{
    /**
     * Constructor.
     * 
     * @param DbConnector $db        Database connector
     * @param string      $tableName Table name
     * @param string      $pkName    Primary key name
     * @param mixed       $pkValue   Primary key value
     */
    public function __construct($db, $tableName, $pkName, $pkValue)
    {
        parent::__construct($db, $tableName, $pkName, $pkValue);
        
        $sql = $this->_getShowColumnsStatement();
        $rows = $this->db->query($sql);
        foreach ($rows as $row) {
            $this->regColumn($row["Field"]);
        }
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
        if (count($columns) > 0) {
            $this->db->exec($this->_getUpdateStatement($columns));
            
            // resets columns
            foreach ($columns as $column) {
                $column->reset();
            }
            
            $this->isUpdated = false;
        }
    }
    
    /**
     * Fetches column values from database.
     * 
     * @implement DbRecordAbstract::fetch()
     * @return void
     */
    public function fetch()
    {
        // gets the columns that haven't changed
        $columns = array_diff($this->columns, $this->getChangedColumns());
        
        // fills columns
        if (count($columns) > 0) {
            $row = $this->db->query($this->_getSelectStatement($columns));
            foreach ($columns as $column) {
                $column->setOriginalValue($row[$column->getName()]);
            }
        }
        
        $this->isUpdated = true;
    }
    
    /**
     * Gets the SQL SELECT statement.
     * 
     * @param DbRecordColumn $columns List of columns
     * 
     * @return string
     */
    private function _getSelectStatement($columns)
    {
        // list of column names separated by commas
        $cols = implode(
            ", ",
            array_map(
                function ($column) {
                    return Db::quoteId($column->getName());
                },
                $columns
            )
        );
        
        $tableName = Db::quoteId($this->tableName);
        $pkName = Db::quoteId($this->primaryKey->getName());
        $pkValue = $this->db->quote($this->primaryKey->getValue());
        return "select $cols from $tableName where $pkName = $pkValue";
    }
    
    /**
     * Gets the SQL UPDATE statement.
     * 
     * @param DbRecordColumn $columns List of columns
     * 
     * @return string
     */
    private function _getUpdateStatement($columns)
    {
        // list of column assignments separated by commas
        $colValues = implode(
            ", ",
            array_map(
                function ($column) {
                    $colName = Db::quoteId($column->getName());
                    $colValue = $this->db->quote($column->getValue());
                    return "$colName = $colValue";
                },
                $columns
            )
        );
        
        $tableName = Db::quoteId($this->tableName);
        $pkName = Db::quoteId($this->primaryKey->getName());
        $pkValue = $this->db->quote($this->primaryKey->getValue());
        return "update $tableName set $colValues where $pkName = $pkValue"; 
    }
    
    /**
     * Gets the SQL SHOW COLUMNS statement.
     * 
     * @return string
     */
    private function _getShowColumnsStatement()
    {
        $tableName = Db::quoteId($this->tableName);
        return "show columns from $tableName";
    }
}
