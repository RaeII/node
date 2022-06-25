<?php

namespace Api\Helpers;

require_once 'src/apis/config/requestConsts.php';

class LatamRequestMaker extends SabreRequestMaker {
    const BAG_1_CODE = '0C3';
    const BAG_2_CODE = '0JT';
    const BAG_3_CODE = '0JO';

    public function logout(String $credential, String $endpoint) {
        $payload = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://www.opentravel.org/OTA/2002/11">
                <soapenv:Header>
                    <sec:Security>
                        <sec:BinarySecurityToken>$credential</sec:BinarySecurityToken>
                    </sec:Security>
                    <mes:MessageHeader mes:id="1" mes:version="1.0.0">
                        <mes:From>
                            <mes:PartyId mes:type="URI">URLofAppsclient@yourDomain.com</mes:PartyId>
                        </mes:From>
                        <mes:To>
                            <mes:PartyId mes:type="URI">$endpoint</mes:PartyId>
                        </mes:To>
                        <mes:CPAId>LA</mes:CPAId>
                        <mes:ConversationId>LA</mes:ConversationId>
                        <mes:Service mes:type="SabreXML">SessionCloseRQ</mes:Service>
                        <mes:Action>SessionCloseRQ</mes:Action>
                        <mes:MessageData>
                            <mes:MessageId></mes:MessageId>
                            <mes:Timestamp></mes:Timestamp>
                        </mes:MessageData>
                        <mes:Description xml:lang="en-us"/>
                    </mes:MessageHeader>
                </soapenv:Header>
                <soapenv:Body>
                    <ns:SessionCloseRQ>
                        <ns:POS>
                            <ns:Source PseudoCityCode="?"/>
                        </ns:POS>
                    </ns:SessionCloseRQ>
                </soapenv:Body>
            </soapenv:Envelope>
            XML;

        return $payload;
    }

    // public function designatePrinterLLS($session, $endpoint) {
    //     return <<<XML
    //         <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://webservices.sabre.com/sabreXML/2011/10">
    //             {$this->getHeader($session, $endpoint, 'DesignatePrinterRQ')}
    //             <soapenv:Body>
    //                 <ns:DesignatePrinterRQ ReturnHostCommand="true" TimeStamp="" Version="2.0.1">
    //                     <ns:Printers>
    //                         <ns:Ticket CountryCode="2A" LNIATA="*ETKT*"/>
    //                     </ns:Printers>
    //                 </ns:DesignatePrinterRQ>
    //             </soapenv:Body>
    //         </soapenv:Envelope>
    //     XML;
    // }

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
        $groupCode      = $offerInfo['original_offer']['AncillaryGroups']['@attributes']['group'];

        if(isset($offerInfo['original_offer']['AncillaryGroups']['Ancillary'][0])) {
            $ancillaries    = $offerInfo['original_offer']['AncillaryGroups']['Ancillary'];
        }else {
            $ancillaries[]  = $offerInfo['original_offer']['AncillaryGroups']['Ancillary'];
        }

