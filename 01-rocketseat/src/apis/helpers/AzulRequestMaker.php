<?php

namespace Api\Helpers;

use \Util\Validator;

require_once 'src/apis/config/requestConsts.php';

class AzulRequestMaker {

    private function getSessionBody($session) {
        return <<<XML
            <book:session>
                <com:SessionControl>OneOnly</com:SessionControl>
                <com:SystemType>Default</com:SystemType>
                <com:SessionID>0</com:SessionID>
                <com:SequenceNumber>0</com:SequenceNumber>
                <com:MessageVersion>0</com:MessageVersion>
                <com:Signature>00000000-0000-0000-0000-000000000000</com:Signature>
                <!--Optional:-->
                <com:SessionPrincipal>
                <!--Optional:-->
                <com:RoleCode>H4sIAAAAAAAEAItWCnE0MFaKBQBV666jCAAAAA==</com:RoleCode>
                <!--Optional:-->
                <com:RoleName>?</com:RoleName>
                <!--Optional:-->
                <com:RoleFullName>?</com:RoleFullName>
                </com:SessionPrincipal>
                <!--Optional:-->
                <com:LocationCode>?</com:LocationCode>
                <!--Optional:-->
                <com:CultureCode>?</com:CultureCode>
                <com:ChannelType>Default</com:ChannelType>
                
                <com:TransactionDepth>0</com:TransactionDepth>
                <com:TransactionCount>0</com:TransactionCount>
                <!--Optional:-->
                <com:SecureToken>$session</com:SecureToken>
            </book:session>
        XML;
    }
    // ################################ Final Structures
    public function availabilityRequest(Array $route, Array $paxs, Array $extra) {
        $request = [];

        $paxsPriceTypes = $this->getPaxPriceTypes($paxs);
        $request = array_merge($request, array("availabilityRequest" => array(
            "TADepartureDate" => '1999-01-01',
            "DepartureStation" => $route["from"],
            "ArrivalStation" => $route["to"],
            "BeginDate" => $route['dep_date'],
            "EndDate" => $route['dep_date'],
            "FlightType" => "All",
            "PaxCount" => count($paxsPriceTypes),
            "CurrencyCode" => CURRENCY_CODE,
            "FareTypes" => FARE_TYPES,
            "Dow" => "Daily",
            "AvailabilityType" => "Default",
            "MaximumConnectingFlights" => 32767,
            "AvailabilityFilter" => "ExcludeUnavailable",
            "FareClassControl" => "CompressByProductClass",
            "MinimumFarePrice" => 0,
            "MaximumFarePrice" => 10000,
            "SSRCollectionsMode" => "None",
            "InboundOutbound" => "None",
            "NightsStay" => 0,
            "IncludeAllotments" => false
            ))
        );

        if(!empty($extra['promo_code'])) $request['availabilityRequest']['PromotionCode'] = $extra['promo_code'];

        // if(isset($fields['trip_info']['aircraft_code']) && $fields['trip_info']['aircraft_code'] != '') 
        //     $request['availabilityRequest']['FlightNumber'] = $fields['trip_info']['aircraft_code'];

        $request["availabilityRequest"] = array_merge($request["availabilityRequest"], $paxsPriceTypes);

        return $request;
    }

