<?php

namespace Api\Helpers;

class LatamUtil {
    public static function throwLatamError($laResponse) {
        if(isset($laResponse['ApplicationResults']['Error'])) {
            // Errors
            // - Unable to end the transaction

            $error = $laResponse['ApplicationResults']['Error']['SystemSpecificResults']['Message'];

            // Warnings
            // - NUMBER OF NAMES NOT EQUAL TO RESERVATIONS

            if(isset($laResponse['ApplicationResults']['Warning'][1])) {
                $error .= (' - ' . $laResponse['ApplicationResults']['Warning'][1]['SystemSpecificResults']['Message']);
            }
            throw new \Exception($error, 1);
        }else if(isset($laResponse['Errors'])) {
            $schedules = array_values(array_filter($laResponse['Errors']['Error'], function($error) {
                return ($error['@attributes']['Type'] === 'SCHEDULES');
            }));

            if(count($schedules) > 0) throw new \Exception($schedules[0]['@attributes']['ShortText']);
        }else {
            throw new \Exception(getErrorMessage('unknownError'), 1);
        }
    }

    public function getBrandByFareBasis($fareBasis) {
        $lastChar = substr($fareBasis, -1);

        // Tarifa operadora/Tarifa publica doméstico Brasil
        if($lastChar == 5) {
            return 'SN';
        }else if($lastChar == 1) {
            return 'SL';
        }else if($lastChar == 8) {
            return 'SE';
        }else if($lastChar == 9) {
            return 'SF';
        }else if($lastChar == 'A') {
            return 'RY';
        }else if($lastChar == 2) {
            return 'RA';
        }
        // Tarifa operadora/Tarifa publica doméstico Brasil 
        else if (str_contains($fareBasis, 'SN')) {
            return 'SN';
        }else if (str_contains($fareBasis, 'SL')) {
            return 'SL';
        }else if (str_contains($fareBasis, 'SE')) {
            return 'SE';
        }else if (str_contains($fareBasis, 'SF')) {
            return 'SF';
        }else if (str_contains($fareBasis, 'RL')) {
            return 'RL';
        }else if (str_contains($fareBasis, 'RY')) {
            return 'RY';
        }else if (str_contains($fareBasis, 'EV')) {
            return 'EV';
        }else if (str_contains($fareBasis, 'EJ')) {
            return 'EJ';
        }else {
            return '';
        }
    }
}