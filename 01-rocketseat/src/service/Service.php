<?php

namespace Service;

class Service {
    protected $dataBase;

    public function startTransaction($sgbdName) {
        $this->dataBase->startTransaction($sgbdName);
    }

    public function commit($sgbdName) {
        $this->dataBase->commit($sgbdName);
    }
}
