<?php

namespace Kottman\Qb;

/**
 * PHP-QuickBase interface setup class. Although allowed, functions are not 
 * meant to be called directly
 */
final class Qb
{

    private function __construct()
    {
        
    }
    
    /**
     * Gets connection information needed to make the request to QB.
     * @param boolean $userTokenOnly if true is passed usertoken is returned
     * @return array|string
     */
    private static function getQBConn($userTokenOnly = false)
    {

        if ($userTokenOnly) {
            return config('app.qbUserToken');
        }

        return [
            'baseUrl' => config('app.qbBaseUrl'),
            'appToken' => config('app.qbAppToken'),
            'userToken' => config('app.qbUserToken'),
            'appId' => config('app.qbAppId'),
            // API request timeout
            'timeout' => config('app.qbTimeout', 12),
        ];
    }
    
    /**
     * API_AddRecord
     * @param string $dbId
     * @param array $updateInfo Array with update info, array keys should match
     * @param array $qbFieldAndValues new record info
     * @return array [success, \SimpleXMLElement $sxe]
     */
    public static function API_AddRecord($dbId, $qbFieldAndValues)
    {

        $connectionInfo = self::getQBConn();

        $userToken = $connectionInfo['userToken'];
        $appToken = $connectionInfo['appToken'];

        $fields = '';

        foreach ($qbFieldAndValues as $fId => $value) {
            $fields .= '<field fid="' . $fId . '"><![CDATA[' . $value . ']]></field>';
        }

        $xmlRequest = "<qdbapi><usertoken>$userToken</usertoken><apptoken>$appToken</apptoken>$fields</qdbapi>";
        $result = self::curlExec($dbId, 'API_AddRecord', $xmlRequest);
        list($success, $sxe) = self::isRequestSuccess($result);
        if (!$success) {
            \Log::error(__FUNCTION__ . ' ' . (is_string($result)? $result : print_r($result, true)));
            
        }
        return [$success, $sxe];
    }

    /**
     * API_DoQuery
     * @param type $dbID
     * @param type $criteria
     * @param type $clist
     * @param type $slist optional sorting
     * @param type $options
     * @return array [success, \SimpleXMLElement $sxe, fields|null]
     */
    public static function API_DoQuery($dbID, $criteria, $clist = 'a', $slist = null, $options = null)
    {
        if (is_array($clist)) {
            $clist = implode('.', $clist);
        }
//
        $connectionInfo = self::getQBConn();
        $xmlRequest = <<<XML
<qdbapi>
   <usertoken>{$connectionInfo['userToken']}</usertoken>
   <clist>{$clist}</clist>
   <query>{$criteria}</query>
   <fmt>structured</fmt>
</qdbapi>
XML;
        $reqSXE = simplexml_load_string($xmlRequest);
        if ($slist != null) {
            $reqSXE->addChild('slist', $slist);
        }
        if ($options != null) {
            $reqSXE->addChild('options', $options);
        }

        $qbResp = self::curlExec($dbID, 'API_DoQuery', $reqSXE->asXML());

        $sxe = simplexml_load_string($qbResp);
        if ($sxe && $sxe->{'errcode'} == 0) {
            $records = $sxe->{'table'}->{'records'}->children();
            $fields = $sxe->{'table'}->{'fields'}->children();
            $packageInfoArr = [];
            foreach ($records as $record) {
                $fieldAndValues = $record->{'f'};
                $packageInfo = [];
                foreach ($fieldAndValues as $fieldVal) {
                    $attributes = $fieldVal->attributes();
                    $packageInfo[(int) $attributes->{'id'}] = (string) $fieldVal;
                }
                $packageInfoArr[] = $packageInfo;
            }
            return [true, $packageInfoArr, $fields];
        }

        \Log::error(is_string($qbResp)? $qbResp : (__FUNCTION__ . ' ' . print_r($qbResp, true)));

        return [false, $sxe? : $qbResp, null];
    }

    /**
     * https://help.quickbase.com/api-guide/edit_record.html
     * @param type $dbId
     * @param string $qbRecordId The QB record Id that will be updated
     * @param array $qbFieldAndValues Optional map with update info, array keys are the fIds and 
     * values are the update values. $updateInfo will be ignored if this value is passed
     * @return array [success, \SimpleXMLElement]
     */
    public static function API_EditRecord(QbModel $record, $qbFieldAndValues)
    {

        $connectionInfo = self::getQBConn();

        $userToken = $connectionInfo['userToken'];
        $appToken = $connectionInfo['appToken'];

        $fields = '';
        $dbId = $record->getDbId();
        $qbRecordId = $record[$record->getTableId()];

        foreach ($qbFieldAndValues as $fId => $value) {
            $fields .= '<field fid="' . $fId . '"><![CDATA[' . $value . ']]></field>';
        }
        $xmlRequest = "<qdbapi><usertoken>$userToken</usertoken><apptoken>$appToken</apptoken><rid>$qbRecordId</rid>$fields</qdbapi>";
        $result = self::curlExec($dbId, 'API_EditRecord', $xmlRequest);
        list($success, $sxe) = self::isRequestSuccess($result);
        if (!$success) {
            \Log::error($qbRecordId . ' ' . (is_string($result) ? $result : (__FUNCTION__ . ' ' . print_r($result, true))));
            \Log::info($fields);
        }
        return [$success, $sxe];
    }

