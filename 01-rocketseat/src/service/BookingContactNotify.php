<?php

namespace Service;

use \Util\Validator;
use Util\SecureData;
use \Service\Service;
use \DataBase\BookingContactNotify;

class BookingContactNotifyService extends Service {

    public function __construct() {
        $this->dataBase = new BookingContactNotify();
    }

    // public function fetch() {
    //     return $this->dataBase->fetch();
    // }

    public function create(Array $contact) {
        $contact['email'] = SecureData::secure($contact['email']);
        $contact['phone_number'] = SecureData::secure($contact['phone_number']);
        $contact['ddd'] = SecureData::secure($contact['ddd']);
        $contact['country_code'] = SecureData::secure($contact['country_code']);
        $contact['first_name'] = SecureData::secure($contact['first_name']);
        if(isset($contact['middle_name'])) $contact['middle_name'] = SecureData::secure($contact['middle_name']);
        $contact['last_name'] = SecureData::secure($contact['last_name']);
        $contact['booking_log_id'] = SecureData::secure($contact['booking_log_id']);

        $this->dataBase->create($contact);
    }
}