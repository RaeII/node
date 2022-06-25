<?php

namespace Service;

use \Util\SecureData;
use \Util\Validator;
use \Service\Service;
use \DataBase\UserAccount;

class UserAccountService extends Service {

    public function __construct() {
        $this->dataBase = new UserAccount();
    }

    public function login(Array $bodyContent) {
        $NEEDEDKEYS = ["username", "pwd"];

        Validator::validateJSONKeys($bodyContent, $NEEDEDKEYS);
        $userName = SecureData::secure($bodyContent['username'], 'Nome de Usuario');
        $pwd = SecureData::secure($bodyContent['pwd'], 'Senha');

        $user = $this->dataBase->fetch($userName, $pwd);

        if(count($user) == 0) {
            throw new \Exception(getErrorMessage('noUserRegistered'));
        }
        return $user[0];
    }

    public function fetchById(Int $id) {
        if($id == null || $id <= 0) throw new \Exception(getErrorMessage('invalidUserId'));

        return $this->dataBase->fetchById($id);
    }
}