    public function availabilityCombinedRequest(String $session, Array $journeys, Array $paxs) {
        $request = [];
        $availabilityRequests = '';
        $tripAvailabilityRequest = '';

        $paxsPriceTypes = $this->getPaxPriceTypesAsXML($paxs);
        $paxCount = $paxs['adults'] + $paxs['childs'] + $paxs['infs'];

        $sessionBody = $this->getSessionBody($session);
        $availabilityRequests = array_reduce($journeys, function($acc, $journey) use($paxsPriceTypes, $paxCount) {
            $paxQtd = $paxCount;
            $currencyCode = CURRENCY_CODE;

            return $acc . <<<XML
                <book1:AvailabilityRequest>
                    <book1:TADepartureDate>1999-01-01</book1:TADepartureDate>
                    <book1:DepartureStation>{$journey['from']}</book1:DepartureStation>
                    <book1:ArrivalStation>{$journey['to']}</book1:ArrivalStation>
                    <book1:BeginDate>{$journey['dep_date']}</book1:BeginDate>
                    <book1:EndDate>{$journey['dep_date']}</book1:EndDate>
                    <book1:FlightType>All</book1:FlightType>
                    <book1:PaxCount>{$paxQtd}</book1:PaxCount>
                    <book1:Dow>Daily</book1:Dow>
                    <book1:CurrencyCode>{$currencyCode}</book1:CurrencyCode>
                    <book1:AvailabilityType>Default</book1:AvailabilityType>
                    <book1:MaximumConnectingFlights>20</book1:MaximumConnectingFlights>
                    <book1:AvailabilityFilter>ExcludeUnavailable</book1:AvailabilityFilter>
                    <book1:FareClassControl>CompressByProductClass</book1:FareClassControl>
                    <book1:MinimumFarePrice>0</book1:MinimumFarePrice>
                    <book1:MaximumFarePrice>0</book1:MaximumFarePrice>
                    <book1:SSRCollectionsMode>None</book1:SSRCollectionsMode>
                    <book1:InboundOutbound>None</book1:InboundOutbound>
                    <book1:NightsStay>0</book1:NightsStay>
                    <book1:IncludeAllotments>false</book1:IncludeAllotments>
                    <book1:PaxPriceTypes>$paxsPriceTypes</book1:PaxPriceTypes>
                </book1:AvailabilityRequest>

            XML;
            // $arranged = '';
            // $arranged['TADepartureDate'] = ;
            // $arranged['DepartureStation'] = ;
            // $arranged['ArrivalStation'] = $journey['to'];
            // $arranged['BeginDate'] = $journey['dep_date'];
            // $arranged['EndDate'] = $journey['dep_date'];
            // $arranged['FlightType'] = "All";
            // $arranged['PaxCount'] = count($paxsPriceTypes);
            // $arranged['Dow'] = "Daily";
            // $arranged['CurrencyCode'] = CURRENCY_CODE;
            // $arranged['AvailabilityType'] = FARE_TYPES;
            // $arranged['MaximumConnectingFlights'] = 32767;
            
            // $arranged['AvailabilityFilter'] = 'ExcludeUnavailable';
            // $arranged['FareClassControl'] = 'CompressByProductClass';
            // $arranged['MinimumFarePrice'] = 0;
            // $arranged['MaximumFarePrice'] = 0;
            // $arranged['SSRCollectionsMode'] = 'None';
            // $arranged['InboundOutbound'] = 'None';
            // $arranged['NightsStay'] = 0;
            // $arranged['IncludeAllotments'] = false;

            // $arranged = array_merge($arranged, $paxsPriceTypes);
            // return $arranged;
        }, '');

        $tripAvailabilityRequest = <<<XML
        <book:tripAvailabilityRequest>
            <book1:AvailabilityRequests>
                $availabilityRequests
            </book1:AvailabilityRequests>
        </book:tripAvailabilityRequest>
        XML;
        
        $request = <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:book="http://schemas.navitaire.com/ClientServices/BookingManager/BookingManagerClient" xmlns:com="http://schemas.navitaire.com/Common" xmlns:book1="http://schemas.navitaire.com/Messages/Booking" xmlns:itin="http://schemas.navitaire.com/Messages/Itinerary">
            <soapenv:Header/>
            <soapenv:Body>
                <book:GetAvailabilityByTrip>
                    {$sessionBody}
                    {$tripAvailabilityRequest}
                </book:GetAvailabilityByTrip>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;
        
        return $request;
    }

