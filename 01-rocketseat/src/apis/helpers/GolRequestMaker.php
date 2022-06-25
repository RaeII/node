<?php

namespace Api\Helpers;

require_once 'src/apis/config/requestConsts.php';

class GolRequestMaker extends SabreRequestMaker {    
    const BAG_1_CODE = '0C3';
    const BAG_2_CODE = '0JT';
    const BAG_3_CODE = '0J0';

    /*################################# UTIL #################################*/

    private function sellAncillaryBaggage(Array $offerInfo) {
        $paxsArranged = '';
        $segsArranged = '';
        $ancillaries  = [];
        $arrangePaxs     = function($pax) {
            $firstName = $pax['first_name'];
            $lastName  = $pax['last_name'];
            $idRef     = $pax['id_cia'];

            return <<<XML
                <v1:NameAssociationTag>
                    <v1:LastName>$lastName</v1:LastName>
                    <v1:FirstName>$firstName</v1:FirstName>
                    <v1:NameRefNumber>$idRef</v1:NameRefNumber>
                </v1:NameAssociationTag>
            XML;
        };
        $arrangeSegments = function($seg) {
            $code           = $seg['comp_code'];
            $flightNumber   = $seg['flight_number'];
            $depDate        = $seg['dep_date'];
            $from           = $seg['from'];
            $to             = $seg['to'];
            $serviceClass   = $seg['service_class'];

            return <<<XML
                <v1:SegmentAssociationTag>
                    <v1:CarrierCode>$code</v1:CarrierCode>
                    <v1:FlightNumber>$flightNumber</v1:FlightNumber>
                    <v1:DepartureDate>$depDate</v1:DepartureDate>
                    <v1:BoardPoint>$from</v1:BoardPoint>
                    <v1:OffPoint>$to</v1:OffPoint>
                    <v1:ClassOfService>$serviceClass</v1:ClassOfService>
                    <v1:BookingStatus>HK</v1:BookingStatus>
                </v1:SegmentAssociationTag>
            XML;
        };

        $paxsArranged = implode('', array_map($arrangePaxs, $offerInfo['condition']['paxs']));

        $segsArranged .= implode('', array_map($arrangeSegments, $offerInfo['condition']['journey']['segments']));            
        $serviceType    = $offerInfo['original_offer']['ServiceType'];
        $groupCode      = $offerInfo['original_offer']['Group'];

        $ancillaries[]  = $offerInfo['original_offer'];

        return implode('', array_map(function($ancillary) use($offerInfo, $serviceType, $groupCode, $paxsArranged, $segsArranged) {
            // $ssrCodesBySubCode = [
            //     '0C3' => 'ABAG',
            //     '0JT' => 'BBAG',
            //     '0JO' => 'CBAG'
            // ];

            $subCode                = $ancillary['SubCode'];
            $commercialName         = $ancillary['CommercialName'];
            $airline                = $ancillary['Airline'];
            $vendor                 = $ancillary['Vendor'];
            $emdType                = self::EMD_TYPES_CODE[$ancillary['ElectronicMiscDocType']];
            $refundableReissuable   = !empty($ancillary['AncillaryRules']['RefundableReissuable']) ? $ancillary['AncillaryRules']['RefundableReissuable'] : '';
            $passengerType          = $offerInfo['condition']['paxs'][0]['type'];
            $quantity               = $ancillary['Quantity'];
            $amount                 = $ancillary['AncillaryFee']['Base']['Amount'];
            // $currency               = $ancillary['AdditionalAncillaryData']['AncillaryFee']['Base']['Amount']['@attributes']['currency'];
            $currency               = 'BRL';
            $ssrCode                = AerialUtil::getSSRCodeBySubCode($subCode);

            $extraCt = strlen($refundableReissuable) > 0 ?  '
                        <v1:RefundIndicator></v1:RefundIndicator>
                        <v1:FormOfRefund>ORIGINAL</v1:FormOfRefund>' : '';
            return <<<XML
                <v1:ReservationUpdateItem UpdateId="1">
                    <!--id: id number
                        op: [C-Create, U-Update, D-Delete.]
                        elementId 
                        UpdateId-->
                
                    <v1:AncillaryServicesUpdate op="C">
                        <v1:NameAssociationList>
                            {$paxsArranged}
                        </v1:NameAssociationList>
                        <v1:SegmentAssociationList>
                            {$segsArranged}
                        </v1:SegmentAssociationList>
                        <v1:CommercialName>$commercialName</v1:CommercialName>
                        <v1:RficCode>$serviceType</v1:RficCode>
                        <v1:RficSubcode>$subCode</v1:RficSubcode>
                        <v1:SSRCode>$ssrCode</v1:SSRCode>
                        <v1:OwningCarrierCode>$airline</v1:OwningCarrierCode>
                        <v1:Vendor>$vendor</v1:Vendor>
                        <!--EMDType=""-->
                        <v1:EMDType>$emdType</v1:EMDType>
                        <!--0 to 24 repetitions:-->
                        <v1:SegmentNumber>1</v1:SegmentNumber>
                        <v1:TTLPrice>
                            <v1:Price>$amount</v1:Price>
                            <v1:Currency>$currency</v1:Currency>
                        </v1:TTLPrice>
                        <v1:OriginalBasePrice>
                            <v1:Price>$amount</v1:Price>
                            <v1:Currency>$currency</v1:Currency>
                        </v1:OriginalBasePrice>
                        {$extraCt}
                        <v1:PassengerTypeCode>$passengerType</v1:PassengerTypeCode>
                        <!--NumberOfItems="VARIABLE"-->
                        <v1:NumberOfItems>$quantity</v1:NumberOfItems>
                        <v1:ActionCode>HD</v1:ActionCode>
                        <!--SegmentIndicator="AdditionalAncillaryData/*:SectorPortionInd"-->
                        <v1:SegmentIndicator>P</v1:SegmentIndicator>
                        <!-- <v1:TaxExemptIndicator>N</v1:TaxExemptIndicator> -->
                        <v1:GroupCode>$groupCode</v1:GroupCode>
                    </v1:AncillaryServicesUpdate>
                </v1:ReservationUpdateItem>
                XML;
        }, $ancillaries));
    }

