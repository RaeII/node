<?php

namespace Service;

use \Util\Validator;
use Util\SecureData;
use \Service\Service;
use \DataBase\AccessLog;

class AccessLogService extends Service {

    public function __construct() {
        $this->dataBase = new AccessLog();
    }

    public function create($data) {
        $data['ip'] = SecureData::secure($data['ip']);
        $data['uri'] = SecureData::secure($data['uri']);
        $data['payload'] = SecureData::secure($data['payload']);
        $data['method'] = SecureData::secure($data['method']);
        $data['auth'] = $data['auth'] != NULL ? SecureData::secure($data['auth']) : NULL;

        $this->dataBase->create($data);
    }
}