        return implode('', array_map(function($ancillary) use($serviceType, $groupCode, $paxsArranged, $segsArranged) {
            // $ssrCodesBySubCode = [
            //     '0C3' => 'ABAG',
            //     '0JT' => 'BBAG',
            //     '0JO' => 'CBAG'
            // ];
            $subCode                = $ancillary['BasicAncillaryData']['Subcode'];
            $commercialName         = $ancillary['BasicAncillaryData']['CommercialName'];
            $airline                = $ancillary['BasicAncillaryData']['Airline'];
            $vendor                 = $ancillary['BasicAncillaryData']['Vendor'];
            $emdType                = self::EMD_TYPES_CODE[$ancillary['BasicAncillaryData']['ElectronicMiscDocType']];
            $refundableReissuable   = $ancillary['AdditionalAncillaryData']['AncillaryRules']['RefundableReissuable'];
            $feeApplicationMethod   = self::FEE_AP_METHODS[$ancillary['AdditionalAncillaryData']['AncillaryRules']['FeeApplicationMethod']];
            $passengerType          = $ancillary['AdditionalAncillaryData']['PassengerType'];
            $quantity               = $ancillary['AdditionalAncillaryData']['Quantity'];
            $amount                 = $ancillary['AdditionalAncillaryData']['AncillaryFee']['Base']['Amount'];
            // $currency               = $ancillary['AdditionalAncillaryData']['AncillaryFee']['Base']['Amount']['@attributes']['currency'];
            $currency               = 'BRL';
            $ssrCode                = AerialUtil::getSSRCodeBySubCode($subCode);

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
                        <v1:OriginalBasePrice>
                            <v1:Price>$amount</v1:Price>
                            <v1:Currency>$currency</v1:Currency>
                        </v1:OriginalBasePrice>
                        <v1:RefundIndicator>$refundableReissuable</v1:RefundIndicator>
                        <v1:FeeApplicationIndicator>$feeApplicationMethod</v1:FeeApplicationIndicator>
                        <v1:PassengerTypeCode>$passengerType</v1:PassengerTypeCode>
                        <!--NumberOfItems="VARIABLE"-->
                        <v1:NumberOfItems>$quantity</v1:NumberOfItems>
                        <v1:ActionCode>HD</v1:ActionCode>
                        <!--SegmentIndicator="AdditionalAncillaryData/*:SectorPortionInd"-->
                        <v1:SegmentIndicator>P</v1:SegmentIndicator>
                        <v1:GroupCode>$groupCode</v1:GroupCode>
                    </v1:AncillaryServicesUpdate>
                </v1:ReservationUpdateItem>
                XML;
        }, $ancillaries));
    }

    /*################################# UTIL #################################*/
    //>>
    public function makePaymentAncillary(Array $paymentInfo, Array $config, $paymentInf) {
        print_r($paymentInfo);exit;
        $locator    = $paymentInfo['locator'];
        $timeStamp  = $config['timestamp'];
        $stationNr  = $config['stationNr'];
        $channelId  = $config['channelId'];
        $merchantId = $config['domain'];
        $arrangedSegments   = [];
        $arrangedPaxs       = [];
        $arrangedPayments   = [];

        $arrangePaxs = function (Array $pax) {
            $firstName  = $pax['first_name'];
            $lastName   = $pax['last_name'];
            $totalCost  = $pax['total_cost'];
            $totalTax   = $pax['total_tax'];
            $tariff     = $pax['tariff'];
            return <<<XML
                <beta:PassengerDetail FirstName="$firstName" LastName="$lastName">
                <!--0 to 99 repetitions:-->
                    <beta:Document DocType="TKT" eTicketInd="true" BaseFare="$tariff" Taxes="$totalTax"/>
                </beta:PassengerDetail>
            XML;
        };
        $arrangeSegments = function(Array $seg) {
            $compCode   = $seg['comp_code'];
            $from       = $seg['from'];
            $to         = $seg['to'];
            $flightNumber = $seg['flight_number'];
            $serviceClass = $seg['service_class'];
            $depDate    = "{$seg['dep_date']}T{$seg['dep_hour']}";
            $arrDate    = "{$seg['arr_date']}T{$seg['arr_hour']}";

            return <<<XML
                <beta:FlightDetail>
                    <beta:AirlineCode>$compCode</beta:AirlineCode>
                    <beta:FlightNumber>$flightNumber</beta:FlightNumber>
                    <beta:ClassOfService>$serviceClass</beta:ClassOfService>
                    <beta:DepartureInfo DepartureDateTime="$depDate" DepartureAirport="$from" CurrentLocalDateTime=""/>
                    <beta:ArrivalInfo ArrivalDateTime="$arrDate" ArrivalAirport="$to" FinalDestinationInd="false"/>
                </beta:FlightDetail>
            XML;
        };
        $arrangedPayments = function(Array $payment) {
            list(
                'type' => $type,
                'code' => $code,
                'acc_holder_name' => $accHolderName,
                'acc_sec_code' => $accSecCode,
                'acc_number' => $accNumber,
                'exp_date' => $expDate,
                'num_instal' => $numInstal,
                'value' => $value
            ) = $payment;

            $expDate = date('mY', strtotime($expDate));
            return <<<XML
                <beta:PaymentDetail>
                    <beta:FOP Type="$type" FOP_Code="$type"/>
                    <!--PaymentCard CardCode="AX" CardNumber="370000000000002" ExpireDate="082018"-->
                    <!--PaymentCard CardCode="CA" CardNumber="5555555555554444" ExpireDate="082018"-->
                    <!--PaymentCard CardCode="DC" CardNumber="36006666333344" ExpireDate="082018"-->
                    <!--PaymentCard CardCode="EL" CardNumber="5066991111111118" ExpireDate="082018"-->
                    <!--PaymentCard CardCode="HC" CardNumber="6062828888666688" ExpireDate="082018"-->
                    <!--PaymentCard CardCode="VI" CardNumber="4988438843884305" ExpireDate="082018"-->
                    <!--PaymentCard CardCode="VI" CardNumber="4444333322221111" ExpireDate="082018" ExtendPayment="12"-->
                    <beta:PaymentCard CardCode="$code" CardNumber="$accNumber" CardSecurityCode="$accSecCode" ExpireDate="$expDate">
                    <beta:CardHolderName Name="$accHolderName"/>
                    </beta:PaymentCard>
                    <!--Optional:-->
                    <beta:AmountDetail Amount="$value" CurrencyCode="BRL"/>
                </beta:PaymentDetail>
            XML;
        };

        $arrangedPaxs     = implode(' ', array_map($arrangePaxs, $paymentInfo['paxs']));
        $arrangedSegments = implode(' ', array_map($arrangeSegments, $paymentInfo['journeys'][0]['segments']));

        $arrangedPayments = implode(' ', array_map($arrangedPayments, $paymentInf));
        print_r($_SESSION);exit;

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:beta="http://www.opentravel.org/OTA/2003/05/beta">
                {$this->getHeader('PaymentRQ', 'PaymentRQ', $config)}
                <soapenv:Body>
                    <beta:PaymentRQ SystemDateTime="{$timeStamp}" Version="4.1.0" xmlns="http://www.opentravel.org/OTA/2003/05/beta" xmlns:xsi="http:/www.w3.org/2001/XMLSchema-instance">
                        <beta:Action>OrchPayment</beta:Action>
                        <beta:POS AgentSine="AWS" StationNumber="$stationNr" ISOCountry="BR" ChannelID="$channelId" LocalDateTime="{$timeStamp}"/>
                        <beta:MerchantDetail MerchantID="$merchantId"/>
                        <beta:OrderDetail RecordLocator="$locator">
                            {$arrangedPaxs}
                            {$arrangedSegments}
                        </beta:OrderDetail>
                        <!--0 to 10 repetitions:-->
                            {$arrangedPayments}
                    </beta:PaymentRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    /*############################ REQUEST MAKERS ############################*/

    public function makeAncillaryOffers($journey, $paxs, $bagCode, $config) {
        $mapItineraries = function ($itinerary, $index) {
            $mapSegments = function ($segment, $index) {
                $compCode       = $segment['comp_code'];
                $flightNumber   = $segment['flight_number'];
                $from           = $segment['from'];
                $to             = $segment['to'];
                $depDate        = "{$segment['dep_date']}T{$segment['dep_hour']}";
                $arrDate        = "{$segment['arr_date']}T{$segment['arr_hour']}";
                $serviceOfClass = $segment['service_class'];
                $segmentIndex   = $index + 1;

                return <<<XML
                    <v022:Segment id="segment_{$segmentIndex}">
                        <v024:FlightDetail id="flight{$segmentIndex}">
                            <v024:Airline>$compCode</v024:Airline>
                            <v024:FlightNumber>$flightNumber</v024:FlightNumber>
                            <v024:DepartureAirport>$from</v024:DepartureAirport>
                            <v024:DepartureTime>$depDate</v024:DepartureTime>
                            <v024:ArrivalAirport>$to</v024:ArrivalAirport>
                            <v024:ArrivalTime>$arrDate</v024:ArrivalTime>
                            <v024:ClassOfService>$serviceOfClass</v024:ClassOfService>
                        </v024:FlightDetail>
                    </v022:Segment>
                XML;
            };

            $segmentsArranged = implode('', array_map($mapSegments, $itinerary['segments'], array_keys($itinerary['segments'])));
            $fareBasis = $itinerary['fares'][0]['fare_basis'];
            $fareArranged = '';
            $itineraryIndex = $index + 1;


            return <<<XML
                <v02:Itinerary id="itinerary_$itineraryIndex">
                    {$segmentsArranged}
                    {$fareArranged}
                </v02:Itinerary>
            XML;
        };

        $header     = $this->getHeader('GetAncillaryOffersRQ', 'GetAncillaryOffersRQ', $config);
        $compCode   = $this->compCode;
        $cityCode   = $config['cityCode'];
        $subCoce    = $bagCode;
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
            $depDate        = "{$segment['dep_date']}T{$segment['dep_hour']}";
            $arrDate        = "{$segment['arr_date']}T{$segment['arr_hour']}";
            $serviceOfClass = $segment['service_class'] ?? $segment['book_code'];
            $segmentIndex   = $indexSegment + 1;

            $segmentsArranged .= <<<XML
                <v022:Segment id="segment_{$segmentIndex}">
                    <v024:FlightDetail id="flight{$segmentIndex}">
                        <v024:Airline>$compCode</v024:Airline>
                        <v024:FlightNumber>$flightNumber</v024:FlightNumber>
                        <v024:DepartureAirport>$from</v024:DepartureAirport>
                        <v024:DepartureTime>$depDate</v024:DepartureTime>
                        <v024:ArrivalAirport>$to</v024:ArrivalAirport>
                        <v024:ArrivalTime>$arrDate</v024:ArrivalTime>
                        <v024:ClassOfService>$serviceOfClass</v024:ClassOfService>
                    </v024:FlightDetail>
                </v022:Segment>
            XML;

            $itineraryIds['segments'][] = "segment_{$segmentIndex}";
        }

        /*################### FARE ###################*/
        $fareArranged = <<<XML
            <v022:FareInfo id="fare_1">
                <v024:FareBasisCode>$fareBasis</v024:FareBasisCode>
            </v022:FareInfo>
        XML;

        $itinerary .= <<<XML
            <v02:Itinerary id="itinerary_1">
                {$segmentsArranged}
                {$fareArranged}
            </v02:Itinerary>
        XML;

        foreach ($paxs as $index => $pax) {
            $paxType = $pax['type'];
            $paxIndex = $index + 1;
            $passengetSegments = implode('', array_map(function ($segmentId) {
                    return <<<XML
                        <v023:PassengerSegment segmentRef="$segmentId">
                            <v023:PassengerFare fareRef="fare_1"/>
                        </v023:PassengerSegment>
                    XML;
                }, $itineraryIds['segments']));

            $itinerary = <<<XML
                <v02:Passenger id="passenger_{$paxIndex}" type="{$paxType}">
                    <v023:PassengerItinerary>
                        $passengetSegments
                    </v023:PassengerItinerary>
                </v02:Passenger>
            XML . $itinerary;
        }
        // $getPassengers = function

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v02="http://services.sabre.com/merch/ancillary/offer/v02" xmlns:v021="http://services.sabre.com/merch/request/v02" xmlns:v022="http://services.sabre.com/merch/ancillary/v02" xmlns:v023="http://services.sabre.com/merch/passenger/v02" xmlns:v024="http://services.sabre.com/merch/common/v02">
                {$header}
                <soapenv:Body>
                    <v02:GetAncillaryOffersRQ version="2.0.0">
                        <v02:RequestType>payload</v02:RequestType>
                        <v02:RequestMode>Booking</v02:RequestMode>
                        <v02:ClientContext clientType="GSA">
                            <v021:CityCode>$cityCode</v021:CityCode>
                            <v021:AirlineCarrierCode>$compCode</v021:AirlineCarrierCode>
                            <v021:AgentCurrencyCode>BRL</v021:AgentCurrencyCode>
                        </v02:ClientContext>
                        <v02:AncillaryRequestOptions>
                            <v022:ServiceType>C</v022:ServiceType>
                            <v022:Subcode>$subCoce</v022:Subcode>
                        </v02:AncillaryRequestOptions>
                        {$itinerary}
                    </v02:GetAncillaryOffersRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }


    public function makeUpdateReservationSellAncillary(Array $data, Array $bookingInfo, Array $config) {
        $header     = $this->getHeader('UpdateReservationRQ', 'UpdateReservationRQ', $config);
        $locator    = $bookingInfo['locator'];
        $arrangedAncillaryBaggage = [];
        $ancillaryBaggageAsXml = '';

        if(!empty($data['ancillary_offers'])) {
            foreach ($data['ancillary_offers'] as $offerInfoByJourney) {
                foreach ($offerInfoByJourney as $offerInfo) {
                    if($offerInfo['original_offer']['AncillaryGroups']['@attributes']['group'] === 'BG') $arrangedAncillaryBaggage[] = $this->sellAncillaryBaggage($offerInfo);
                }
            }

        }

        $ancillaryBaggageAsXml = implode('', $arrangedAncillaryBaggage);
        return <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v1="http://webservices.sabre.com/pnrbuilder/v1_15" xmlns:v11="http://services.sabre.com/res/or/v1_8">
                $header
                <soapenv:Body>
                    <v1:UpdateReservationRQ Version="1.15.0">
                        <v1:RequestType>Stateful</v1:RequestType>
                        <v1:ReturnOptions RetrievePNR="true" IncludeUpdateDetails="true" ReturnLocator="true"/>
                        <v1:ReservationUpdateList>
                            <v1:Locator>{$locator}</v1:Locator>
                            {$ancillaryBaggageAsXml}
                        </v1:ReservationUpdateList>
                    </v1:UpdateReservationRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }
    //>>
    //Pay ancillary
    // public function makePaymentAncillary( Array $bookingInfo, Array $config, Array $cardInfo) {
    //     $header     = $this->getHeader('UpdateReservationRQ', 'UpdateReservationRQ', $config);
    //     $locator    = $bookingInfo['locator'];
    //     $arrangedAncillaryBaggage = [];
    //     $ancillaryBaggageAsXml = '';


    //     $arrangedAncillaryBaggage[] = $this->payAncillaryBaggage($bookingInfo['journeys'],$cardInfo);


    //     $ancillaryBaggageAsXml = implode('', $arrangedAncillaryBaggage);
    //     return <<<XML
    //     <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v1="http://webservices.sabre.com/pnrbuilder/v1_15" xmlns:v11="http://services.sabre.com/res/or/v1_8">
    //             $header
    //             <soapenv:Body>
    //                 <v1:UpdateReservationRQ Version="1.15.0">
    //                     <v1:RequestType>Stateful</v1:RequestType>
    //                     <v1:ReturnOptions RetrievePNR="true" IncludeUpdateDetails="true" ReturnLocator="true"/>
    //                     <v1:ReservationUpdateList>
    //                         <v1:Locator>{$locator}</v1:Locator>
    //                         {$ancillaryBaggageAsXml}
    //                     </v1:ReservationUpdateList>
    //                 </v1:UpdateReservationRQ>
    //             </soapenv:Body>
    //         </soapenv:Envelope>
    //     XML;
    // }


}