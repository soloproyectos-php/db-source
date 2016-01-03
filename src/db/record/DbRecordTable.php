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
 * DbRecordTable class.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
class DbRecordTable
{
    /**
     * Database record.
     * @var DbRecord
     */
    private $_record;
    
    /**
     * Table column.
     * @var DbRecordColumn
     */
    private $_column;
    
    /**
     * Constructor.
     * 
     * @param DbRecord       $record Database record
     * @param DbRecordColumn $column Table column
     */
    public function __construct($record, $column)
    {
        $this->_record = $record;
        $this->_column = $column;
    }
    
    /**
     * Gets the record.
     * 
     * @return DbRecord
     */
    public function getRecord()
    {
        return $this->_record;
    }
    
    /**
     * Gets the table column.
     * 
     * @return DbRecordColumn
     */
    public function getColumn()
    {
        return $this->_column;
    }
    
    /**
     * Saves the record and updates the column index.
     * 
     * @return void
     */
    public function save()
    {
        $this->_record->save();
        $pk = $this->_record->getPrimaryKey();
        $this->_column->setValue($pk->getValue());
    }
}
