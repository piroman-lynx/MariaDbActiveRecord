<?php

class ActiveRecord extends CActiveRecord
{
    protected $dynamicValues = array();
    protected $columns = array();
    protected $columnsArray = array();

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
    public function getDynamicValue($attribute = null)
    {
        if (null === $attribute)
            return $this->dynamicValues;
        elseif ($this->hasDynamicValue($attribute))
            return isset($this->dynamicValues[$attribute]) ? $this->dynamicValues[$attribute] : $this->getDynamicValues($attribute);
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
            $this->dynamicValues[$attribute] = $value;
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
        return array_key_exists($attribute, $this->dynamicValues) || array_key_exists($attribute, $this->dynamicValuesProperties());
    }


    protected function initDynamicValues()
    {

    }

    protected function prepareDynamicValues()
    {

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

    /**
     * put dynamic fields to db
     */
    protected function beforeSave()
    {
        $this->prepareDynamicValues();
        return parent::beforeSave();
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
        return parent::getAttributes() + $this->getDynamicValues($names);
    }

    /**
     * @param bool|array|null $names
     * @return array
     */
    public function getDynamicValues($names = true)
    {
        $dynamicValues = $this->getDynamicValue();
        $attributes = array();
        if (true === $names)
        {
            $attributes = $dynamicValues;
        }
        else if (is_array($names))
        {
            foreach ($names as $name)
            {
                if (isset($dynamicValues[$name]))
                {
                    $attributes[$name] = $dynamicValues[$name];
                }
            }
        }
        else
        {
            foreach ($dynamicValues as $name => $value)
            {
                if (null !== $value)
                {
                    $attributes[$name] = $value;
                }
            }
        }
        return $attributes;
    }


    /**
     * Return list of dynamic fields
     * array(
     *   fieldName1 => fieldValue1,
     *   fieldName2 => fieldValue2,
     *   .....
     * )
     * @return array
     */
    protected function dynamicValuesProperties()
    {
        return array();
    }

    /**
     * Return default dynamic field value
     * @param string $name
     * @return mixed
     */
    protected function getDefaultDynamicValue($name)
    {
        $e = $this->initDynamicValues();
        return isset($e[$name]) ? $e[$name] : null;
    }
}
