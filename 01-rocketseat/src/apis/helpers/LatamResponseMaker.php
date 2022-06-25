<?php

namespace Api\Helpers;

use Api\Helpers\SabreResponseMaker;

class LatamResponseMaker extends SabreResponseMaker{
    public function arrangeAncillaryOffer($offer) {
        $offer      = $offer['AncillaryOffers']['Itinerary']['AncillariesByServiceType'];
        $arranged   = [];
        $serviceType = [];

        $serviceType                = $offer['ServiceType'] === 'C' ? 'BG' : '';
        $arranged['service_type']   = $serviceType; 
        
        if($serviceType === 'BG') {
            $subCode    = $offer['AncillaryGroups']['Ancillary']['BasicAncillaryData']['Subcode'];
            $newSubCode = '';

            if($subCode === '0C3') $newSubCode = 1;
            if($subCode === '0JT') $newSubCode = 2;

            $arranged['sub_code']       = $newSubCode;
            $arranged['description']    = $offer['AncillaryGroups']['Ancillary']['AncillaryDefinition']['Description1']['Description1Code'];
            $arranged['value']          = $offer['AncillaryGroups']['Ancillary']['AdditionalAncillaryData']['AncillaryFee']['Base']['Amount'];
        }

        return $arranged;
    }
}