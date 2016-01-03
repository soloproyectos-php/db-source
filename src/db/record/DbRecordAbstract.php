<?php
/**
 * This file is part of Soloproyectos common library.
 *
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
namespace soloproyectos\db\record;

/**
 * DbRecordAbstract class.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
abstract class DbRecordAbstract implements DbRecordInterface
{
    /**
     * Database connector.
     * @var DbConnector
     */
    protected $db = null;
    
    /**
     * Table name.
     * @var string
     */
    protected $tableName = "";
    
    /**
     * Primary key.
     * @var DbRecordColumn
     */
    protected $primaryKey = null;
    
    /**
     * List of columns.
     * @var DbRecordColumn[]
     */
    protected $columns = [];
    
    /**
     * List of left join tables.
     * @var DbRecordLeftJoin[]
     */
    protected $leftJoins = [];
    
    /**
     * Is the record updated?
     * @var boolean
     */
    protected $isUpdated = true;
    
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
        $this->db = $db;
        $this->tableName = $tableName;
        $this->primaryKey = new DbRecordColumn($this, $pkName);
        $this->primaryKey->setValue($pkValue);
    }
    
    /**
     * Fetches column values from database.
     * 
     * @return void
     */
    abstract public function fetch();
    
    /**
     * Gets the internal record.
     * 
     * @implement DbRecordInterface::getRecord()
     * @return DbRecordAbstract
     */
    public function getInternal()
    {
        return $this;
    }
    
    /**
     * Gets the table name.
     * 
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }
    
    /**
     * Gets the primary key column.
     * 
     * @return DbRecordColumn
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    
    /**
     * Gets the columns that have changed.
     * 
     * @return DbRecordColumn[]
     */
    protected function getChangedColumns()
    {
        return array_filter(
            $this->columns,
            function ($column) {
                return $column->hasChanged();
            }
        );
    }
    
    /**
     * Is the record updated?
     * 
     * @return boolean
     */
    public function isUpdated()
    {
        return $this->isUpdated;
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
        
        $column = $table->searchColumn($colName);
        if ($column === null) {
            $column = $table->addColumn(new DbRecordColumn($table, $colName));
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
        $table = $this->searchTable($tableName, $pkName, $column->getName());
        if ($table === null) {
            $table = $this->addTable(
                new DbRecordLeftJoin(
                    new DbRecord($this->db, $tableName, [$pkName => $column->getValue()]),
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
    public function addColumn($column)
    {
        array_push($this->columns, $column);
        $this->isUpdated = false;
        return $column;
    }
    
    /**
     * Adds a 'left join' table.
     * 
     * @param DbRecordLeftJoin $leftJoin Left join table
     * 
     * @return DbRecord
     */
    public function addTable($leftJoin)
    {
        array_push($this->leftJoins, $leftJoin);
        return $leftJoin->getRecord();
    }
    
    /**
     * Searches column by name.
     * 
     * @param string $colName Column name
     * 
     * @return DbRecordColumn|null
     */
    public function searchColumn($colName)
    {
        $ret = null;
        foreach ($this->columns as $column) {
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
    public function searchTable($tableName, $pkName, $colName)
    {
        $ret = null;
        foreach ($this->leftJoins as $leftJoin) {
            $record = $leftJoin->getRecord();
            $pk = $record->getPrimaryKey();
            $column = $leftJoin->getColumn();
            if ($record->getTableName() == $tableName
                && $pk->getName() == $pkName
                && $column->getName() == $colName
            ) {
                $ret = $record;
                break;
            }
        }
        return $ret;
    }
}
