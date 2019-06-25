<?php
public function incomingCall($internalNumber,$externalNumber, $userGroupId, $callStartDate, $oktellCallId,$action)
{
    
    $answer = null;
    $param = ["PHONE_NUMBER" => "+" . $externalNumber];
    $dateNow = new DateTime();
    $this->db->incomingCallRegister($internalNumber,$externalNumber,$callStartDate,$oktellCallId,$action);
    $result = $this->getEntity('test', 'telephony.externalCall.searchCrmEntities', $param)['result'];
    if (count($result) !== 0) {// вернуть внутренний номер ответсвеннного из кол центра + регистрация звонка в бд
        $workNumber = null;
        $innerNumber = null;
        foreach ($result as $key => $value) {
            ($result[$key]['CRM_ENTITY_TYPE'] === 'CONTACT' && count($result[$key]['ASSIGNED_BY']['WORK_PHONE'])!==0) ?
                $workNumber = $result[$key]['ASSIGNED_BY']['WORK_PHONE'] :
                false;
            ($result[$key]['CRM_ENTITY_TYPE'] === 'LEAD' && count($result[$key]['ASSIGNED_BY']['USER_PHONE_INNER'])!==0) ?
                $innerNumber = $result[$key]['ASSIGNED_BY']['USER_PHONE_INNER'] :
                false;
        }
        if($workNumber!==null){ //найден заведенный контакт на клиента, перевести на ответсвенного из отдела продаж
            $workNumber = str_replace("+", "", $workNumber);
            $workNumber = str_replace("-", "", $workNumber);
            $workNumber = str_replace(" ", "", $workNumber);
            $answer = ['redirection_number' => $workNumber , 'inner_number' => 'null'];
        }
        else if($innerNumber !== null){ //перевести на ответсвенного из колл центра
            $answer = ['redirection_number' => 'null', 'inner_number' => $innerNumber];
        }
    }
    else { //иначе дать инструкцию на обзвон по всему колл центру
        $answer =  ['redirection_number' => 'null', 'inner_number' => 'null'];
    }
    return $answer;
}
public function incomingCallUp($internalNumber,$userGroupId, $innerNumber, $oktellCallId, $externalNumber = null)
{
    $phoneNumber = null;
    $dateNow = new DateTime();
    //блок для обработки обратных звонков
    if($externalNumber !== null){
        $this->writeToLogTest($externalNumber);
        $phoneNumber = $externalNumber;
    }
    //для входящих звонков
    else{
        $call = $this->db->getCallByOktellId($oktellCallId);
        $phoneNumber = $call['external_number'];
    }
    $param = ["PHONE_NUMBER" =>$call['external_number']];
    $result = $this->getEntity('test', 'telephony.externalCall.searchCrmEntities', $param)['result'];
    if(count($result)!==0){ //сущность в crm cуществует
        switch ($internalNumber):
            case "test":
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 0, "CRM_SOURCE"=>"32", 'TYPE' => 2, 'SHOW' => 1, ];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
                break;
            case "test":
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 0, "CRM_SOURCE"=>"33", 'TYPE' => 2, 'SHOW' => 1, ];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
                break;
            case "test":
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 0, "CRM_SOURCE"=>"29", 'TYPE' => 2, 'SHOW' => 1, ];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
                break;
            default:
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 0, 'TYPE' => 2, 'SHOW' => 1, 'CRM_ENTITY_TYPE'=>$result[0]['CRM_ENTITY_TYPE']  , 'CRM_ENTITY_ID'=>$result[0]['CRM_ENTITY_ID'] ];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
        endswitch;
        //если звонок прикрепляется к фиктивному лиду - лид улетает в биржу
        if($result[0]['CRM_ENTITY_TYPE']==='LEAD'){
            $leadId=$result[0]['CRM_ENTITY_ID'];
            $param2 = ['id'=>$leadId];
            $lead = $this->getEntity('test', 'crm.lead.get', $param2)['result'];
            $status = $lead['STATUS_ID'];
            if ($status === "JUNK"){
                //$this->writeToLog("ON incomingCallUp: change status of lead {$leadId} (Junk to NEW)");
                $param2 = ['id'=>$leadId, "fields" => ["STATUS_ID"=>'NEW', 'UF_CRM_1550584121'=>"", 'UF_CRM_1554110565'=>""]];
                $this->getEntity('test', 'crm.lead.update', $param2)['result'];
            }
        }
    }
    else{
        switch ($internalNumber) {
            case "test":
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 1, "CRM_SOURCE" => "32", 'TYPE' => 2, 'SHOW' => 1,];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
                break;
            case "test":
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 1, "CRM_SOURCE" => "33", 'TYPE' => 2, 'SHOW' => 1,];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
                break;
            case "test":
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 1, "CRM_SOURCE" => "29", 'TYPE' => 2, 'SHOW' => 1,];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
                break;
            default:
                $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $phoneNumber, "CALL_START_DATE" => $call['call_start_date'],
                    'CRM_CREATE' => 1, "CRM_SOURCE" => "ADVERTISING", 'TYPE' => 2, 'SHOW' => 1,];
                $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
        }
    }
    $param = ['CALL_ID'=>$result["CALL_ID"]];
    $this->getEntity('test', 'telephony.externalcall.show', $param);
    if (isset($result["CALL_ID"])) {
        $this->db->setBitrixCallId($oktellCallId,$result["CALL_ID"]);
        $param = ['filter' => ['UF_PHONE_INNER'=> $innerNumber]];
        $users = $this->getEntity('test', 'user.get', $param)['result'];
        $idOfManager = $users[0]['ID'];
        if(isset($result['CRM_CREATED_LEAD'])){ //отправить уведомление о созданном лиде.
            $leadLink = "https://crm.procredit.by/crm/lead/details/{$result['CRM_CREATED_LEAD']}/";
            $param = ['to'=> $idOfManager, 'message' => "На основании звонка создан лид №{$result['CRM_CREATED_LEAD']}({$leadLink})", 'type' => 'SYSTEM'];
            $this->getEntity('test', 'im.notify', $param);
        }
        else{
            $this->writeToLog("Входящий звонок прикреплен к {$result['CRM_ENTITY_TYPE']} {$result['CRM_ENTITY_ID']}");
            $entity = strtolower($result['CRM_ENTITY_TYPE']);
            $link = "https://crm.procredit.by/crm/{$entity}/details/{$result['CRM_ENTITY_ID']}/";
            $param = ['to'=> $idOfManager, 'message' => "Звонок прикреплеплен к ({$link})", 'type' => 'SYSTEM'];
            $this->getEntity('test', 'im.notify', $param);
        }
        return ['status' => 'ok'];
    }
    else {
        return ['status' => 'bad'];
    }
}
public function outgoingCall($externalNumber, $innerNumber, $callStartDate, $oktellCallId,$action)
{
    $dateNow = new DateTime();
    $dateNowStr = $dateNow->format('c');
    $this->db->incomingCallRegister("исходящий звонок",$externalNumber, $callStartDate, $oktellCallId,$action); //регистрация звонка в бд

    $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $externalNumber, "CALL_START_DATE" => $callStartDate,
        'CRM_CREATE' => 0, 'TYPE' => 1];
    $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
    $param = ['CALL_ID'=>$result["CALL_ID"]];
    $this->getEntity('test', 'telephony.externalcall.show', $param)['result']; //Метод показывает карточку звонка пользователю.
    if (isset($result["CALL_ID"])) {
        $this->db->setBitrixCallId($oktellCallId, $result["CALL_ID"]);
    }
    if(isset($result['CRM_CREATED_LEAD'])){
        $this->writeToLog("{$dateNowStr}:На основании исходящего звонка Создан лид ID: {$result['CRM_CREATED_LEAD']}");
    }
    else{
        $this->writeToLog("{$dateNowStr}:Исходящий звонок: Тип найденой сущности crm: {$result['CRM_ENTITY_TYPE']}, id {$result['CRM_ENTITY_ID']}");
    }

}

