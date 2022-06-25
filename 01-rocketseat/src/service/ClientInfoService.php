<?php

namespace Service;

use \Util\SecureData;
use \Service\Service;
use \DataBase\ClientInfo;

class ClientInfoService extends Service {
    public function __construct() {
        $this->dataBase = new ClientInfo();
    }

    public function fetch($id) {
        try {
            $id = SecureData::secure($id);

            return $this->dataBase->fetch($id);
        } catch (\Exception $e) {
            throw $e;
        }
    }


}
