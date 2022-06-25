<?php

namespace Api\Interfaces;

interface AerialResponseMaker {

    public function arrangeSearch($searchRes);

    public function arrangeSeatAviability($seatsInfo);

    public function arrangeBookingInfo($booking);

    public function arrangeDivideBooking($divideResult);
}