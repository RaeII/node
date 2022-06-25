<?php

namespace Api\Interfaces;

interface AerialController {
    public function logon();
    public function saveSession($session);
    public function search(Array $request):Array;
    public function book(Array $request):Array;
    public function confirmBooking(Array $request, Int $credentialId):Array;
    public function ancillary(Array $request);
    public function ancillaryPrice(Array $request);
    public function divideBooking(Array $request):Array;
    public function divideAndCancelBooking(Array $request):Array;
    public function getSeatAvailability(Array $request):Array;
    public function seatAssign(Array $request):bool;
    public function getBooking(Array $request):Array;
    public function cancelAncillaries(Array $request):Array;
    public function cancelLoc(Array $request):Array;
    public function clearSession();
    public function logout(Array $response):bool;
}