    public function sellWithKeyRequest(Array $fields) {
        $request = [];

        Validator::validateJSONKeys($fields['pax_info'][0], array('resident_country'));
        $request["SellKeyList"] = [];
        $request["SellKeyList"] = array_merge($request["SellKeyList"], $this->getSellKeyList($fields['trip_info'][0]['key']));
        if(isset($fields['trip_info'][1])) {
            $request["SellKeyList"] = array_merge($request["SellKeyList"], $this->getSellKeyList($fields['trip_info'][1]['key']));
            // $request['SellKeyList'][] = $this->getSellKeyList($fields['return_keys']);
        }

        $request = array_merge($request, $this->getPaxPriceTypesWhenBooking($fields['pax_info']));
        $request['ActionStatusCode'] = ACTION_STATUS_CODE;
        $request['CurrencyCode'] = CURRENCY_CODE;
        $request['PaxCount'] = count($fields['pax_info']);
        if(isset($fields['trip_info'][0]['key']['cp'])) {
            $request['promo_code'] = $fields['trip_info'][0]['key']['cp'];
        }
        $request['PaxResidentCountry'] = $fields['pax_info'][0]['resident_country'];
        return array("sellWithKeyRequest" => $request);
    }

    // public function sellSeatRequest(Array $fields) {
    //     $request = [];

    //     try {
    //         $request = array (
    //             "BlockType" => 'None',
    //             "SameSeatRequiredOnThruLegs" => 'true',
    //             "AssignNoSeatIfAlreadyTaken" => 'false',
    //             "AllowSeatSwappingInPNR" => 'true',
    //             "SeatRequests" => array (

    //             ),
    //             "WaiveFee" => 'false',
    //             "SeatAssignmentMode" => 'PreSeatAssignment',
    //             "ReplaceSpecificSeatRequest" => 'false',
    //             "IgnoreCheckedInOnSeatFind" => 'false',
    //         );

    //         for ($index=0; $index < count($fields['seats']); $index++) { 
    //             $seat = $fields['seats'][$index];

    //             array_push($request["SeatRequests"], 
    //                 array(
    //                     "FlightDesignator" => array(
    //                         "CarrierCode" => $seat['trip_info']['comp_code'],
    //                         "FlightNumber" => $seat['trip_info']['flight_number'],
    //                         "OpSuffix" => $seat['trip_info']['op_suffix']
    //                     ),
    //                     "STD" => $seat['trip_info']['dep_date'],
    //                     "DepartureStation" => $seat['trip_info']['from'],
    //                     "ArrivalStation" => $seat['trip_info']['to'],
    //                     "PassengerNumbers" => array("short" => $seat['pax_num']),
    //                     "Seat" => array(
    //                         "Row" => $seat['seat']['row'],
    //                         "Column" => $seat['seat']['column'],
    //                         "CabinOfService" => $seat['seat']['cabin_of_service']
    //                     ),
    //                     "SeatPreference" => "None",
    //                     "SeatAssignmentLevel" => "Segment",
    //                     "CabinOfService" => '0'
    //                 )
    //             );
    //         }

