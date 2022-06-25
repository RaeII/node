<?php

namespace Api\Helpers;

class AerialUtil {
    function isTripInternational($segments) {
        $aerialStationSvc = new \Service\AerialStationsService();
        $airports = [];
        $segmentsLength = count($segments);
        $isInternational = false;
        $fetchRes = [];

        $fetchRes = $aerialStationSvc->fetchLike($segments[0]['from']);
        if(count($fetchRes) > 0) $airports[] = $fetchRes[0];
        $fetchRes = $aerialStationSvc->fetchLike($segments[$segmentsLength - 1]['to']);
        if(count($fetchRes) > 0) $airports[] = $fetchRes[0];

        foreach ($airports as $airport) {
            
            if($airport['country_code'] !== 'BR') {
                $isInternational = true;
                break;
            }
        }

        return $isInternational;
    }

    static function getSSRCodeBySubCode(String $subCode):String {
        $ssrCodesBySubCode = [
            '0C3' => 'ABAG',
            '0JT' => 'BBAG',
            '0JO' => 'CBAG',
            '0J0' => 'CBAG'
        ];

        return $ssrCodesBySubCode[$subCode];
    }
}