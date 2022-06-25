<?php

namespace Api\Helpers;

use Api\Interfaces\AerialRequestMaker;
use PlugHttp\Body\XML;
use SimpleXMLElement;
use Util\Formatter;

class SabreRequestMaker implements AerialRequestMaker {
    const EMD_TYPES_CODE = [
        'FLIGHT_COUPON_ASSOCIATED' => 2
    ];
    const FEE_AP_METHODS = [
        'PER_BAGGAGE_TRAVEL' => 4
    ];

    function __construct($compCode) {
        $this->compCode = $compCode;
    }

    /*################################# UTIL #################################*/
    //>>
    public function getHeader(String $method, String $service, Array $config) {
        $session = $config['session'];
        $cpaid = $config['CPAId'];
        $fromPartyId = $config['fromPartyId'];
        $toPartyId = $config['toPartyId'];
        $conversationId = $config['conversationId'];

        $header = <<<XML
           <soapenv:Header>
                <sec:Security>
                    <sec:BinarySecurityToken>$session</sec:BinarySecurityToken>
                </sec:Security>
                <mes:MessageHeader mes:id="1" mes:version="1.0.0">
                    <mes:From>
                        <mes:PartyId mes:type="URI">$fromPartyId</mes:PartyId>
                    </mes:From>
                    <mes:To>
                        <mes:PartyId mes:type="URI">$toPartyId</mes:PartyId>
                    </mes:To>
                    <mes:CPAId>$cpaid</mes:CPAId>
                    <mes:ConversationId>$conversationId</mes:ConversationId>
                    <mes:Service mes:type="SabreXML">$service</mes:Service>
                    <mes:Action>$method</mes:Action>
                    <mes:MessageData>
                        <mes:MessageId></mes:MessageId>
                        <mes:Timestamp></mes:Timestamp>
                    </mes:MessageData>
                    <mes:Description xml:lang="en-us"/>
                </mes:MessageHeader>
            </soapenv:Header>
        XML;

        return $header;
    }

    // private function genElemByArray(Array $arr) {
    //     return array_reduce($arr, function($acc, $elem) {
    //         foreach ($elem as $key =>  $value) {
    //             if(gettype($value) === 'array' && $key !== '@attributes') {
    //                 $acc .= <<<XML
    //                     <$key>
    //                         {$this->genElemByArray($value)}
    //                     <$key/>
    //                 XML;
    //             }else if($key === '@attributes') {
    //                 $attrs = '';

    //                 foreach ($value as $attrKey => $attrValue) {
    //                     $attrs .= "$attrKey=\"$attrValue\"";
    //                 }
    //                 $acc .= <<<XML
    //                     <$key $attrs />
    //                 XML;
    //             }else {
    //                 $acc .= <<<XML
    //                     <$key>$value</$key>
    //                 XML;
    //             }
    //         }
    //         return $acc;
    //     });
    // }

    private function getRouteTrip(Int $index, String $from, String $to, String $depDate) {
        $tpaExtensions = '';

        if($this->compCode === 'G3') {
            $tpaExtensions = <<<XML
                <TPA_Extensions>
                    <DateFlexibility NbrOfDays="0"/>
                    <SegmentType Code="O"/>
                    <!--<IncludeVendorPref Code="G3"/>-->
                </TPA_Extensions>
            XML;
        }

        $arramgedDepDate = $depDate . 'T00:00:00';
        return <<<XML
        <ns:OriginDestinationInformation RPH="{$index}">
            <ns:DepartureDateTime>$arramgedDepDate</ns:DepartureDateTime>
            <ns:OriginLocation LocationCode="{$from}"/>
            <ns:DestinationLocation LocationCode="{$to}"/>
            $tpaExtensions
        </ns:OriginDestinationInformation>
        XML;
    }

    private function getArrayAsAttributes(Array $elems) {
        $extra = '';

        foreach ($elems as $key => $value) {
            $extra .= " $key=\"$value\" ";
        }

        return $extra;
    }

    private function getSearchPayload(Array $body, Array $config) {
        $pcc            = $config['pcc'];
        $personalcc     = $config['personalcc'];
        $accoutingCode  = $config['accoutingCode'];
        $requestType    = $config['requestType'];
        $serviceTag     = $config['serviceTag'];
        $officeCode     = $config['officeCode'];
        $defaultTicketingCarrier = $config['defaultTicketingCarrier'];
        $priceRequestInformation = '';
        $extraAirLowFareAttrs = '';
        if($this->compCode === 'G3') {
            $extra = '';

            if(!empty($body['promo_code'])) $extra = "<ns:PromoID>{$body['promo_code']}</ns:PromoID>";

            $priceRequestInformation .= <<<XML
                <TPA_Extensions>
                    <PrivateFare Ind="true"/>
                    {$extra}
                </TPA_Extensions>
            XML;
            if(!empty($config['airLowFareSearchAttrs'])) {
                $extraAirLowFareAttrs = $this->getArrayAsAttributes($config['airLowFareSearchAttrs']);
            }
        }
        if($this->compCode === 'LA' && !empty($body['promo_code'])) {
            $priceRequestInformation .= <<<XML
                <ns:TPA_Extensions>
                    <ns:PromoID>{$body['promo_code']}</ns:PromoID>
                </ns:TPA_Extensions>
            XML;
        }
        $payload = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://www.opentravel.org/OTA/2003/05">
                {$this->getHeader('AdvancedAirShoppingRQ', 'AdvancedAirShoppingRQ', $config)}
                <soapenv:Body>
                    <ns:OTA_AirLowFareSearchRQ Target="Production" Version="5.4.0" {$extraAirLowFareAttrs} DirectFlightsOnly="false" AvailableFlightsOnly="true">
                        <ns:POS>
                            <ns:Source PseudoCityCode="$pcc" PersonalCityCode="$personalcc" AccountingCode="$accoutingCode" OfficeCode="$officeCode" DefaultTicketingCarrier="$defaultTicketingCarrier">
                            <ns:RequestorID Type="1" ID="1">
                                <ns:CompanyName Code="SSW"/>
                            </ns:RequestorID>
                            </ns:Source>
                        </ns:POS>
                        {$body['segments']}
                        <ns:TravelPreferences MaxStopsQuantity="1">
                            <ns:TPA_Extensions>
                                <ns:NumTrips Number="40"/>
                            </ns:TPA_Extensions>
                        </ns:TravelPreferences>
                        <ns:TravelerInfoSummary>
                            <ns:AirTravelerAvail>
                                {$body['paxs']}
                            </ns:AirTravelerAvail>
                            <ns:PriceRequestInformation CurrencyCode="BRL">
                                $priceRequestInformation
                            </ns:PriceRequestInformation>
                        </ns:TravelerInfoSummary>
                        <ns:TPA_Extensions>
                            <ns:IntelliSellTransaction>
                                <ns:RequestType Name="$requestType"/>
                                <ns:ServiceTag Name="$serviceTag"/>
                            </ns:IntelliSellTransaction>
                            <ns:SplitTaxes ByLeg="false" ByFareComponent="true"/>
                        </ns:TPA_Extensions>
                    </ns:OTA_AirLowFareSearchRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;

        return $payload;
    }

