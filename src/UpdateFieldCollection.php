<?php

namespace Kottman\Qb;

class UpdateFieldCollection extends \Illuminate\Support\Collection
{

    /**
     * 
     * @param UpdateField $item
     * @return $this
     */
    public function add($item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @return array [fieldId1 => value1, fieldId2 => value2, ...]
     */
    public function getUpdateData()
    {
        $updateData = [];
        foreach ($this->items as $item) {
            $updateData[$item->getFieldId()] = $item->getValue();
        }

        return $updateData;
    }

    public function getErrors()
    {
        $errors = [];
        foreach ($this->items as $item) {
            $itemError = $item->getError();
            if($itemError)
            $errors[$item->getFieldId()] = $itemError;
        }

        return $errors;
    }

}
