<?php

class ActiveRecord extends CActiveRecord
{
    protected $columns = array();
    protected $columnsArray = array();
    protected $dynamicTable = null;
    protected $loadDynamic = true;
    

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->hasDynamicValue($name))
            $value = $this->getDynamicValue($name);
        else
            $value = parent::__get($name);

        return $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return mixed|void
     */
    public function __set($name, $value)
    {
        if (false === $this->setDynamicValue($name, $value))
        {
            parent::__set($name, $value);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        if ($this->hasDynamicValue($name))
            return true;
        else
            return parent::__isset($name);
    }

    /**
     * @param string $name
     * @return mixed|void
     */
    public function __unset($name)
    {
        if ($this->hasDynamicValue($name))
            $this->setDynamicValue($name, null);
        else
            parent::__unset($name);
    }

    /**
     * @param string $attribute
     * @return array|null
     */
    public function getDynamicValue($attribute)
    {
        if (!$attribute)
            throw new MariaException("Column is (bool)==false");
        elseif ($this->hasDynamicValue($attribute))
            list($column, $name) = explode('_', $attribute);
            return isset($this->columnsValues[$column][$name]) ? $this->dynamicValues[$column][$name] : $this->getDynamicValues();
        else
            return null;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function setDynamicValue($attribute, $value)
    {
        if ($this->hasDynamicValue($attribute))
        {
            list($column, $name) = explode('_', $attribute);
            $this->columnValues[$column][$name] = $value;
            return true;
        }
        return false;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    public function hasDynamicValue($attribute)
    {
        list($column, $name) = explode('_', $attribute);
        return isset($this->columnsValues[$column][$name]);
    }

    /**
     *  @todo вынести в проперти
     */
    protected function getNamingModel()
    {
        return DynamicList::model();
    }

    protected function initDynamicValues()
    {
        if ($this->loadDynamic){
            $sql = "SELECT ";
            foreach ($columns as $column){
                //@todo cache here
                $dynamicIdsSql = "SELECT COLUMN_LIST($column) FROM " . $this->dynamicTable . " WHERE " . $this->primaryKey() . " = " . $this->primaryKey() . ";";
                
                $dynamicIdsList = explode(",", Yii::app()->db->createCommand($dynamicIdsSql)->queryScalar());
                $sql_columns = array();
                foreach ($dynamicIdsList as $id){
                    $id = (int)$id;
                    $model = $this->getNamingModel()->getByPk($id);
                    if (!($model instanceof MariaMetadataModel)){
                        throw new MariaException("Bad metadata model");
                    }
                    $type = $model->type;
                    $name = $model->name;
                    $sql_columns []= " COLUMN_GET($column, {$id} as {$type}) as {$column}_{$name} ";
                }
                $sql .= implode(", ", $sql_columns);
            }
            $sql .= " FROM " . $this->dynamicTable . " WHERE " . $this->primaryKey() . " = " . $this->primaryKey() . ";";
            $result = Yii::app()->db->createCommand($sql)->queryRow($sql);
            $this->columnsValues[$column] = array();
            foreach ($result as $name=>$val){
                list($column, $name) = explode('_', $name);
                $this->columnsValues[$column][$name]=$val;
            }
        }
    }

    /**
     * select dynamic fields from db
     */
    protected function beforeFind()
    {
        //logic from MariaModel
        parent::beforeFind();
    }

    protected function afterFind()
    {
        parent::afterFind();
        $this->initDynamicValues();
    }

    protected function saveDynamicChanged()
    {
        
    }
        
    protected function expireDynamicChanged()
    {
        
    }

    /**
     * put dynamic fields to db
     */
    protected function beforeSave()
    {
        return parent::beforeSave();
    }
    
    protected function afterSave()
    {
        $this->saveDynamicChanged();
        $this->expireDynamicChanged();
        return parent::afterSave();
    }

    /**
     * @return array
     */
    public function attributeNames()
    {
        return array_merge(parent::attributeNames(), array_keys($this->getDynamicValue()));
    }

    public function primaryKey()
    {
        return 'id';
    }

    /**
     * @param bool $names
     * @return array
     */
    public function getAttributes($names = true)
    {
        return parent::getAttributes() + $this->getDynamicValuesNames());
    }

    protected function getDynamicValuesNames()
    {
        
    }

    /**
     * @param bool|array|null $names
     * @todo выпилить $names == false и параметр $names
     * 
     * @return array
     */
    public function getDynamicValues()
    {
        $this->loadDynamic = true;
        $this->initDynamicValues();
        return $this->columnsValues;
    }

}
