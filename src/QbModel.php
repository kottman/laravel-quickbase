<?php

namespace Kottman\Qb;

/**
 * Laravel-QuickBase interface main class. Extend QB app specific models
 * from this class
 */
class QbModel implements \JsonSerializable, \Illuminate\Contracts\Support\Jsonable, \ArrayAccess
{

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static )->$method(...$parameters);
    }

    /*
     * Instance
     */

    protected $tableId = 3;
    /**
     * In most cases 3 is the table key. Override if needed
     * @var int 
     */
    protected $tableKeyId = 3;
    protected $fieldIdNameMap = [
        3 => 'Record ID#',
    ];
    protected $fieldIdValuesMap = [];

    /**
     *
     * @param array $data field id - value map for initialization
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $fieldId => $value) {
            $this->fieldIdValuesMap[$fieldId] = $value;
        }
    }

    public function getFieldIdNameMap()
    {
        return $this->fieldIdNameMap;
    }

    public function getTableId()
    {
        return $this->tableId;
    }
    
    public function getTableKeyFieldId()
    {
        return $this->tableKeyId;
    }

    public function getDbId()
    {
        return $this->dbId;
    }

    public function getRecordId()
    {
        return $this[$this->getTableId()];
    }

    /**
     * Stores a new record in QuickBase
     * 
     * @return array [$success, $sxe]
     */
    public function save()
    {
        list($success, $sxe) = Qb::API_AddRecord($this->dbId, $this->fieldIdValuesMap);
        return [$success, $sxe];
    }

    /**
     * Stores a new record in QuickBase and fetches record from QuickBase.
     * Useful when need to get formula fields
     */
    public function saveAndFetch()
    {
        list($success, $sxe) = Qb::API_AddRecord($this->dbId, $this->fieldIdValuesMap);
        if ($success) {
            return $this->findByRecordId($sxe->rid);
        }
        return null;
    }

    /**
     * Use the passed UpdateFieldCollection to update QB record
     * @param UpdateFieldCollection $updateData
     * @return bool
     */
    public function updateByFieldsAndValues(UpdateFieldCollection $updateData)
    {
        list($success, $resp) = Qb::API_EditRecord($this, $updateData->getUpdateData());
        return $success;
    }

    /**
     * Update current instance
     * @param array $updateInfo
     * @param array $qbFieldAndValues
     * @return type
     */
    public function updateThis(array $updateInfo, array $qbFieldAndValues = null)
    {
        return Qb::API_EditRecord($this, $updateInfo, $qbFieldAndValues);
    }

    /**
     * Update current instance and fetch updated record
     * @param array $updateInfo
     * @param array $qbFieldAndValues
     * @return type
     */
    public function updateThisAndFetch(array $updateInfo, array $qbFieldAndValues = null)
    {
        list($success, $sxe) = Qb::API_EditRecord($this, $updateInfo, $qbFieldAndValues);
        if ($success) {
            return $this->findByRecordId($sxe->rid);
        }
        return null;
    }

    public function jsonSerialize()
    {
        return $this->fieldIdValuesMap;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize());
    }

    public function __get($name)
    {
        $fieldId = array_flip($this->fieldIdNameMap)[$name];
        return $this->fieldIdValuesMap[$fieldId];
    }

    /*     * ****************************************************************************** */

    /**
     * Get a record by record id
     * @param int $recordId
     * @return type
     */
    private function findByRecordId(int $recordId)
    {

        $collection = $this->whereRaw("{3.EX.$recordId}", 'a', null, 'num-1');
        if ($collection->count() > 0) {
            return $collection[0];
        }
        return null;
    }

    /**
     * Get a record by record id or fail
     * @param int $recordId
     * @return type
     */
    private function findByRecordIdOrFail(int $recordId)
    {

        $record = $this->findByRecordId($recordId);
        if ($record === null) {
            abort(404);
        }
        return $record;
    }

    /**
     * Get a record by record key
     * @param int $recordKey
     * @return type
     */
    private function findByRecordKey(int $recordKey)
    {
        $collection = $this->whereRaw("{{$this->tableKeyId}.EX.$recordKey}", 'a', null, 'num-1');
        if ($collection->count() > 0) {
            return $collection[0];
        }
        return null;
    }

    private function findByRecordKeyOrFail(int $recordKey)
    {
        $record = $this->findByRecordKey($recordKey);
        if ($record === null) {
            abort(404);
        }
        return $record;
    }
    
    /**
     * Gets first record matching the criteria. Aborts if none is found
     * @param string $criteria
     * @param type $clist
     * @param type $slist
     * @param type $options
     * @return QbModel
     */
    private function whereRawFirstOrFail(string $criteria, $clist = null, $slist = null, $options = null)
    {
        $record = $this->whereRawFirst($criteria, $clist, $slist, $options);
        if (!$record) {
            abort(404);
        }
        return $record;
    }
    
    /**
     * Gets first record matching the criteria
     * @param string $criteria Qb like API_DoQuery
     * @param array|string $clist
     * @param string $slist
     * @param string $options
     * @return QbModel|null
     */
    private function whereRawFirst(string $criteria, $clist = null, $slist = null, $options = null)
    {
        if (!$options) {
            $options = 'num-1';
        } else if (stripos($options, 'num-') === false) {
            $options .= '.num-1';
        }
        $collection = $this->whereRaw($criteria, $clist, $slist, $options);
        
        return $collection->first();
    }

    /**
     * Perform query
     * @param string $criteria
     * @param array|string $clist
     * @param string $slist
     * @param string $options
     * @return \Illuminate\Support\Collection Description
     */
    private function whereRaw(string $criteria, $clist = null, $slist = null, $options = null)
    {
        list($success, $recordInfoArr, $fields) = Qb::API_DoQuery($this->dbId, $criteria, ($clist ?: 'a'), $slist, $options);
        $collection = collect();
        if ($success) {
            foreach ($recordInfoArr as $recordInfo) {
                $model = new static;
                $model->fieldIdValuesMap = $recordInfo;
                foreach ($fields as $fieldXml) {
                    $attributes = $fieldXml->attributes();
                    $model->fieldIdNameMap[(int) $attributes->{'id'}] = (string) $fieldXml->{'label'};
                }
                $collection->push($model);
            }
        }
        return $collection;
    }
    
    
    /**
     * Performs the import-update
     * @param \Illuminate\Support\Collection A collection of Kottman\Qb\ImportUpdateRecord 
     * to be added or updated
     * @return array [$success, \SimpleXMLElement $sxe|string] $sxe is the QB response as \SimpleXMLElement
     */
    public function importUpdate(\Illuminate\Support\Collection $collection)
    {
        if ($collection->count() > 0) {
            $fieldIds = $collection->first()->getFieldIds();
            $values = $collection->pluck('values')->toArray();
            foreach ($values as $key => $recordValuesRow) {
                if(count($recordValuesRow) !== count($fieldIds)){
                    \Log::error('QbModel::importUpdate() invalid data passed');
                    return [false, null];
                }
                $values[$key] = str_replace(',', ' ', $values[$key]);
            }
            return Qb::API_ImportFromCSV($this->dbId, $fieldIds, $values);
        }
        return [false, null];
    }
    
    public function offsetSet($offset, $value)
    {
        if (isset($this->fieldIdValuesMap[$offset])) {
            $this->fieldIdValuesMap[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->fieldIdValuesMap[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->fieldIdValuesMap[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->fieldIdValuesMap[$offset]) ? $this->fieldIdValuesMap[$offset] : null;
    }

}