    private function arrangedOriginDestinatioInfo(Array $segments, int $numberInParty) {
        $arranged = '';
        $extraSegmentInfo = '';

        foreach ($segments as $segment) {
            if(empty($segment['book_code'])) throw new \Exception(getErrorMessage('missingField', 'book code'));

            $depDate = $segment['dep_date'] . 'T' . $segment['dep_hour'];
            $flightNumber = $segment['flight_number'];
            $from = $segment['from'];
            $to = $segment['to'];
            $companyCode = $segment['comp_code'];
            $booCode = $segment['book_code'];

            if($this->compCode === 'LA') {
                $extraSegmentInfo = <<<XML
                    <v3:MarriageGrp>O</v3:MarriageGrp>
                    <v3:OperatingAirline Code="{$companyCode}"/>
                XML;
            }

            $arranged .=
            <<<XML
                <v3:FlightSegment DepartureDateTime="{$depDate}" FlightNumber="{$flightNumber}" NumberInParty="$numberInParty" ResBookDesigCode="{$booCode}" Status="NN">
                    <v3:DestinationLocation LocationCode="{$to}"/>
                    <v3:MarketingAirline Code="{$companyCode}" FlightNumber="{$flightNumber}"/>
                    $extraSegmentInfo
                    <v3:OriginLocation LocationCode="{$from}"/>
                </v3:FlightSegment>
            XML;
        }

        return $arranged;
    }

