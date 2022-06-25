<?php

namespace Api\Helpers;

use Api\Helpers\SabreResponseMaker;

class GolResponseMaker extends SabreResponseMaker {
    public function arrangeAncillaryOffer($offer) {
        $arranged   = [];
        $serviceType = [];

        $serviceType                = $offer['ServiceType'] === 'C' || $offer['ServiceType'] === 'A' ? 'BG' : '';
        $arranged['service_type']   = $serviceType; 
        
        if($serviceType === 'BG') {
            $subCode    = $offer['SubCode'];
            $newSubCode = '';

            if($subCode === '0C3') $newSubCode = 1;
            if($subCode === '0JT') $newSubCode = 2;
            if($subCode === '0J0') $newSubCode = 3;

            $arranged['sub_code']       = $newSubCode;
            $arranged['description']    = $offer['Description1Code'];
            $arranged['value']          = $offer['AncillaryFee']['Base']['Amount'];
        }

        return $arranged;
    }
}