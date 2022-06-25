<?php

namespace Service;

use \Util\SecureData;
use \Util\Validator;
use \Service\Service;
use \DataBase\BookingRegDiscount;

class BookingRegDiscountService extends Service {

    public function __construct() {
        $this->dataBase = new BookingRegDiscount();
    }

    public function create(Array $taxs, Int $bookingRegId) {
        $NEEDED_MARKUP_KEYS = ['code', 'value', 'pax_id'];

        Validator::validateJSONKeys($taxs, $NEEDED_MARKUP_KEYS);
        if($taxs['code'] != '') {
            $taxs['code'] = SecureData::secure($taxs['code']);
        }else {
            $taxs['code'] = NULL;
        }
        $taxs['value'] = SecureData::secure($taxs['value']);
        if($taxs['pax_id'] != '' && $taxs['pax_id'] > 0) {
            $taxs['pax_id'] = SecureData::secure($taxs['pax_id']);
        }else {
            $taxs['pax_id'] = NULL;
        }
        $bookingRegId = SecureData::secure($bookingRegId);

        $this->dataBase->create($taxs, $bookingRegId);
    }

    public function fetchSumByLocator(String $locator) {
        $res = $this->dataBase->fetchSumByLocator($locator);

        if(count($res) > 0 && $res[0]['total_value'] != null) {
            return $res[0]['total_value'];
        }else {
            return 0;
        }
    }
}