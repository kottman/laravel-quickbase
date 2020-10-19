<?php

namespace Kottman\Qb;

class UpdateField
{

    public $fieldId;
    public $value;
    public $error;

    public function __construct($fieldId, $value, $error = null)
    {
        $this->fieldId = (int)$fieldId;
        $this->value = $value;
        $this->error = $error;
    }

    public function getFieldId()
    {
        return $this->fieldId;
    }

    public function getValue()
    {
        return $this->value;
    }
    
    public function getError()
    {
        return $this->error;
    }

}
