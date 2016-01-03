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
 * DbRecordColumn class.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db-record/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db-record
 */
class DbRecordColumn
{
    /**
     * Parent record.
     * @var DbRecord
     */
    private $_record = null;
    
    /**
     * Column name.
     * @var string
     */
    private $_name = "";
    
    /**
     * Original value from database.
     * @var mixed
     */
    private $_originalValue = null;
    
    /**
     * Column value.
     * @var mixed
     */
    private $_value = null;
    
    /**
     * Is the column value updated?
     * @var boolean
     */
    private $_hasChanged = false;
    
    /**
     * Creates an instance.
     * 
     * @param DbRecord $record Record
     * @param string   $name   Column name
     */
    public function __construct($record, $name)
    {
        $this->_record = $record;
        $this->_name = $name;
    }
    
    /**
     * Gets the column name.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * Has the column changed?
     * 
     * @return boolean
     */
    public function hasChanged()
    {
        return $this->_hasChanged;
    }
    
    /**
     * Gets the column value.
     * 
     * @return mixed
     */
    public function getValue()
    {
        return $this->_hasChanged? $this->_value: $this->getOriginalValue();
    }
    
    /**
     * Sets the column value.
     * 
     * @param mixed $value Column value
     * 
     * @return void
     */
    public function setValue($value)
    {
        $this->_value = $value;
        $this->_hasChanged = true;
    }
    
    /**
     * Gets original value from database.
     * 
     * @return mixed
     */
    public function getOriginalValue()
    {
        if (!$this->_record->isUpdated()) {
            $this->_record->fetch();
        }
        return $this->_originalValue;
    }
    
    /**
     * Sets original database value.
     * 
     * @param mixed $value Database value
     * 
     * @return void
     */
    public function setOriginalValue($value)
    {
        $this->_originalValue = $value;
        $this->_value = null;
        $this->_hasChanged = false;
    }
    
    /**
     * Resets the columns to its original state.
     * 
     * @return void
     */
    public function reset()
    {
        $this->_originalValue = null;
        $this->_value = null;
        $this->_hasChanged = false;
    }
    
    /**
     * Gets a string representation of the column.
     * 
     * Actually this method returns the name of the column.
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->_name;
    }
}
