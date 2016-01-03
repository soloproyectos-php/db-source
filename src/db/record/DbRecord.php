<?php
/**
 * This file is part of Soloproyectos common library.
 *
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
namespace soloproyectos\db\record;
use soloproyectos\text\Text;

/**
 * DbRecord class.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
class DbRecord implements DbRecordInterface
{
    /**
     * Database connector.
     * @var DbConnector
     */
    private $_db;
    
    /**
     * Table name.
     * @var string
     */
    private $_tableName = "";
    
    /**
     * Record.
     * @var DbRecordAbstract
     */
    private $_record = null;
    
    /**
     * Constructor.
     * 
     * @param DbConnector $db        Database connector
     * @param string      $tableName Table name
     * @param mixed|array $pk        Record ID (not required)
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
        $this->_record = Text::isEmpty($pkValue)
            ? new DbRecordInsert($db, $tableName, $pkName)
            : new DbRecordUpdate($db, $tableName, $pkName, $pkValue);
    }
    
    /**
     * Saves the current record.
     * 
     * @implement DbRecordInterface::save()
     * @return void
     */
    public function save()
    {
        $this->_record->save();
        
        // updates the internal record
        if ($this->_record instanceof DbRecordInsert) {
            $row = $this->_db->query("select last_insert_id() as id");
            $pk = $this->_record->getPrimaryKey();
            $this->_record = new DbRecordUpdate(
                $this->_db, $this->_tableName, $pk->getName(), $row["id"]
            );
        }
    }
    
    /**
     * Gets the internal record.
     * 
     * @implement DbRecordInterface::getRecord()
     * @return DbRecordAbstract
     */
    public function getInternal()
    {
        return $this->_record;
    }
    
    /**
     * Gets column value.
     * 
     * @param string $colName Column name
     * 
     * @return mixed
     */
    public function get($colName)
    {
        $col = $this->_record->regColumn($colName);
        return $col->getValue();
    }
    
    /**
     * Sets column value.
     * 
     * @param string $colName Column name
     * @param mixed  $value   Column value
     * 
     * @return void
     */
    public function set($colName, $value)
    {
        $col = $this->_record->regColumn($colName);
        $col->setValue($value);
    }
}