public function finish($externalNumber, $innerNumber, $callStartDate, $oktellCallId, $duration, $status, $recordUrl)
{
    $dateNow = new DateTime();
    $dateNowStr = $dateNow->format('c');
    $call = $this->db->getCallByOktellId($oktellCallId);
    $name = explode('/',$recordUrl);
    $param = ['CALL_ID' => $call['bitrix_call_id'], 'USER_PHONE_INNER' => $innerNumber, 'DURATION' => $duration, 'STATUS_CODE' => $status];
    $result = $this->getEntity('test', 'telephony.externalcall.finish', $param)['result'];
    $param1 = ['CALL_ID' => $call['bitrix_call_id'],'FILENAME'=>$name[3],'RECORD_URL' => "http://out_oktell:9we1hbDc@test/{$recordUrl}"];
    $this->getEntity('test', 'telephony.externalcall.attachRecord', $param1);
    return $result;
}

public function missed($internalNumber,$externalNumber, $innerNumber, $callStartDate, $oktellCallId, $duration, $status, $recordUrl)
{

    $dateNow = new DateTime();
    $dateNowStr = $dateNow->format('c');

    $param = ["PHONE_NUMBER" => "+" . $externalNumber];
    $result = $this->getEntity('test', 'telephony.externalCall.searchCrmEntities', $param)['result'];
    //на пропущенный  входящий звонок регистрируется лид в битрикс с источником "пропущенный звонок'
    if (count($result) === 0) {
        switch($internalNumber){
            case "test":
                $param = ['fields' => ['SOURCE_ID' => 32, 'TITLE' => "{$externalNumber} - Входящий звонок на номер {$internalNumber}", 'PHONE' => [['VALUE' => $externalNumber, 'VALUE_TYPE' => 'WORK']]]];
                $this->getEntity('test', 'crm.lead.add', $param)['result'];
                break;
            case "test":
                $param = ['fields' => ['SOURCE_ID' => 33, 'TITLE' => "{$externalNumber} - Входящий звонок на номер {$internalNumber}", 'PHONE' => [['VALUE' => $externalNumber, 'VALUE_TYPE' => 'WORK']]]];
                $this->getEntity('test', 'crm.lead.add', $param)['result'];
                break;
            case "test":
                $param = ['fields' => ['SOURCE_ID' => 29, 'TITLE' => "{$externalNumber} - Входящий звонок на номер {$internalNumber}", 'PHONE' => [['VALUE' => $externalNumber, 'VALUE_TYPE' => 'WORK']]]];
                $this->getEntity('test', 'crm.lead.add', $param)['result'];
                break;
            default:
                $param = ['fields' => ['SOURCE_ID' => 22, 'TITLE' => "{$externalNumber} - Входящий звонок", 'PHONE' => [['VALUE' => $externalNumber, 'VALUE_TYPE' => 'WORK']]]];
                $this->getEntity('test', 'crm.lead.add', $param)['result'];}
    }
    else { //иначе найти в какую сущность прикрепить звонок
        $innerNumber = $result[0]['ASSIGNED_BY']['USER_PHONE_INNER'];
        $id = $result[0]['CRM_ENTITY_ID'];
        $type = $result[0]['CRM_ENTITY_TYPE'];
        $param = ['USER_PHONE_INNER' => $innerNumber, 'PHONE_NUMBER' => $externalNumber, "CALL_START_DATE" => $callStartDate,
            'CRM_CREATE' => 0, 'TYPE' => 2, 'CRM_ENTITY_TYPE'=>$type, 'SHOW'=>0, "CRM_ENTITY_ID" => $id];
        $result = $this->getEntity('test', 'telephony.externalcall.register', $param)['result'];
        $param = ['CALL_ID' => $result['CALL_ID'], 'USER_PHONE_INNER' => $innerNumber, 'DURATION' => $duration, 'STATUS_CODE' => $status];
        $this->getEntity('test', 'telephony.externalcall.finish', $param)['result'];
        //если пропущенный звонок прикрепляется к фиктивному лиду - лид улетает в биржу
        if($type ==="LEAD"){
            $param = ['id'=>$id];
            $lead = $this->getEntity('test', 'crm.lead.get', $param)['result'];
            $status = $lead['STATUS_ID'];
            if ($status === "JUNK"){
                $param = ['id'=>$id, "fields" => ["STATUS_ID"=>'NEW', 'UF_CRM_1550584121'=>"", 'UF_CRM_1554110565'=>""]];
                $this->getEntity('test', 'crm.lead.update', $param)['result'];
            }
        }
    }
}
}