    protected function passengerDetailsBody($bodyContent, $config) {
        $passengerDetailsExtraAttrs = !empty($config['passengerDetailsRQ']) ? $this->getArrayAsAttributes($config['passengerDetailsRQ']) : '';

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v3="http://services.sabre.com/sp/pd/v3_4">
                {$this->getHeader('PassengerDetailsRQ', 'PassengerDetailsRQ', $config)}
                <soapenv:Body>
                    <v3:PassengerDetailsRQ version="3.4.0" {$passengerDetailsExtraAttrs}>
                    {$bodyContent}
                    </v3:PassengerDetailsRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    /*############################ REQUEST MAKERS ############################*/

    public function makeLogon(Array $credential, Array $config) {
        if($credential['loginName'] === null || strlen($credential['loginName']) === 0) throw new \Exception(getErrorMessage('missingCredentialInformation'));
        if($credential['password'] === null || strlen($credential['password']) === 0) throw new \Exception(getErrorMessage('missingCredentialInformation'));

        if(empty($config['org']))            throw new \Exception(getErrorMessage('wsInternalRequestMissingData', 'Organization'));
        if(empty($config['domain']))         throw new \Exception(getErrorMessage('wsInternalRequestMissingData', 'Domain'));
        if(empty($config['conversationId'])) throw new \Exception(getErrorMessage('wsInternalRequestMissingData', 'Conversation id'));
        if(empty($config['pcc']))            throw new \Exception(getErrorMessage('wsInternalRequestMissingData', 'PCC'));

        $loginName  = $credential['loginName'];
        $pwd        = $credential['password'];

        $org                = $config['org'];
        $domain             = $config['domain'];
        $fromPartyId        = $config['fromPartyId'];
        $toPartyId          = $config['toPartyId'];
        $conversationId     = $config['conversationId'];
        $pcc                = $config['pcc'];

        $payload = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://www.opentravel.org/OTA/2002/11">
            <soapenv:Header>
                <sec:Security>
                    <sec:UsernameToken>
                        <sec:Username>$loginName</sec:Username>
                        <sec:Password>$pwd</sec:Password>
                        <Organization>$org</Organization>
                        <Domain>$domain</Domain>
                    </sec:UsernameToken>
                </sec:Security>
                <mes:MessageHeader mes:id="1" mes:version="1.0">
                    <mes:From>
                        <mes:PartyId mes:type="URI">$fromPartyId</mes:PartyId>
                    </mes:From>
                    <mes:To>
                        <mes:PartyId mes:type="URI">$toPartyId</mes:PartyId>
                    </mes:To>
                    <mes:CPAId/>
                    <mes:ConversationId>$conversationId</mes:ConversationId>
                    <mes:Service mes:type="OTA">SessionCreateRQ</mes:Service>
                    <mes:Action>SessionCreateRQ</mes:Action>
                    <mes:MessageData>
                        <mes:MessageId></mes:MessageId>
                        <mes:Timestamp></mes:Timestamp>
                    </mes:MessageData>
                    <mes:Description xml:lang="en-us"/>
                </mes:MessageHeader>
            </soapenv:Header>
            <soapenv:Body>
                <ns:SessionCreateRQ returnContextID="true">
                    <ns:POS>
                        <ns:Source PseudoCityCode="{$pcc}"/>
                    </ns:POS>
                </ns:SessionCreateRQ>
            </soapenv:Body>
            </soapenv:Envelope>
            XML;

        return $payload;
    }

    public function makeSearch(Array $segment, Array $paxs, Array $config, Array $extraMisc = []) {

        $segments = '';
        $arrangePaxs = function ($paxs) {
            $arranged = '';

            if($paxs['adults'] > 0) {
                $arranged .= <<<XML
                    <ns:PassengerTypeQuantity Code="ADT" Quantity="{$paxs['adults']}"/>
                XML;
            }
            if($paxs['childs'] > 0) {
                $arranged .= <<<XML
                    <ns:PassengerTypeQuantity Code="CNN" Quantity="{$paxs['childs']}"/>
                XML;
            }
            if($paxs['infs'] > 0) {
                $arranged .= <<<XML
                    <ns:PassengerTypeQuantity Code="INF" Quantity="{$paxs['infs']}"/>
                XML;
            }
            return $arranged;
        };

        //xml de rota
        $segments = $this->getRouteTrip(1, $segment['from'], $segment['to'], $segment['dep_date']);

        $body = [
            'segments' => $segments,
            'paxs' => $arrangePaxs($paxs)
        ];



        if(!empty($extraMisc['promo_code'])) $body['promo_code'] = $extraMisc['promo_code'];
        $payload = $this->getSearchPayload($body, $config);

        return $payload;
    }

    public function searchCombined(Array $request, Array $config, Array $extraMisc = []) {
        $tripInfo = $request['trip_info'][0];
        $segments = '';
        $arrangePaxs = function ($paxs) {
            $arranged = '';

            if($paxs['adults'] > 0) {
                $arranged .= <<<XML
                    <ns:PassengerTypeQuantity Code="ADT" Quantity="{$paxs['adults']}"/>
                XML;
            }
            if($paxs['childs'] > 0) {
                $arranged .= <<<XML
                    <ns:PassengerTypeQuantity Code="CNN" Quantity="{$paxs['childs']}"/>
                XML;
            }
            if($paxs['infs'] > 0) {
                $arranged .= <<<XML
                    <ns:PassengerTypeQuantity Code="INF" Quantity="{$paxs['infs']}"/>
                XML;
            }
            return $arranged;
        };

        $segments = $this->getRouteTrip(1, $tripInfo['from'], $tripInfo['to'], $tripInfo['dep_date']);

        if($request['combined_flights']) {
            $segments .= $this->getRouteTrip(2, $tripInfo['to'], $tripInfo['from'], $tripInfo['back_date']);
        }

        $body = [
            'segments' => $segments,
            'paxs' => $arrangePaxs($request['pax_info'])
        ];
        if(!empty($extraMisc['promo_code'])) $body['promo_code'] = $extraMisc['promo_code'];
        $payload = $this->getSearchPayload($body, $config);

        return $payload;
    }

    public function makeEnhancedAirBook(Array $req, Array $paxs, Array $config) {
        $arrangePaxs = function($paxs) {
            $arranged = '';

            if($paxs['adults'] > 0) {
                $arranged .= <<<XML
                    <v3:PassengerType Code="ADT" Force="false" Quantity="{$paxs['adults']}"/>
                XML;
            }
            if($paxs['childs'] > 0) {
                $arranged .= <<<XML
                    <v3:PassengerType Code="CNN" Force="false" Quantity="{$paxs['childs']}"/>
                XML;
            }
            if($paxs['infs'] > 0) {
                $arranged .= <<<XML
                    <v3:PassengerType Code="INF" Force="false" Quantity="{$paxs['infs']}"/>
                XML;
            }
            return $arranged;
        };
        $journeys = $req['trip_info'];
        $arrangedFlightDestination = '';
        $req = '';
        $paxsSum = $paxs['adults'] + $paxs['childs'] + $paxs['infs'];

        $arrangedFlightDestination .= array_reduce($journeys, function ($acc, $journey) use($paxsSum) {
            return $acc . $this->arrangedOriginDestinatioInfo($journey['segments'], $paxsSum);
        }, '');

        $pricingQualifiers = '';
        // PostProcessing and PreProcessing.
        $processingMethods = '';

        if($this->compCode === 'G3') {
            $pricingQualifiers = <<<XML
                <v3:Brand>MX</v3:Brand>
            XML;
            $processingMethods = <<<XML
                <v3:PostProcessing IgnoreAfter="false">
                    <v3:RedisplayReservation/>
                </v3:PostProcessing>
                <v3:PreProcessing IgnoreBefore="false"/>
            XML;
        }else if ($this->compCode === 'LA') {
            $pricingQualifiers = <<<XML
                <v3:BargainFinder Rebook="true"/>
            XML;
        }

        $req = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v3="http://services.sabre.com/sp/eab/v3_10">
                {$this->getHeader('EnhancedAirBookRQ', 'EnhancedAirBookRQ', $config)}
                <soapenv:Body>
                    <!--version="3.2.0"-->
                    <!--"HaltOnError" type="xs:boolean" use="optional"-->
                    <!--HaltOnError="true" (if true) tells the system to halt processing if any errors are returned from the low level invocation-->
                    <!--"IgnoreOnError" type="xs:boolean" use="optional"-->
                    <!--IgnoreOnError="false" (if true) tells the system to ignore the entire transaction if any errors are encountered.-->
                    <!--Equivalent Sabre host command: I-->
                    <!--EnhancedAirBookRQ contains OTA_AirbookRQ and OTA_AirPriceRQ. They are Orchestrated Services-->
                    <v3:EnhancedAirBookRQ version="3.10.0" HaltOnError="true" IgnoreOnError="false">
                        <v3:OTA_AirBookRQ>
                            <v3:HaltOnStatus Code="NO"/>
                            <v3:HaltOnStatus Code="NN"/>
                            <v3:HaltOnStatus Code="UC"/>
                            <v3:HaltOnStatus Code="US"/>
                            <v3:OriginDestinationInformation>
                                {$arrangedFlightDestination}
                            </v3:OriginDestinationInformation>
                            <!--"RedisplayReservation" minOccurs="0"-->
                            <!--"NumAttempts" use="required" base="xs:integer" minInclusive/@value="1" maxInclusive/@value="10" default="2"-->
                            <!--"WaitInterval" use="required" base="xs:integer" minInclusive/@value="100" maxInclusive/@value="10000" default="5000"-->
                            <v3:RedisplayReservation NumAttempts="2" WaitInterval="5000"/>
                        </v3:OTA_AirBookRQ>
                        <v3:OTA_AirPriceRQ>
                            <v3:PriceRequestInformation Retain="true">
                            <v3:OptionalQualifiers>
                                <v3:PricingQualifiers>
                                    <!--Optional:-->
                                    <!--Host Command: Rebook="true" WPNCB-->
                                    <!--Host Command: Rebook="false" WPNC-->
                                    {$pricingQualifiers}
                                    {$arrangePaxs($paxs)}
                                </v3:PricingQualifiers>
                            </v3:OptionalQualifiers>
                            </v3:PriceRequestInformation>
                        </v3:OTA_AirPriceRQ>
                        $processingMethods
                    </v3:EnhancedAirBookRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;

        return $req;
    }

    public function makePassengerDetailsPaxs(Array $request, Int $user, Array $config) {
        $arranged = '';
        $arrangedPax = '';
        $paxs = [];
        $bodyContent = '';
        $travelItineraryAddInfo = '';

        $paxs = $request['pax_info'];
        $arrangedPax = array_reduce($paxs, function ($acc, $pax) {
            $paxType = $pax['type'];
            $isInfant = $paxType === 'INF' ? 'true' : 'false';
            $firstName = $pax['first_name'];
            $lastName = $pax['last_name'];

            return $acc . <<<XML
             <v3:PersonName Infant="{$isInfant}" NameReference="{$paxType}">
                <!--GivenName="StringLength1to57"-->
                <v3:GivenName>$firstName</v3:GivenName>
                <!--Surname="StringLength1to57"-->
                <v3:Surname>$lastName</v3:Surname>
            </v3:PersonName>

            XML;
        }, '');

        if($this->compCode === 'LA') {
            $travelItineraryAddInfo = <<<XML
            <v3:TravelItineraryAddInfoRQ>
                <v3:AgencyInfo>
                <!--Address: This is the only one need to be send separate functionality-->
                    <v3:Address>
                        <v3:AddressLine>ELATAM WEBSERVICE</v3:AddressLine>
                        <v3:CityName>SAO PAULO</v3:CityName>
                        <v3:CountryCode>BR</v3:CountryCode>
                        <v3:PostalCode>03112</v3:PostalCode>
                        <v3:StateCountyProv StateCode="SP"/>
                        <v3:StreetNmbr>2001 VERBO DIVINO ST - TOWER A</v3:StreetNmbr>
                    </v3:Address>
                    <v3:Ticketing TicketType="82359"/>
                </v3:AgencyInfo>
                <v3:CustomerInfo>
                    <v3:ContactNumbers>
                        <v3:ContactNumber Phone="99768594" PhoneUseType=""/>
                        <v3:ContactNumber Phone="11-5582-9382" PhoneUseType="A"/>
                    </v3:ContactNumbers>
                    <!--Optional-->
                    <!--Repeat Factor=1-99-->
                    <v3:Email Address="naoresponder@latam.com" Type="TO"/>
                    <!--NameReference="StringLength1to30"-->
                    {$arrangedPax}
                </v3:CustomerInfo>
            </v3:TravelItineraryAddInfoRQ>
            XML;
        }else if($this->compCode === 'G3') {
            $travelItineraryAddInfo = <<<XML
                <v3:TravelItineraryAddInfoRQ>
                    <AgencyInfo>
                        <Ticketing TicketType="7TAW"/>
                    </AgencyInfo>
                    <CustomerInfo>
                        <ContactNumbers>
                            <ContactNumber NameNumber="1.1" Phone="817-555-1212" PhoneUseType="H"/>
                        </ContactNumbers>
                        <Email Address="contato@ecomponent.com"/>
                        {$arrangedPax}
                    </CustomerInfo>
                </v3:TravelItineraryAddInfoRQ>
            XML;
        }

        $bodyContent .= $travelItineraryAddInfo;

        $arranged = $this->passengerDetailsBody($bodyContent, $config);
        // print_r($arranged);
        // die();

        return $arranged;
    }

    public function makePassengerDetailsEndTransaction($config) {
        $arranged = '';
        $bodyContent = '';
        $header = [];

        $bodyContent = <<<XML
            <v3:PostProcessing>
                <v3:EndTransactionRQ>
                    <v3:EndTransaction Ind="true"/>
                    <v3:Source ReceivedFrom="APPNAME/USERNAME"/>
                </v3:EndTransactionRQ>
            </v3:PostProcessing>
        XML;

        $arranged = $this->passengerDetailsBody($bodyContent, $config);
        return $arranged;
    }

    public function makeGetBooking($req, $config) {
        $locator = $req['loc'];

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v1="http://webservices.sabre.com/pnrbuilder/v1_19">
                {$this->getHeader('getReservationRQ', 'getReservationRQ', $config)}
                <soapenv:Body>
                    <v1:GetReservationRQ xmlns:ns7 = "http://webservices.sabre.com/pnrbuilder/v1_19" Version = "1.19.0">
                    <v1:Locator>$locator</v1:Locator>
                    <v1:RequestType>Statefull</v1:RequestType>
                    <v1:ReturnOptions ShowTicketStatus="true" PriceQuoteServiceVersion="3.2.0">
                        <v1:SubjectAreas>
                            <v1:SubjectArea>PRICE_QUOTE</v1:SubjectArea>
                        </v1:SubjectAreas>
                        <v1:ViewName>Full</v1:ViewName>
                        <v1:ResponseFormat>STL</v1:ResponseFormat>
                </v1:ReturnOptions>
                </v1:GetReservationRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function makeEnhancedSeatMap(Array $segment, String $serviceClass, Array $config) {
        $header         = $this->getHeader('EnhancedSeatMapRQ', 'EnhancedSeatMapRQ', $config);
        $from           = $segment['from'];
        $to             = $segment['to'];
        $flightNumber   = $segment['flight_number'];
        $depDate        = $segment['dep_date'];

        $compCode = $config['compCode'];
        $cityCode = $config['cityCode'];
        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v3="http://stl.sabre.com/Merchandising/v5" xmlns:v02="http://opentravel.org/common/message/v02">
                {$header}
                <soapenv:Body>
                    <v3:EnhancedSeatMapRQ version="5.0.0">
                        <v3:SeatMapQueryEnhanced>
                            <v3:RequestType>Stateful</v3:RequestType>
                            <v3:Flight destination="$to" origin="$from">
                                <v3:DepartureDate localTime="08:40:00.0000000-03:00">$depDate</v3:DepartureDate>
                                <v3:Marketing carrier="LA">$flightNumber</v3:Marketing>
                            </v3:Flight>
                            <v3:CabinDefinition>
                                <v3:RBD>$serviceClass</v3:RBD>
                            </v3:CabinDefinition>
                            <v3:Currency>BRL</v3:Currency>
                            <v3:FareAvailQualifiers>
                                <v3:TravellerID>1</v3:TravellerID>
                            </v3:FareAvailQualifiers>
                            <v3:POS multiHost="$compCode" company="$compCode">
                                <v3:Actual city="$cityCode"/>
                            </v3:POS>
                        </v3:SeatMapQueryEnhanced>`
                    </v3:EnhancedSeatMapRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function makePayment(Array $paymentInfo, Array $config) {
        $locator    = $config['locator'];
        $timeStamp  = $config['timeStamp'];
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
        $arrangePayments = function(Array $payment) {
            list(
                'type' => $type,
                'code' => $code,
                'acc_holder_name' => $accHolderName,
                'acc_sec_code' => $accSecCode,
                'acc_number' => $accNumber,
                'exp_date' => $expDate,
                //'num_instal' => $numInstal,
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
        $arrangedSegments = implode(' ', array_map($arrangeSegments, $paymentInfo['segments']));
        $arrangedPayments = implode(' ', array_map($arrangePayments, $paymentInfo['payments']));


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
    //>>
    public function makeCollectMiscFee(Array $paymentInfo, Array $config, Array $payAncillary) {
        print_r($paymentInfo);exit;
        $optionalService = $paymentInfo['optionalService'];
        $timeStamp  = $config['timeStamp'];
        $stationNr  = $config['stationNr'];
        $channelId  = $config['channelId'];
        $merchantId = $config['domain'];
        $compCode = $config['compCode'];
        $AccountingCity = $config['AccountingCity'];
        $arrangedSegments   = [];
        $arrangedPaxs       = [];
        $arrangedPayments   = [];

        $arrangePaxs = function (Array $pax) use ($config) {
            $firstName   = $pax['first_name'];
            $lastName    = $pax['last_name'];
            $stationCode = $config['stationCode'];
            $locator     = $config['locator'];
            $idCia       = $pax['id_cia'];

            return <<<XML
                <v01:Customer lastName="$lastName" firstName="$firstName">
                  <v01:CustomerDetails nameRefNumber="$idCia" pnrLocator="$locator">
                     <v01:AgencyIataNumber>$stationCode</v01:AgencyIataNumber>
                  </v01:CustomerDetails>
               </v01:Customer>
            XML;
        };

        $fee = function(Array $seg) use ($optionalService) {
            $rficCode = $optionalService['rficCode'];
            $compCode   = $seg['comp_code'];
            $from       = $seg['from'];
            $to         = $seg['to'];
            $flightNumber = $seg['flight_number'];
            $serviceClass = $seg['service_class'];
            $depDate    = "{$seg['dep_date']}T{$seg['dep_hour']}";
            $arrDate    = "{$seg['arr_date']}T{$seg['arr_hour']}";

            return <<<XML
               <v01:Fee>
                  <v01:FeeDetails code="0C3" quantity="1">
                     <v01:Base currencyCode="BRL">130.00</v01:Base>
                     <!--v01:Equiv currencyCode="BRL">0.00</v01:Equiv-->
                     <!--v01:TotalTax currencyCode=""/-->
                     <v01:Total currencyCode="BRL">130.00</v01:Total>
                  </v01:FeeDetails>
                  <v01:OptionalService RFIC="C" subCode="0C3" ssrCode="ABAG" name="1ST 23KG" group="BG" segmentIndicator="P">
                     <!--EmdType="Standalone/Associated/Reference/Other/ElectronicTicket"-->
                     <v01:EmdType>Associated</v01:EmdType>
                     <v01:AirExtraItemNumber>35</v01:AirExtraItemNumber>
                     <v01:OwningCarrierCode>LA</v01:OwningCarrierCode>
                     <v01:Vendor>ATP</v01:Vendor>
                     <v01:IATAParameters commission="N" refundable="Y" interline="Y"/>
                  </v01:OptionalService>
                  <!--Zero or more repetitions:-->
                  <v01:AssociatedFlight segmentNumber="1">
                     <v01:CarrierCode>LA</v01:CarrierCode>
                     <v01:FlightNumber>3299</v01:FlightNumber>
                     <v01:ClassOfService>H</v01:ClassOfService>
                     <v01:DepartureCity>FLN</v01:DepartureCity>
                     <v01:ArrivalCity>GRU</v01:ArrivalCity>
                     <v01:DepartureDate>2022-06-26</v01:DepartureDate>
                     <v01:AssociatedTicketNumber couponNumber="1">{#TestSuite#VCR}</v01:AssociatedTicketNumber>
                  </v01:AssociatedFlight>
               </v01:Fee>
            XML;
        };

        $optionalService = function(Array $seg) use ($optionalService) {
            $rficCode = $optionalService['rficCode'];
            $compCode   = $seg['comp_code'];
            $from       = $seg['from'];
            $to         = $seg['to'];
            $flightNumber = $seg['flight_number'];
            $serviceClass = $seg['service_class'];
            $depDate    = "{$seg['dep_date']}T{$seg['dep_hour']}";
            $arrDate    = "{$seg['arr_date']}T{$seg['arr_hour']}";

            return <<<XML
               <!--1 to 99 repetitions:-->

                  <v01:OptionalService RFIC="$rficCode" subCode="0C3" ssrCode="ABAG" name="1ST 23KG" group="BG" segmentIndicator="P">
                     <!--EmdType="Standalone/Associated/Reference/Other/ElectronicTicket"-->
                     <v01:EmdType>Associated</v01:EmdType>
                     <v01:AirExtraItemNumber>35</v01:AirExtraItemNumber>
                     <v01:OwningCarrierCode>LA</v01:OwningCarrierCode>
                     <v01:Vendor>ATP</v01:Vendor>
                     <v01:IATAParameters commission="N" refundable="Y" interline="Y"/>
                  </v01:OptionalService>
            XML;
        };

        $associatedFlight = function(Array $seg) use ($optionalService) {
            $compCode   = $seg['comp_code'];
            $from       = $seg['from'];
            $to         = $seg['to'];
            $flightNumber = $seg['flight_number'];
            $serviceClass = $seg['service_class'];
            $depDate    = "{$seg['dep_date']}T{$seg['dep_hour']}";
            $arrDate    = "{$seg['arr_date']}T{$seg['arr_hour']}";

            return <<<XML
                  <v01:AssociatedFlight segmentNumber="1">
                     <v01:CarrierCode>LA</v01:CarrierCode>
                     <v01:FlightNumber>3299</v01:FlightNumber>
                     <v01:ClassOfService>B</v01:ClassOfService>
                     <v01:DepartureCity>FLN</v01:DepartureCity>
                     <v01:ArrivalCity>GRU</v01:ArrivalCity>
                     <v01:DepartureDate>2022-06-20</v01:DepartureDate>
                     <v01:AssociatedTicketNumber couponNumber="1">{#TestSuite#VCR}</v01:AssociatedTicketNumber>
                  </v01:AssociatedFlight>
            XML;
        };
        $arrangePayments = function(Array $payment) {
            list(
                'type' => $type,
                'code' => $code,
                'acc_holder_name' => $accHolderName,
                'acc_sec_code' => $accSecCode,
                'acc_number' => $accNumber,
                'exp_date' => $expDate,
                //'num_instal' => $numInstal,
                'value' => $value
            ) = $payment;

            $expDate = date('mY', strtotime($expDate));
            return <<<XML
                <beta:PaymentDetail>
                    <beta:FOP Type="$type" FOP_Code="$type"/>
                    <!--PaymentCard CardCode="AX" CardNumber="370000000000002"  ExpireDate="082018"   -->
                    <!--PaymentCard CardCode="CA" CardNumber="5555555555554444" ExpireDate="082018"   -->
                    <!--PaymentCard CardCode="DC" CardNumber="36006666333344"   ExpireDate="082018"   -->
                    <!--PaymentCard CardCode="EL" CardNumber="5066991111111118" ExpireDate="082018"   -->
                    <!--PaymentCard CardCode="HC" CardNumber="6062828888666688" ExpireDate="082018"   -->
                    <!--PaymentCard CardCode="VI" CardNumber="4988438843884305" ExpireDate="082018"   -->
                    <!--PaymentCard CardCode="VI" CardNumber="4444333322221111" ExpireDate="082018" ExtendPayment="12"-->
                    <beta:PaymentCard CardCode="$code" CardNumber="$accNumber" CardSecurityCode="$accSecCode" ExpireDate="$expDate">
                    <beta:CardHolderName Name="$accHolderName"/>
                    </beta:PaymentCard>
                    <!--Optional:-->
                    <beta:AmountDetail Amount="$value" CurrencyCode="BRL"/>
                </beta:PaymentDetail>
            XML;
        };

        $segments = !empty($paymentInfo['segments']) ? $paymentInfo['segments'] : $paymentInfo['journeys'][0]['segments'];

        $arrangedPaxs     = implode(' ', array_map($arrangePaxs, $paymentInfo['paxs']));
        $arrangedSegments = implode(' ', array_map($arrangedSegments, $segments));
        $arrangedPayments = implode(' ', array_map($arrangePayments, $paymentInfo['payments']));

        return <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:beta="http://www.opentravel.org/OTA/2003/05/beta">
                {$this->getHeader('MISCServicesRQ', 'CollectMiscFeeRQ', $config)}
        <soapenv:Body>
        <ns:CollectMiscFeeRQ version="1.3.7">
           <v01:Header/>
           <v01:AgentPOS company="$compCode" lniata="000000" dutyCode="4">
              <v01:AAA>$AccountingCity</v01:AAA>
           </v01:AgentPOS>
           <!--code="EMD" Fixed-->
           <v01:Transaction code="EMD"/>
           <!--1 to 99 repetitions:-->
           <v01:Fees>
              <!--You have a CHOICE of the next 2 items at this level-->
              <v01:Linked>
                $arrangePaxs
            <v01:Fee>
            $fee
            $optionalService
            $associatedFlight
            </v01:Fee>
            </v01:Linked>
              <!-- FIM  -->
        XML;
    }


    public function makePassengerDetailsSeatAssign(Array $assignsInfo, Array $config) {
        $seats = implode('', array_map(function ($assignInfo) {
            $sequence = $assignInfo['sequence_index'];

            return implode('', array_map(function($seat) use($sequence) {
                $nameNumber = $seat['pax_index'];
                $number     = "{$seat['row']}{$seat['column']}";

                return <<<XML
                    <v3:Seat NameNumber="$nameNumber" Number="$number" SegmentNumber="$sequence"/>
                XML;
            }, $assignInfo['seats']));

        }, $assignsInfo['segments']));

        $seatAssignXML = <<<XML
            <v3:PostProcessing>
                <v3:EndTransactionRQ>
                <v3:EndTransaction Ind="true"/>
                <v3:Source ReceivedFrom="SOAPUI-AirSeatRQ"/>
                </v3:EndTransactionRQ>
            </v3:PostProcessing>
            <v3:SpecialReqDetails>
                <v3:AirSeatRQ>
                <v3:Seats>
                    <!--1 to 98 repetitions:-->
                    $seats
                </v3:Seats>
                </v3:AirSeatRQ>
            </v3:SpecialReqDetails>
        XML;

        return $this->passengerDetailsBody($seatAssignXML, $config);
    }

    public function makeTravelItineraryRead(String $locator, Array $config) {
        $header = $this->getHeader('MISCServicesRQ', 'MISCServicesRQ', $config);

        $timeStamp = $config['timeStamp'];

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:v3="http://services.sabre.com/res/tir/v3_8">
                $header
                <soapenv:Body>
                    <v3:TravelItineraryReadRQ TimeStamp="$timeStamp" Version="3.8.0">
                        <v3:MessagingDetails>
                            <v3:SubjectAreas>
                            <v3:SubjectArea>FULL</v3:SubjectArea>
                            </v3:SubjectAreas>
                        </v3:MessagingDetails>
                        <v3:UniqueID ID="$locator"/>
                        <v3:ReturnOptions UnmaskCreditCard="false"/>
                    </v3:TravelItineraryReadRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function makeContextChangeLLR(Array $data, Array $config) {
        $header = $this->getHeader('ContextChangeLLSRQ', 'ContextChangeRQ', $config);

        $timeStamp = $config['timeStamp'];
        $code = $data['changeDutyCode'];

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://webservices.sabre.com/sabreXML/2011/10">
                $header
                <soapenv:Body>
                    <ns:ContextChangeRQ ReturnHostCommand="true" TimeStamp="$timeStamp" Version="2.0.3">
                        <ns:ChangeDuty Code="$code"/>
                    </ns:ContextChangeRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function make(Array $data, Array $config) {
        $header = $this->getHeader('SRQ', 'SRQ', $config);

        $timeStamp  = $config['timeStamp'];
        $code       = $data['countryCode'];

        return <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://webservices.sabre.com/sabreXML/2011/10">
            $header
            <soapenv:Body>
                <ns:RQ ReturnHostCommand="true" TimeStamp="$timeStamp" Version="2.0.1">
                    <!--Optional:-->
                    <ns:Printers>
                        <ns:Ticket CountryCode="$code"/>
                    </ns:Printers>
                </ns:RQ>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
    }

    public function makeAirTicket(Array $paymnet, Array $paymentResponse, Array $config) {
        $header = $this->getHeader('AirTicketLLSRQ', 'AirTicketRQ', $config);

        $timeStamp  = $config['timeStamp'];
        list(
            'code' => $code,
            'acc_number' => $accNumber,
            'exp_date' => $expDate
        ) = $paymnet;
        list(
            'approval_code' => $approvalCode,
            'supplier_trans_id' => $supplierTransID
        ) = $paymentResponse;
        $id = $approvalCode . '*ID' . $supplierTransID;

        $expDate = date('Y-m', strtotime($expDate));
        return <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://webservices.sabre.com/sabreXML/2011/10">
            $header
            <soapenv:Body>
                <ns:AirTicketRQ NumResponses="1" ReturnHostCommand="true" TimeStamp="$timeStamp" Version="2.9.0">
                    <ns:OptionalQualifiers>
                        <ns:FOP_Qualifiers>
                            <ns:SabreSonicTicketing>
                                <ns:BasicFOP>
                                    <ns:CC_Info>
                                        <ns:PaymentCard Code="$code" ExpireDate="$expDate" ManualApprovalCode="$id" Number="$accNumber"/>
                                    </ns:CC_Info>
                                </ns:BasicFOP>
                            </ns:SabreSonicTicketing>
                        </ns:FOP_Qualifiers>
                        <ns:MiscQualifiers>
                            <ns:Ticket Type="VCR"/>
                        </ns:MiscQualifiers>
                        <ns:PricingQualifiers>
                            <ns:PriceQuote>
                                <ns:Record Number="1" Reissue="false"/>
                            </ns:PriceQuote>
                        </ns:PricingQualifiers>
                    </ns:OptionalQualifiers>
                </ns:AirTicketRQ>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
    }

    public function makeVoidTicket(String $ticketNumber, Array $config) {
        $header = $this->getHeader('VoidTicketLLSRQ', 'VoidTicketLLSRQ', $config);

        $timeStamp = $config['timeStamp'];

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://webservices.sabre.com/sabreXML/2011/10">
                $header
                <soapenv:Body>
                    <ns:VoidTicketRQ ReturnHostCommand="true" TimeStamp="{$timeStamp}" Version="2.0.2">
                        <ns:Ticketing eTicketNumber="{$ticketNumber}"/>
                    </ns:VoidTicketRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function makeCancelIssue(Array $config) {
        $header = $this->getHeader('OTA_CancelLLSRQ', 'OTA_CancelLLSRQ', $config);

        $timeStamp = $config['timeStamp'];

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://webservices.sabre.com/sabreXML/2011/10">
                $header
                <soapenv:Body>
                    <ns:OTA_CancelRQ ReturnHostCommand="true" TimeStamp="{$timeStamp}" Version="2.0.0">
                        <ns:Segment Type="entire"/>
                    </ns:OTA_CancelRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function makeTicketingDocument(Array $data, Array $config) {
        $header = '';
        $localIssueDateComponent = '';
        $reservationComponent = '';
        list(
            'locator' => $locator,
            'issue_date' => $issueDate
        ) = $data;
        list(
            'domain' => $ticketingProvider
        ) = $config;
        $header = $this->getHeader('TicketingDocumentServicesRQ', 'TicketingDocumentServicesRQ', $config);

        if(!empty($issueDate)) $localIssueDateComponent = "<dc:LocalIssueDate>{$issueDate}</dc:LocalIssueDate> <!--Data Atual -->";
        if(!empty($locator)) {
            $reservationComponent = <<<XML
                <dc:Reservation pnrLocator="{$locator}">
                    {$localIssueDateComponent}
                </dc:Reservation>
            XML;
        }

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:dc="http://www.sabre.com/ns/Ticketing/DC" xmlns:v01="http://services.sabre.com/STL/v01">
                $header
                <soapenv:Body>
                    <dc:GetTicketingDocumentRQ Version="3.26.0">
                        <v01:STL_Header.RQ/>
                        <v01:POS/>
                        <dc:SearchParameters resultType="A">
                            <dc:TicketingProvider>$ticketingProvider</dc:TicketingProvider>
                            <!---->
                            <!--<dc:DocumentType></dc:DocumentType>-->
                            <!--<dc:DocumentNumber>1272100022239</dc:DocumentNumber>-->
                            $reservationComponent
                        </dc:SearchParameters>
                    </dc:GetTicketingDocumentRQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    public function makeAERTicket(Array $data, Array $config) {
        $header         = '';
        $transaction    = '';
        $refound        = '';
        $exchDocType    = '';
        $exchDocCupons  = '';
        $newDocPax      = '';
        $newDocFare     = '';

        $pax            = $data['pax'];

        $header = $this->getHeader('AERRQ', 'AERRQ', $config);
        list(
            'serial_number' => $serialNumber,
            'accounting_code' => $accountingCode,
            'type' => $documentType
        ) = $data;

        /**
         * Possible actions:
         *  - refound
         *  - ticket_retained
         */
        if($data['transaction_action'] === 'refund') {
            $transaction = <<<XML
            <ns:Transaction Action="Refund">
               <ns:SubAction Retain="true"/>
            </ns:Transaction>
            XML;
        }else {
            list(
                'type' => $paymentType,
                'total' => $paymentTotal,
                'card' => $paymentCard
            ) = $data['payment'];

            $paymentTypeCompost = $paymentType === 'CC' ? 'CreditCard' : '';
            $transaction = <<<XML
            <ns:Transaction Action="TicketRetained"/>
            XML;

            $refound = <<<XML
            <ns:Refund Type="{$paymentTypeCompost}">
                <ns:FormOfPayment Code="{$paymentType}">
                    <ns:Credit Vendor="{$paymentCard['type']}" MaskedNumber="{$paymentCard['number']}"/>
                </ns:FormOfPayment>
                <ns:Total CurrencyCode="BRL" Amount="{$paymentTotal}" DecimalPlaces="2"/>
            </ns:Refund>
            XML;
        }

        /**
         * Possible types:
         *  - EMD
         *  - TKT
         */
        if($documentType === 'EMD') {
            $exchDocType = '<ns:Type Database="true" Flight="false" NonFlight="E"/>';
            $exchDocCupons = '<ns:Coupons Flown="U" Booklet="1" Transaction="R"/>';

            if($data['transaction_action'] === 'refund') {
                $newDocFare = <<<XML
                    <ns:Fare>
                      <ns:Fees CurrencyCode="BRL" DecimalPlaces="2" FeeCode="CHG" Raw="W01">W01</ns:Fees>
                   </ns:Fare>
                XML;
            }
            $refound = '<ns:Refund Type="StandAloneEMD"/>';
        }else {
            $exchDocType = '<ns:Type/>';
        }

        if($documentType === 'TKT' || ($documentType === 'EMD' && $data['transaction_action'] === 'ticket_retained')) {
            $newDocPax = <<<XML
                <ns:Passenger>
                    <ns:TravelerRefNumber>{$pax['id_cia']}</ns:TravelerRefNumber>
                </ns:Passenger>
            XML;
        }

        return <<<XML
           <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://services.sabre.com/ticketing/aer/1.0">
                $header
                <soapenv:Body>
                <ns:AER_RQ version="1.5.6">
                    <ns:AERDetails>
                        $transaction
                        <ns:ExchDoc>
                            <ns:Number PlatingCarrier="{$accountingCode}">$serialNumber</ns:Number>
                            $exchDocType
                            $exchDocCupons
                        </ns:ExchDoc>
                        <ns:NewDoc>
                            $newDocPax
                            $newDocFare
                        </ns:NewDoc>
                        $refound
                    </ns:AERDetails>
                </ns:AER_RQ>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }
}