    //         return array("sellSeatRequest" => $request);
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    public function commitHold(Array $fields) {
        $request = [];
        $paxs = $fields['pax_info'];

        try {

            $request = array("bookingRequest" => array(
                "CurrencyCode" => "BRL",
                "ReceivedBy" => $fields['received_by'],
                "PaxResidentCountry" => $paxs[0]['resident_country'],
                "CommitAction" => 'CommitRetrieve',
                "RestrictionOverride" => 'false',
                "HoldDateTime" => date('Y-m-d') . 'T00:00:00.00Z',
                "DistributeToContacts" => 'true',
                "DistributionOption" => 'Email',
                "ChangeHoldDateTime" => 'false',
                "WaiveNameChangeFee" => 'false',
                "BookingContacts" => array(
                ),
                "BookingPassengers" => array(
                ),
            ));
    
            $request['bookingRequest']['BookingContacts'] = array_merge($request['bookingRequest']['BookingContacts'], $this->getBookingContacts($fields['booking_contacts']));
            $request['bookingRequest']['BookingPassengers'] = array_merge($request['bookingRequest']['BookingPassengers'], $this->getBookingPassager($paxs));
            return $request;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // ################################ Separated Structures

    private function getPaxTravelDoc($pax, $docType, $docNumber) {
        if(!($docType === 'CPF')) throw new \Exception(getErrorMessage('docTypeNotImplemented'));

        $doc = array(
            "CreatedAgentID" => 0,
            "CreatedDate" => "0001-01-01T00:00:00",
            "ModifiedAgentID" => 0,
            "ModifiedDate" =>"0001-01-01T00:00:00",
            "PassengerID" => 0,
            "DocTypeCode" => $docType,
            "IssuedByCode" => $pax['resident_country'],
            "DocSuffix" => 32,
            "ExpirationDate" => "9999-12-31",
            "DocNumber" => $docNumber,
            "DOB" => "",
            "Gender" => $pax['gender'] === 'M' ? 'Male' : 'Female',
            "Nationality" => $pax['resident_country'],
            "Name" => array(
                "FirstName" => $pax['first_name'],
                "LastName" => $pax['last_name'],
            ),
        );

        if(isset($pax['middle_name']) && $pax['middle_name'] !== '') $doc["Name"]["MiddleName"] = $pax['middle_name'];
        if(!empty($pax['birth_date'])) {
            $doc['DOB'] = $pax['birth_date'];
        }

        return $doc;
    }

    public function getBookingPassager(Array $paxsInfo) {
        $NEEDED_PAX_INFO = array(
            "type",
            "first_name",
            "last_name",
            "gender",
            "resident_country",
            "birth_date"
        );
        $list = [];

        for ($index = 0; $index < count($paxsInfo); $index++) {
            $pax = $paxsInfo[$index];
            $isInfant = $pax['type'] == "INF" ? 'true' : 'false';

            Validator::validateJSONKeys($pax, $NEEDED_PAX_INFO);

            Validator::existValueOrError($pax['first_name'], 'Primeiro Nome');
            Validator::existValueOrError($pax['last_name'], 'Último Nome');
            Validator::existValueOrError($pax['birth_date'], 'Data de nascimento');
            Validator::existValueOrError($pax['gender'], 'Gênero');
            Validator::existValueOrError($pax['resident_country'], 'País Residente');
            $aux = array(
                "PassengerNumber" => $index,
                "Name" => array(
                    "FirstName" => $pax['first_name'],
                    "LastName" => $pax['last_name'],
                    // "Suffix" => $paxsInfo[$index]['suffix'],
                ),
                "PaxPriceType" => array(),
                "PassengerID" => 0,
                "FamilyNumber" => 0,
                "Gender" => $pax['gender'] === 'M' ? 'Male' : 'Female',
                "WeightCategory" => $pax['gender'] === 'M' ? 'Male' : 'Female',
                "Infant" => $isInfant,
                "ResidentCountry" => $pax['resident_country'],
                "TotalCost" => 0,
                "BalanceDue" => 0,
                "DOB" => $pax['birth_date'],
                "PassengerTravelDocs" => array()

            );
            if(isset($pax['middle_name']) && $pax['middle_name'] !== '') $aux["Name"]["MiddleName"] = $pax['middle_name'];
            if(isset($pax['suffix'])) {
                $aux["Name"]["Suffix"] = $pax['suffix'];
            }
            if(isset($pax['inf'])) {
                $aux['PassengerInfant'] = $this->getBookingPassagerInfant($pax['inf'], $index);
            }
            if(isset($pax['cpf'])) {
                array_push($aux['PassengerTravelDocs'], $this->getPaxTravelDoc($pax, 'CPF', $pax['cpf']));
            }
            $paxAux = [];
            $paxAux[] = $pax;
            $aux['PaxPriceType'] = array_merge($aux['PaxPriceType'], $this->getPaxPriceTypesWhenBooking($paxAux)['PaxPriceTypes'][0]);

            $list[] = $aux;
        }

        return $list;
    }

    public function getBookingPassagerInfant(Array $infant, Int $paxId) {
        $NEEDED_INF_INFO = array(
            "first_name",
            "middle_name",
            "last_name",
            "gender",
            "birth_date",
            "resident_country"
        );

        Validator::validateJSONKeys($infant, $NEEDED_INF_INFO);

        Validator::existValueOrError($infant['first_name'], getErrorCauseByJsonKey('first_name'));
        Validator::existValueOrError($infant['last_name'], getErrorCauseByJsonKey('last_name'));
        Validator::existValueOrError($infant['gender'], getErrorCauseByJsonKey('gender'));
        Validator::existValueOrError($infant['birth_date'], getErrorCauseByJsonKey('birth_date'));
        Validator::existValueOrError($infant['resident_country'], getErrorCauseByJsonKey('resident_country'));
        $infArray = array(
            "PassengerID" => $paxId,
            "DOB" => $infant['birth_date'],
            "Gender" => $infant['gender'] === 'M' ? 'Male' : 'Female',
            "ResidentCountry" => $infant['resident_country'],
            "Name" => array(
                "FirstName" => $infant['first_name'],
                "LastName" => $infant['last_name'],
                // "Suffix" => $paxsInfo[$index]['suffix'],
            ),
        );

        return $infArray;
    }

    public function getBookingContacts(Array $contacts) {
        $NEEDED_CONTACT_INFO = array(
            "first_name",
            "last_name"
        );
        $list = [];

        foreach ($contacts as $contact) {
            $aux = [];
            $address1 = '';
            $address2 = '';
            $address3  = '';

            Validator::validateJSONKeys($contact, $NEEDED_CONTACT_INFO);

            // Validator::existValueOrError($user['email'], getErrorCauseByJsonKey('email'));
            // Validator::existValueOrError($user['name'], getErrorCauseByJsonKey('first_name'));
            // Validator::existValueOrError($user['last_name'], getErrorCauseByJsonKey('last_name'));
            // Validator::existValueOrError($user['work_phone'], getErrorCauseByJsonKey('work_phone'));
            Validator::existValueOrError($contact['first_name'], getErrorCauseByJsonKey('address_line1'));
            Validator::existValueOrError($contact['last_name'], getErrorCauseByJsonKey('contact_option'));
        // Validator::existValueOrError($contact['notification_preference'], getErrorCauseByJsonKey('notification_preference'));
            if(!empty($contact['public_place'])) {
                $address1 = $contact['public_place'] . ' ' . $contact['address_number'];
                $address2 = $contact['address_complement'];
                $address3 = $contact['cnpj'];
            }

            $aux = array(
                "TypeCode" => 80,
                "EmailAddress" => $contact['email'],
                "WorkPhone" => '',
                "Name" => array(
                    "FirstName" => $contact['first_name'],
                    "LastName" => $contact['last_name'],
                ),
                "AddressLine1" => 0,
                "AddressLine2" => 0,
                "AddressLine3" => 0,
                // "CompanyName" => "WORLD TURISMO",
                "CultureCode" => "pt-BR",
                "DistributionOption" => "Hold",
                "NotificationPreference" => "None"
            );
            if($address1 !== '') $aux["AddressLine1"] = $address1;
            if($address2 !== '') $aux["AddressLine2"] = $address2;
            if($address3 !== '') $aux["AddressLine3"] = $address3;
            if(!empty($contact['phone']))          $aux['HomePhone'] = $contact['phone'];
            if(!empty($contact['work_phone']))     $aux['WorkPhone'] = $contact['work_phone'];
            if(!empty($contact['middle_name']))    $aux['Name']['Middle_name'] = $contact['middle_name'];
            if(!empty($contact['sufix']))          $aux['Name']['Suffix'] = $contact['sufix'];
            if(!empty($contact['city']))           $aux['City'] = $contact['city'];
            if(!empty($contact['province_code']))  $aux['ProvinceState'] = $contact['province_code'];
            if(!empty($contact['postal_code']))    $aux['PostalCode'] = $contact['postal_code'];
            if(!empty($contact['country_code']))   $aux['CountryCode'] = $contact['country_code'];

            $list[] = $aux;
        }

        return $list;
    }

    private function getPaxPriceTypesWhenBooking(Array $paxsInfo) {
        $paxsTypes = [];
        $paxsTypes["PaxPriceTypes"] = [];

        foreach ($paxsInfo as $pax) {
            Validator::validateJSONKeys($pax, array('type'));

            Validator::existValueOrError($pax['type'], '"Tipo pax"');
            
            array_push($paxsTypes["PaxPriceTypes"], array("PaxType" => $pax["type"]));
        }

        return $paxsTypes;
    }

    public function getPaxPriceTypes(Array $paxsInfo) {
        $arrangePaxs = function (Int $paxs, String $type) {
            $arranged = [];
            for ($index = 0; $index < $paxs; $index++) { 
                array_push($arranged, array("PaxType" => $type));
            }

            return $arranged;
        };

        $paxsTypes["PaxPriceTypes"] = [];
        
        if($paxsInfo['adults'] <= 0 && $paxsInfo['adults'] <= 0) throw new \Exception(getErrorMessage('missingPaxs'));

        $paxsTypes["PaxPriceTypes"] =       array_merge($paxsTypes["PaxPriceTypes"], $arrangePaxs($paxsInfo['adults'], 'ADT'));
        if($paxsInfo['childs'] > 0)
            $paxsTypes["PaxPriceTypes"] =   array_merge($paxsTypes["PaxPriceTypes"], $arrangePaxs($paxsInfo['childs'], 'CHD'));
        if($paxsInfo['infs'] > 0)
            $paxsTypes["PaxPriceTypes"] =   array_merge($paxsTypes["PaxPriceTypes"], $arrangePaxs($paxsInfo['infs'], 'INF'));
        // foreach ($paxsInfo['adults'] as $pax) {
        //     $paxPrice = array("PaxType" => "ADT");
        //     // if($pax["discount_code"] != "") {
        //     //     $paxPrice["PaxDiscountCode"] = $pax["discount_code"];
        //     // }
        //     array_push($paxsTypes["PaxPriceTypes"], $paxPrice);
        // }

        return $paxsTypes;
    }

    public function getPaxPriceTypesAsXML(Array $paxs) {
        $arrangePaxs = function (Int $paxs, String $type) {
            $arranged = '';

            for ($index = 0; $index < $paxs; $index++) { 
                $arranged .= <<<XML

                    <itin:PaxPriceType>
                        <itin:PaxType>$type</itin:PaxType>
                   </itin:PaxPriceType>

                XML;
            }

            return $arranged;
        };
        $paxPriceTypes = '';

        
        if($paxs['adults'] <= 0 && $paxs['adults'] <= 0) throw new \Exception(getErrorMessage('missingPaxs'));

        $paxPriceTypes = $arrangePaxs($paxs['adults'], 'ADT');
        if($paxs['childs'] > 0)
            $paxPriceTypes .= $arrangePaxs($paxs['childs'], 'CHD');
        // if($paxsInfo['infs'] > 0)
            // $paxPriceTypes .= $arrangePaxs($paxs['infs'], 'INF');

        return $paxPriceTypes;
    }

    public function getSellKeyList(Array $keys) {
        $sellKeys = [];
        // if(!isset($list["SellKeyList"])) $list["SellKeyList"] = [];

        $journey = array(
                "JourneySellKey" => $keys['journey'],
                "FareSellKey" => $keys['tax']
        );
        array_push($sellKeys, $journey);

        return $sellKeys;
    }
    
    // public function getPaxPriceTypes(Array $pax) {
    //     $list["PaxPriceTypes"] = [];

    //     for ($i = 0; $i < count($pax); $i++) { 
    //         array_push($list["PaxPriceTypes"], array("PaxPriceType" => 
    //             array(
    //                 "PaxType" => $pax[$i]["type"],
    //             )
    //         ));
    //     }
    //     return $list;
    // }
}