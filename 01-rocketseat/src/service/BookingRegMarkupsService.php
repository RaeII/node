<?php

namespace Service;

use \Util\SecureData;
use \Util\Validator;
use \Service\Service;
use \DataBase\BookingRegMarkups;

class BookingRegMarkupsService extends Service {

    public function __construct() {
        $this->dataBase = new BookingRegMarkups();
    }

    public function create(Array $markup, Int $bookingRegId) {
        $NEEDED_MARKUP_KEYS = ['description', 'role', 'value_type', 'value'];

        Validator::validateJSONKeys($markup, $NEEDED_MARKUP_KEYS);
        $markup['role'] = SecureData::secure($markup['role']);
        $markup['value_type'] = SecureData::secure($markup['value_type']);
        $markup['value'] = SecureData::secure($markup['value']);
        $markup['description'] = SecureData::secure($markup['description']);
        $bookingRegId = SecureData::secure($bookingRegId);

        $this->dataBase->create($markup, $bookingRegId);
    }

    public function fetchByLocator(String $locator) {
        $locator = SecureData::secure($locator);

        return $this->dataBase->fetchByLocator($locator);
    }

    public function fetchSumByLocator(String $locator) {
        $locator = SecureData::secure($locator);

        $res = $this->dataBase->fetchSumByLocator($locator);
        if(count($res) <= 0) {
            array_push($res, array('total_value' => 0, 'total_perc' => 0));
        }
        return $res[0];
    }
}