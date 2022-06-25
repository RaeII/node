<?php

namespace Service;

use \Service\Service;
use \DataBase\BookingLogJourneyFareTaxs;

class BookingLogJourneyFareTaxsService extends Service {

    public function __construct() {
        $this->dataBase = new BookingLogJourneyFareTaxs();
    }

    /*
        @param $extraTax Journey PaxFare from getBooking.
    */
    public function create(Array $extraTax, Int $journeyFaresId) {
        $this->dataBase->create($extraTax, $journeyFaresId);
    }

    // public function fetchByLoc(String $loc, Int $userId = null) {
    //     // return $this->dataBase->fetchByLoc($loc, $userId);
    // }
}