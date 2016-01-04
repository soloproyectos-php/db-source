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
use soloproyectos\text\Text;

/**
 * DbRecord class.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
class DbRecord
{
    /**
     * Database connector.
     * @var DbConnector
     */
    private $_db = null;
    
    /**
     * Table name.
     * @var string
     */
    private $_tableName = "";
    
    /**
     * Primary key.
     * @var DbRecordColumn
     */
    private $_primaryKey = null;
    
    /**
     * List of columns.
     * @var DbRecordColumn[]
     */
    private $_columns = [];
    
    /**
     * List of left join tables.
     * @var DbRecordTable[]
     */
    private $_tables = [];
    
    /**
     * Is the record updated?
     * @var boolean
     */
    private $_isUpdated = true;
    
    /**
     * Constructor.
     * 
     * @param DbConnector $db        Database connector
     * @param string      $tableName Table name
     * @param mixed|array $pk        Primary key (not required)
     */
    public function __construct($db, $tableName, $pk = ["id" => ""])
    {
        // gets the primary key and value
        $pkName = "";
        $pkValue = "";
        if (!is_array($pk)) {
            $pk = ["id" => "$pk"];
        }
        foreach ($pk as $key => $value) {
            $pkName = "$key";
            $pkValue = "$value";
            break;
        }
        
        $this->_db = $db;
        $this->_tableName = $tableName;
        $this->_primaryKey = new DbRecordColumn($this, $pkName);
        if (!Text::isEmpty($pkValue)) {
            $this->_primaryKey->setValue($pkValue);
        }
    }
    
    /**
     * Gets the primary key column.
     * 
     * @return DbRecordColumn
     */
    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }
    
    /**
     * Is the record updated?
     * 
     * @return boolean
     */
    public function isUpdated()
    {
        return $this->_isUpdated;
    }
    
    /**
     * Saves the current record.
     * 
     * @param array $colVals Column values
     * 
     * @return void
     */
    public function save($colVals = [])
    {
        $ret = [];
        
        // method overloading
        if (func_num_args() > 1) {
            $colPath = func_get_arg(0);
            $colVal = func_get_arg(1);
            $colVals = [$colPath => $colVal];
        }
        
        if (count($colVals) > 0) {
            // registers columns and saves
            $this->_tables = [];
            $this->_columns = [];
            foreach ($colVals as $colPath => $colVal) {
                $col = $this->regColumn($colPath);
                $col->setValue($colVal);
            }
            $this->save();
        } else {
            // first saves the 'left join' tables
            foreach ($this->_tables as $table) {
                $table->save();
            }
            
            $columns = $this->_getChangedColumns();
            if ($this->_primaryKey->hasChanged()) {
                // update
                if (count($columns) > 0) {
                    $this->_db->exec($this->_getUpdateStatement($columns));
                }
            } else {
                // insert
                $this->_db->exec($this->_getInsertStatement($columns));
                $row = $this->_db->query("select last_insert_id() as id");
                $this->_primaryKey->setValue($row["id"]);
            }
            
            // resets columns
            foreach ($columns as $column) {
                $column->reset();
            }
            $this->_isUpdated = false;
        }
        
        return $ret;
    }
    
    /**
     * Fetches column values from database.
     * 
     * @param array|mixed $colPaths Column paths
     * 
     * @return mixed|mixed[]
     */
    public function fetch($colPaths = [])
    {
        $ret = [];
        
        // method overloading
        $isArrayColPaths = is_array($colPaths);
        if (!$isArrayColPaths) {
            $colPaths = [$colPaths];
        }
        
        if (count($colPaths) > 0) {
            // registers columns and fetches values
            $this->_tables = [];
            $this->_columns = [];
            $cols = [];
            foreach ($colPaths as $colPath) {
                array_push($cols, $this->regColumn($colPath));
            }
            foreach ($cols as $col) {
                array_push($ret, $col->getValue());
            }
            if (!$isArrayColPaths) {
                $ret = $ret[0];
            }
        } else {
            if ($this->_primaryKey->hasChanged()) {
                // gets the columns that haven't changed
                $columns = array_diff($this->_columns, $this->_getChangedColumns());
                
                // fills columns
                if (count($columns) > 0) {
                    $row = $this->_db->query($this->_getSelectStatement($columns));
                    foreach ($columns as $column) {
                        $column->setDbValue($row[$column->getName()]);
                    }
                }
            }
            $this->_isUpdated = true;
        }
        
        return $ret;
    }
    
    /**
     * Registers a column.
     * 
     * This method adds a column only if not already added.
     * 
     * @param string $colPath Column path
     * 
     * @return DbRecordColumn
     */
    public function regColumn($colPath)
    {
        $table = $this;
        $colName = $colPath;
        
        $pos = strrpos($colPath, ".");
        if ($pos !== false) {
            $tableExp = trim(substr($colPath, 0, $pos));
            $colName = trim(substr($colPath, $pos + 1));
            $table = $this->regTable($tableExp);
        }
        
        $column = $table->_searchColumn($colName);
        if ($column === null) {
            $column = $table->_addColumn(new DbRecordColumn($table, $colName));
        }

        return $column;
    }
    
    /**
     * Registers a 'left join' table.
     * 
     * @param string $tablePath Table path
     * 
     * @return DbRecord
     */
    public function regTable($tablePath)
    {
        $tableName = $tablePath;
        $pkName = "id";
        $colName = "{$tableName}_id";

        if (preg_match("/(.*)\s*\[(.*)\]$/U", $tablePath, $matches)) {
            $tableName = $matches[1];
            $colName = trim($matches[2]);

            $pos = strpos($colName, "=");
            if ($pos !== false) {
                $pkName = trim(substr($colName, 0, $pos));
                $colName = trim(substr($colName, $pos + 1));
            }
        }

        $column = $this->regColumn($colName);
        $table = $this->_searchTable($tableName, $pkName, $column->getName());
        if ($table === null) {
            $table = $this->_addTable(
                new DbRecordTable(
                    new DbRecord($this->_db, $tableName, $pkName, $column->getValue()),
                    $column
                )
            );
        }

        return $table;
    }
    
    /**
     * Adds colum.
     * 
     * @param DbRecordColumn $column Column
     * 
     * @return DbRecordColumn
     */
    private function _addColumn($column)
    {
        array_push($this->_columns, $column);
        $this->_isUpdated = false;
        return $column;
    }
    
    /**
     * Adds a 'left join' table.
     * 
     * @param DbRecordTable $table Left join table
     * 
     * @return DbRecord
     */
    private function _addTable($table)
    {
        array_push($this->_tables, $table);
        return $table->getRecord();
    }
    
    /**
     * Searches column by name.
     * 
     * @param string $colName Column name
     * 
     * @return DbRecordColumn|null
     */
    private function _searchColumn($colName)
    {
        $ret = null;
        foreach ($this->_columns as $column) {
            if ($column->getName() == $colName) {
                $ret = $column;
                break;
            }
        }
        return $ret;
    }
    
    /**
     * Searches a 'left join' table by its name and column index.
     * 
     * @param string $tableName Table name
     * @param string $pkName    Primary key name
     * @param string $colName   Column index name
     * 
     * @return DbRecord
     */
    private function _searchTable($tableName, $pkName, $colName)
    {
        $ret = null;
        foreach ($this->_tables as $table) {
            $record = $table->getRecord();
            $pk = $record->getPrimaryKey();
            $column = $table->getColumn();
            if ($record->_tableName == $tableName
                && $pk->getName() == $pkName
                && $column->getName() == $colName
            ) {
                $ret = $record;
                break;
            }
        }
        return $ret;
    }
    
    /**
     * Gets the columns that have changed.
     * 
     * @return DbRecordColumn[]
     */
    private function _getChangedColumns()
    {
        return array_filter(
            $this->_columns,
            function ($column) {
                return $column->hasChanged();
            }
        );
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
        
        $tableName = Db::quoteId($this->_tableName);
        $pkName = Db::quoteId($this->_primaryKey->getName());
        $pkValue = $this->_db->quote($this->_primaryKey->getValue());
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
                    $colValue = $this->_db->quote($column->getValue());
                    return "$colName = $colValue";
                },
                $columns
            )
        );
        
        $tableName = Db::quoteId($this->_tableName);
        $pkName = Db::quoteId($this->_primaryKey->getName());
        $pkValue = $this->_db->quote($this->_primaryKey->getValue());
        return "update $tableName set $colValues where $pkName = $pkValue"; 
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
            array_push($colValues, $this->_db->quote($column->getValue()));
        }
        $cols = implode(", ", $colNames);
        $vals = implode(", ", $colValues);
        
        $tableName = Db::quoteId($this->_tableName);
        return "insert into $tableName($cols) values($vals)";
    }
}
