<?php

namespace Kottman\Qb;

/**
 * Used with @see QbModel::importUpdate() to create a collection for
 * ImportFromCSV API call
 */
class ImportUpdateRecord
{

    public $fieldIdValueMap;
    public $values;

    /**
     *
     * @param array $fieldIdValueMap
     */
    public function __construct(array $fieldIdValueMap)
    {
        ksort($fieldIdValueMap);
        $this->fieldIdValueMap = $fieldIdValueMap;
        $this->values = array_values($this->fieldIdValueMap);
    }

    public function getFieldIds()
    {
        return array_keys($this->fieldIdValueMap);
    }

    public function getValue($fieldId)
    {
        return $this->fieldIdValueMap[$fieldId];
    }

    public function getFieldIdValueMap()
    {
        return $this->fieldIdValueMap;
    }

}