    private function delAncillaries(Array $ancillaries) {
        return array_reduce($ancillaries, function ($acc, $ancillary) {
            $id = $ancillary['ancillary_id'];

            $acc .= <<<XML
                <v1:ReservationUpdateItem>
                    <v1:AncillaryServicesUpdate op="D" id="{$id}"/>
                </v1:ReservationUpdateItem>
            XML;

            return $acc;
        }, '');
    }
    /*############################ REQUEST MAKERS ############################*/

    public function makeAncillaryOffers($journey, $paxs, $bagCode, $config) {
        $header     = $this->getHeader('GetAncillaryOffersRQ', 'GetAncillaryOffersRQ', $config);
        $compCode   = $this->compCode;
        $itinerary  = '';
        $itineraryIds = [];
        $fareArranged = '';
        $segmentsArranged = '';
        // $initneraries = implode('', array_map($mapItineraries, $content['journeys'], array_keys($content['journeys'])));
        // $initnerariesParsed = Formatter::soapToArray($initneraries, 'Itinerary');
        
        // print_r($initnerariesParsed);die();
        // foreach ($initnerariesParsed as $key => $value) {
            # code...
            // }
            // $initneraries = '';

        $fareBasis = $journey['fares'][0]['fare_basis'];

        /*################### SEGMENTS ###################*/
        foreach ($journey['segments'] as $indexSegment => $segment) {
            $compCode       = $segment['comp_code'];
            $flightNumber   = $segment['flight_number'];
            $from           = $segment['from'];
            $to             = $segment['to'];
            $depDate        = $segment['dep_date'];
            $depHour        = $segment['dep_hour'];
            $arrDate        = $segment['arr_date']; 
            $arrHour        = $segment['arr_hour'];
            $serviceOfClass = $segment['service_class'] ?? $segment['book_code'];
            $segmentIndex   = $indexSegment + 1;

            $segmentsArranged .= <<<XML
                <v03:Segment id="seg_{$segmentIndex}" segmentNumber="{$segmentIndex}">
                    <v035:FlightDetail id="flight_{$segmentIndex}">
                        <v037:Airline>$compCode</v037:Airline>
                        <v037:FlightNumber>$flightNumber</v037:FlightNumber>
                        <v037:DepartureAirport>$from</v037:DepartureAirport>
                        <v037:DepartureDate>$depDate</v037:DepartureDate>
                        <v037:DepartureTime>$depHour</v037:DepartureTime>
                        <v037:ArrivalAirport>$to</v037:ArrivalAirport>
                        <v037:ArrivalDate>$arrDate</v037:ArrivalDate>
                        <v037:ArrivalTime>$arrHour</v037:ArrivalTime>
                        <v037:ClassOfService>$serviceOfClass</v037:ClassOfService>
                    </v035:FlightDetail>
                </v03:Segment>
            XML;

            $itineraryIds['segments'][] = "seg_{$segmentIndex}";
        }

        /*################### FARE ###################*/
        $fareArranged = <<<XML
            <v03:FareInfo id="a9">
                <FareBasisCode>$fareBasis</FareBasisCode>
            </v03:FareInfo>
        XML;

        $itinerary .= <<<XML
            {$segmentsArranged}
            {$fareArranged}
        XML;

        foreach ($paxs as $index => $pax) {
            $paxType = $pax['type'];
            $paxIndex = $index + 1;
            $passengetSegments = implode('', array_map(function ($segmentId) { 
                    return <<<XML
                        <v03:PassengerSegment segmentRef="$segmentId">
                            <v035:FareBreakAssociation FareInfoRef="a9"/>
                        </v03:PassengerSegment>
                    XML;
                }, $itineraryIds['segments']));

            $itinerary = <<<XML
                <v03:QueryPassengerItinerary>
                    <v03:Passenger id="passenger_{$paxIndex}" type="{$paxType}"/>
                    <v03:PassengerItinerary>
                        $passengetSegments
                    </v03:PassengerItinerary>
                </v03:QueryPassengerItinerary>
            XML . $itinerary;
        }

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v03="http://services.sabre.com/merch/ancillary/offer/v03" xmlns:v031="http://services.sabre.com/merch/request/v03" xmlns:v032="http://services.sabre.com/merch/ancillary/v03" xmlns:v033="http://services.sabre.com/merch/passenger/v03" xmlns:v034="http://services.sabre.com/merch/common/v03" xmlns:v035="http://services.sabre.com/merch/itinerary/v03" xmlns:v036="http://services.sabre.com/merch/ticket/v03" xmlns:v037="http://services.sabre.com/merch/flight/v03">
                {$header}
                <soapenv:Body>
                    <v03:GetAncillaryOffersRQ version="3.1.0">
                        <v03:RequestType>payload</v03:RequestType>
                        <v03:RequestMode>Booking</v03:RequestMode>
                        <v03:ClientContext clientType="GSA">
                            <v031:AgentCurrencyCode>BRL</v031:AgentCurrencyCode>
                        </v03:ClientContext>
                        <v03:AncillaryRequestOptions>
                            <v032:Group>BG</v032:Group>
                        </v03:AncillaryRequestOptions>
                        <v03:QueryByItinerary>
                            {$itinerary}
                        </v03:QueryByItinerary>
                    </v03:GetAncillaryOffersRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function makeUpdateReservationSellAncillary(Array $data, Array $bookingInfo, Array $config) {
        $header     = $this->getHeader('UpdateReservationRQ', 'UpdateReservationRQ', $config);
        $locator    = $bookingInfo['locator'];
        $arrangedAncillaryBaggage = [];
        $ancillaryXml = '';

        if(!empty($data['ancillary_offers'])) {
            foreach ($data['ancillary_offers'] as $offerInfoByJourney) {
                foreach ($offerInfoByJourney as $offerInfo) {
                    if($offerInfo['original_offer']['Group'] === 'BG') $arrangedAncillaryBaggage[] = $this->sellAncillaryBaggage($offerInfo);
                }
            }

            $ancillaryXml = implode('', $arrangedAncillaryBaggage);
        }
        if(!empty($data['delete_ancillaries'])) {
            $del = $this->delAncillaries($data['delete_ancillaries']);
            $ancillaryXml .= $del;    
        }

        // if()
        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v1="http://webservices.sabre.com/pnrbuilder/v1_15" xmlns:v11="http://services.sabre.com/res/or/v1_8">
                $header
                <soapenv:Body>
                    <v1:UpdateReservationRQ Version="1.15.0">
                        <v1:RequestType>Stateful</v1:RequestType>
                        <v1:ReturnOptions RetrievePNR="true" IncludeUpdateDetails="true" ReturnLocator="true"/>
                        <v1:ReservationUpdateList>
                            <v1:Locator>{$locator}</v1:Locator>
                            {$ancillaryXml}
                        </v1:ReservationUpdateList>
                    </v1:UpdateReservationRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }
}