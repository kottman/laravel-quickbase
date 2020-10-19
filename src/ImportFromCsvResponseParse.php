<?php

namespace Kottman\Qb;

/**
 * Parses QB response from a API_ImportFromCSV API call
 */
class ImportFromCsvResponseParse
{

    private $errCode;
    private $errText;
    private $numRecsInput;
    private $num_recs_input;
    private $numRecsAdded;
    private $numRecsUpdated;
    private $num_recs_updated;
    private $addedRecIds;
    private $updatetedRecIds;

    /**
     * Parses QB response from a API_ImportFromCSV API call
     * @param \SimpleXMLElement $sxe QB response as \SimpleXMLElement
     */
    public function __construct(\SimpleXMLElement $sxe)
    {
        $this->errCode = (int) $sxe->{'errcode'};
        $this->errText = (string) $sxe->{'errtext'};
        $this->num_recs_input = isset($sxe->{'num_recs_input'}) ? (int) $sxe->{'num_recs_input'} : null;
        $this->numRecsInput = isset($sxe->{'num_recs_input'}) ? (int) $sxe->{'num_recs_input'} : null;
        $this->numRecsAdded = isset($sxe->{'num_recs_added'}) ? (int) $sxe->{'num_recs_added'} : null;
        $this->numRecsUpdated = isset($sxe->{'num_recs_updated'}) ? (int) $sxe->{'num_recs_updated'} : null;
        $this->num_recs_updated = isset($sxe->{'num_recs_updated'}) ? (int) $sxe->{'num_recs_updated'} : null;
        if (isset($sxe->{'rids'})) {
            foreach ($sxe->{'rids'}->children() as $rid) {
                /* @var $rid \SimpleXMLElement */
                if (isset($rid['update_id'])) {
                    $this->addedRecIds[] = (int) $rid;
                } else {
                    $this->updatetedRecIds[] = (int) $rid;
                }
            }
        } else {
            $this->addedRecIds = null;
        }
    }

    public function __get($name)
    {
        return $this->$name;
    }

}