    /**
     * Use API_ProvisionUser to add a user who is not yet registered with Quick Base to your application     * 
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param type $roleId
     * @return array [success, \SimpleXMLElement]
     */
    public static function API_ProvisionUser($email, $firstName, $lastName, $roleId)
    {
        $connectionInfo = self::getQBConn();

        $userToken = $connectionInfo['userToken'];
        $appToken = $connectionInfo['appToken'];
        $appId = $connectionInfo['appId'];
        $xmlRequest = <<<XML
<qdbapi>
   <usertoken>{$userToken}</usertoken>
   <apptoken>{$appToken}</apptoken>
   <roleid>{$roleId}</roleid>
   <email>{$email}</email>
   <fname><![CDATA[{$firstName}]]></fname>
   <lname><![CDATA[{$lastName}]]></lname>
</qdbapi>
XML;
        $result = self::curlExec($appId, 'API_ProvisionUser', $xmlRequest);
        list($success, $sxe) = self::isRequestSuccess($result);
        return [$success, $sxe];
    }
    
    /**
     * Use API_SendInvitation to send an email invitation to your application. 
     * @param type $userId
     * @return array [success, \SimpleXMLElement]
     */
    public static function API_SendInvitation($userId)
    {
        $connectionInfo = self::getQBConn();

        $userToken = $connectionInfo['userToken'];
        $appToken = $connectionInfo['appToken'];
        $appId = $connectionInfo['appId'];

        $xmlRequest = <<<XML
<qdbapi>
   <usertoken>{$userToken}</usertoken>
   <apptoken>{$appToken}</apptoken>
   <userid>{$userId}</userid>
</qdbapi>
XML;
        $result = self::curlExec($appId, 'API_SendInvitation', $xmlRequest);
        list($success, $sxe) = self::isRequestSuccess($result);
        if(!$success){
            \Log::error('Qb::API_SendInvitation got result: ' . ($result? : 'empty') . "for userId $userId");
        }
        return [$success, $sxe];
    }
    
    /**
     * Use API_SendInvitation to send an email invitation to your application. 
     * @param string $email The login email of the user
     * @return array [$success, \SimpleXMLElement $sxe|string] $sxe is the QB response as \SimpleXMLElement
     */
    public static function API_GetUserInfo($email)
    {
        $connectionInfo = self::getQBConn();

        $userToken = $connectionInfo['userToken'];

        $xmlRequest = <<<XML
<qdbapi>
   <usertoken>{$userToken}</usertoken>
   <email>{$email}</email>
   <fmt>structured</fmt>
</qdbapi>
XML;
        $result = self::curlExec('main', 'API_GetUserInfo', $xmlRequest);
        list($success, $sxe) = self::isRequestSuccess($result);
        return [$success, $sxe];
    }

    /**
     * https://help.quickbase.com/api-guide/importfromcsv.html
     * Note: Values passed should not contain any comma characters
     * @param string $dbId
     * @param array $fieldIds a list with the QB field ids
     * @param array $fieldValuesArr an array of arrays of field values, values should
     * be at the same orders as their field ids
     * @return array [$success, ImportFromCsvResponse $sxe|string] $sxe is the QB response as ImportFromCsvResponse
     */
    public static function API_ImportFromCSV($dbId, $fieldIds, $fieldValuesArr)
    {
        $connectionInfo = self::getQBConn();
        $userToken = $connectionInfo['userToken'];
        $appToken = $connectionInfo['appToken'];

        $xml = '<qdbapi><records_csv><![CDATA[';
        $valueRows = array();
        $i = 0;
        foreach ($fieldValuesArr as $fieldValues) {

            $valueRows[] = implode(',', $fieldValues);
            $i++;
        }
        $xml .= implode("\r\n", $valueRows) . ']]></records_csv>';
        $clist = '<clist>' . implode('.', $fieldIds) . '</clist>';
        $xml .= $clist . '<skipfirst>0</skipfirst><usertoken>' . $userToken . '</usertoken><apptoken>' . $appToken . '</apptoken>';
        $xml .= '</qdbapi>';
        $apiUpdateResp = self::curlExec($dbId, 'API_ImportFromCSV', $xml);

        list($success, $sxe) = self::isRequestSuccess($apiUpdateResp);

        return [$success, ($sxe instanceof \SimpleXMLElement) ? new ImportFromCsvResponse($sxe) : $sxe];
    }

    /**
     * Check QB response if request was successful
     * @param string $qbResp the response string from QB, xml formatted
     * @return array [$success, \SimpleXMLElement $sxe|string] $sxe is the QB response as \SimpleXMLElement
     */
    public static function isRequestSuccess($qbResp)
    {
        $sxe = simplexml_load_string($qbResp);
        if ($sxe && $sxe->{'errcode'} == 0) {
            return [true, $sxe];
        }
        return [false, $sxe];
    }

    public static function curlExec($dbID, $action, $requestString)
    {

        $connectionInfo = self::getQBConn();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $connectionInfo['baseUrl'] . '/' . $dbID);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml', 'QUICKBASE-ACTION: ' . $action));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($ch, CURLOPT_TIMEOUT, $connectionInfo['timeout']);

        $result = curl_exec($ch);

        if (!$result) {
            \Log::error('Kottman\Qb\Qb::curlExec() ' . print_r(curl_error($ch), true));
        }

        curl_close($ch);

        return $result;
    }

}
