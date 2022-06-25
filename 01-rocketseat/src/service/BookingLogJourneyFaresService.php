<?php

namespace Service;

use \Service\Service;
use \DataBase\BookingLogJourneyFares;

class BookingLogJourneyFaresService extends Service {

    public function __construct() {
        $this->dataBase = new BookingLogJourneyFares();
    }

    /*
        @param $fare Journey Fare from getBooking.
    */
    public function create(Array $paxFare, String $productClass, String $serviceClass, Int $journeyId) {
        return $this->dataBase->create($paxFare, $productClass, $serviceClass, $journeyId);
    }

    // public function fetchByLoc(String $loc, Int $userId = null) {
    //     // return $this->dataBase->fetchByLoc($loc, $userId);
    // }
}