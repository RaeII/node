<?php

namespace Api\Helpers;

class AzulUtil {
    public function getTripsFromRouteKey($key) {
        $tripAirportIatas = [];
        $trips = explode("~~", $key);

        unset($trips[0]);
        foreach ($trips as $trip) {
            $mt = [];

            preg_match_all('/\b[A-Z]{3}\b/', $trip, $mt);
            $response = $mt[0];

            if(count($response) <= 0 || count($response) % 2 != 0)   throw new \Exception(getErrorMessage('tripKeyNotValid'));
            $tripAirportIatas[] = $response;
        }
        return $tripAirportIatas;
    }

    public function getDatesFromRouteKey($key) {
        $dates = [];
        $trips = explode('~~', $key);

        unset($trips[0]);
        foreach ($trips as $trip) {
            $mt = [];

            preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $trip, $mt);
            $response = $mt[0];

            if(count($response) <= 0 || count($response) % 2 != 0)   throw new \Exception(getErrorMessage('tripKeyNotValid'));
            $dates[] = $response;
        }
        return $dates;
    }

    public function getAircraftCodes($key) {
        $codes = [];
        $mt = [];

        preg_match_all('/~\d{4}~/', $key, $mt);
        $response = $mt[0];

        if(count($response) <= 0)   throw new \Exception(getErrorMessage('tripKeyNotValid'));

        $response = str_replace('~', '', $response);
        return $response;
    }

    public function getProductClassName($prodClass) {
        switch ($prodClass) {
            case 'F+':
                return 'Tarifa Azul';            
            case 'PR':
                return 'Tarifa MaisAzul';
            case 'OF':
                return 'Tarifa Operadora';
            default:
                throw new \Exception(getErrorMessage('productClassNotFound'));
        }
    }
}