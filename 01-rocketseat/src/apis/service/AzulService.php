<?php
namespace Api\Service;
use \Util\Validator;
use \Util\Formatter;

class AzulService {

    function __construct($wsdlUrl) {
        $this->actualSSRNumber = 0;
        $this->wsdlUrl = $wsdlUrl;
        $this->soapCli = new \SoapClient($this->wsdlUrl, $options = array(
            'trace' => 1,
            'exceptions' => 0,
            'encoding' => 'UTF-8'
            )
        );
    }

    public function commitUpdate($session, $loc, $pnr) {

        try {
            $contact = isset($pnr['BookingContacts']['BookingContact']) ? $pnr['BookingContacts']['BookingContact'] : $pnr['BookingContacts'][0];
            $request['session'] = $session;
            $request['bookingRequest'] = array(
                "RecordLocator"=> $loc,
                "CurrencyCode"=> "BRL",
                "PaxResidentCountry"=> $pnr["PaxResidentCountry"],
                "ReceivedBy"=> $pnr['ReceivedBy'],
                "CommitAction"=> "CommitRetrieve",
                "RestrictionOverride"=> false,
                "DistributeToContacts"=> true,
                "DistributionOption"=> $contact['DistributionOption'],
                "HoldDateTime"=> $pnr['HoldDateTime'],
                "ChangeHoldDateTime"=> false,
                "WaiveNameChangeFee"=> false
            );

            if(TEST_MODE) {
                print_r($request);
                return $request;
            }
            return $this->soapCli->Commit($request);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function commitAsHold($session, $requestInfo) {
        $request = [];
        $request['session'] = [];
        $request['bookingRequest'] = [];

        Validator::validateJSONKeys($requestInfo, ["trip_info", "pax_info", "received_by"]);
        Validator::validateJSONKeys($requestInfo, ["booking_contacts"]);
        Validator::validateJSONKeys($requestInfo['pax_info'][0], array('resident_country'));

        Validator::existValueOrError($requestInfo['pax_info'][0], getErrorCauseByJsonKey('pax_resident_country'));
        Validator::existValueOrError($requestInfo['received_by'], getErrorCauseByJsonKey('received_by'));
        // ### End Pre-Validation


        try {
            $reqMaker = new \Api\Helpers\AzulRequestMaker();
            $request['session'] = array_merge($request['session'], $session);
            $request['bookingRequest'] = $reqMaker->commitHold($requestInfo)['bookingRequest'];
            if(TEST_MODE) {
                // if(TEST_MODE_LEVEL == 3) {
                //     $response = $this->soapCli->Commit($request);
                // }
                print_r($request);
                return $request;
            }
            $response = $this->soapCli->Commit($request);
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function logon(Array $credential) {
        try {
            // print_r($this->soapCli->__getFunctions());
            // print_r($this->soapCli->__getTypes());
            // die();
            // $soapCli->__soapCall('Logon', array($payload->asXML()));
            // print_r($soapCli->Logon());
            $request = array(
                "request" => array(
                    "DomainCode" => "EXT",
                    "AgentName" => $credential['loginName'],
                    "Password" => $credential['password'],
                    "SystemType" => 'Default',
                    "RoleCode" => "",
                    "ChannelType" => 'Default',
                )
            );
            $response = $this->soapCli->Logon($request);

            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function priceItinerary($session, $request) {
    //     $NEEDEDFIELDS = array(
    //         'trip_info',
    //         'payment_cc_info',
    //         'received_by',,
    //         'pax_resident_country'
    //     );
    //     $NEED_TRIP_INFOS = array(
    //         'segments',
    //         'pax_info',
    //         'booking_contacts'
    //     );

    //     Validator::validateJSONKeys($request, $NEEDEDFIELDS);
    //     Validator::validateJSONKeys($request['trip_info'], $NEED_TRIP_INFOS);
    //     Validator::validateJSONKeys($request['trip_info'], $NEED_TRIP_INFOS);
    // }

    public function search($session, $requestInfo) {
        try {
            $reqMaker = new \Api\Helpers\AzulRequestMaker();
            $request = [];
            $requests = [];
            $response = [];
            $extraAbailabilityReqData = [];

            if(!empty($requestInfo['promo_code'])) $extraAbailabilityReqData['promo_code'] = $requestInfo['promo_code'];
            // To FIX
            $request['session'] = array_merge($request, $session);
            $request = array_merge($request, $reqMaker->availabilityRequest($requestInfo['trip_info'][0], $requestInfo['pax_info'], $extraAbailabilityReqData));
            $requests[] = $request;

            // Get Return voos
            if(!empty($requestInfo['trip_info'][0]['back_date'])) {
                $returnRequestInfo = $requestInfo['trip_info'][0];

                $returnRequestInfo['from'] = $requestInfo['trip_info'][0]['to'];
                $returnRequestInfo['to'] = $requestInfo['trip_info'][0]['from'];
                $returnRequestInfo['dep_date'] = $returnRequestInfo['back_date'];
                $request = array_merge($request, $reqMaker->availabilityRequest($returnRequestInfo, $requestInfo['pax_info'], $extraAbailabilityReqData));
                $requests[] = $request;
            }

            $resObject = json_decode(json_encode($this->soapCli->GetAvailability($requests[0])), true);

            $response[] = $resObject;

            if(!empty($requestInfo['trip_info'][0]['back_date'])) {
                $resObject = json_decode(json_encode($this->soapCli->GetAvailability($requests[1])), true);
                $response[] = $resObject;
            }
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function searchCombined($session, $payload) {
        try {
            $reqMaker = new \Api\Helpers\AzulRequestMaker();
            $request = '';
            $response = '';

            $request = $reqMaker->availabilityCombinedRequest($session['SecureToken'], $payload['trip_info'], $payload['pax_info']);

            $soapRes = $this->soapCli->__doRequest($request, 'https://webservices.voeazul.com.br/AzulWS/AzulServices.svc?wsdl', 'http://schemas.navitaire.com/ClientServices/BookingManager/BookingManagerClient/IBookingManagerClient/GetAvailabilityByTrip', '1.1');

            $response = Formatter::soapToArray($soapRes)['GetAvailabilityByTripResponse']['GetAvailabilityByTripResult']['Schedules']['ArrayOfJourneyDateMarket'];

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    // public function getSeatAvailability($session, $booking) {
    //     try {
    //         $response = [];

    //         // if($booking == null || count($booking) <= 0) throw new \Exception(getErrorMessage(''));
    //         foreach ($booking["JourneyServices"] as $bookingService) {
    //             foreach ($bookingService["Segments"] as $segment) {
    //                 $request = array(
    //                     "session" => $session,
    //                     "seatAvailabilityRequest" => array(
    //                         "FlightDesignator" => $segment["FlightDesignator"],
    //                         "STD" => $segment["STD"],
    //                         "DepartureStation" => $segment["DepartureStation"],
    //                         "ArrivalStation" => $segment["ArrivalStation"],
    //                         "IncludeSSRSeatMapCode" => false,
    //                         "IncludeSeatFees" => true,
    //                         "SeatAssignmentMode" => "PreSeatAssignment"
    //                     )
    //                 );
    //                 $seatAvialability = json_decode(json_encode($this->soapCli->GetSeatAvailability($request)), true);
    //                 // if(count($seatAvialability) <= 0) throw new \Exception(getErrorMessage('notSeatFound'));

    //                 $seatAvialability = $seatAvialability['GetSeatAvailabilityResult'];

    //                 // $aux = [];
    //                 // // $aux["flight_code"] = $segment["FlightDesignator"];
    //                 // $aux["from"] = $segment["DepartureStation"];
    //                 // $aux["to"] = $segment["ArrivalStation"];

    //                 // $aux = array_merge($aux, $seatAvialability);

    //                 array_push($response, $seatAvialability);
    //             }
    //         }

    //         return $response;
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    private function arrangeSeatWithPrice($seats) {
        return $seats;
    }

    public function getSeatAvailability($session, $requestInfo) {
        $NEEDED_TIP_SEG_FIELDS = array(
            "from",
            "to",
            "dep_date",
            "comp_code",
            "flight_number"
        );

        try {
            $requests = [];
            $response = [];
            $priced = false;

            Validator::validateJSONKeys($requestInfo, array('segments'));
            Validator::existValueOrError($requestInfo['segments'], getErrorCauseByJsonKey('trip_segments'));
            // if($booking == null || count($booking) <= 0) throw new \Exception(getErrorMessage(''));
            if(!empty($requestInfo['loc'])) $priced = true;
            foreach ($requestInfo["segments"] as $segment) {

                Validator::validateJSONKeys($segment, $NEEDED_TIP_SEG_FIELDS);
                Validator::existValueOrError($segment["from"], getErrorCauseByJsonKey('from'));
                Validator::existValueOrError($segment["to"], getErrorCauseByJsonKey('to'));
                Validator::existValueOrError($segment["dep_date"], getErrorCauseByJsonKey('dep_date'));
                Validator::existValueOrError($segment["comp_code"], getErrorCauseByJsonKey('comp_code'));
                Validator::existValueOrError($segment["flight_number"], getErrorCauseByJsonKey('flight_number'));
                $requestBody = array(
                    "session" => $session,
                    "seatAvailabilityRequest" => array(
                        "FlightDesignator" => array(
                            "CarrierCode" => $segment["comp_code"],
                            "FlightNumber" => $segment["flight_number"],
                            "OpSuffix" => 32
                        ),
                        "STD" => $segment["dep_date"],
                        "DepartureStation" => $segment["from"],
                        "ArrivalStation" => $segment["to"],
                        "IncludeSSRSeatMapCode" => false,
                        "IncludeSeatFees" => true,
                        "SeatAssignmentMode" => "PreSeatAssignment"
                    )
                );

                $requests[] = $requestBody;
            }

            if(TEST_MODE) {
                print_r($requests);
                return $requests;
            }

            foreach ($requests as $request) {
                $seatAvialability = json_decode(json_encode($this->soapCli->GetSeatAvailability($request)), true);
                // if(count($seatAvialability) <= 0) throw new \Exception(getErrorMessage('notSeatFound'));
                $seatAvialability = $seatAvialability['GetSeatAvailabilityResult'];

                array_push($response, $seatAvialability);
            }
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /*
        Needed body fields.
        "trip_info": {
            "segments": {
                "going": [
                    {
                        "from": "FLN",
                        "to": "CWB",
                        "dep_date": "2021-05-04",
                        "comp_code": "AD",
                        "flight_number": "6135",
                        "op_suffix": "32",
                        "seats": [{
                            "row": 6,
                            "column": 68,
                            "cabin_of_service": 89,
                            "pax_index": 0
                        }]
                    }
                ],
                "return": [
                ]
            }
        }
    */
    public function seatAssign($session, $requestInfo) {
        $reqMaker = new \Api\Helpers\AzulRequestMaker();

        // ### End Pre-Validation.
        $request = [];

        $request["session"] = $session;
        $request["sellSeatRequest"] = array(
            'BlockType' => 'None',
            'SameSeatRequiredOnThruLegs' => 'true',
            'AssignNoSeatIfAlreadyTaken' => 'false',
            'AllowSeatSwappingInPNR' => 'true',
            'SeatRequests' => array(

            ),
            // WaiveFee has one trouble when using 'false' value, request not recognize the value,
            // then has the need to use zero as value.
            'WaiveFee' => '0',
            'SeatAssignmentMode' => 'PreSeatAssignment',
            'ReplaceSpecificSeatRequest' => 'false',
            'IgnoreCheckedInOnSeatFind' => 'false',
        );

        $segments = $requestInfo['segments'];
        for ($index = 0; $index < count($segments); $index++) {
            $segment = $segments[$index];

            // Validator::existValueOrError($segment['from'], getErrorCauseByJsonKey('from'));
            // Validator::existValueOrError($segment['to'], getErrorCauseByJsonKey('to'));
            // Validator::existValueOrError($segment['dep_date'], getErrorCauseByJsonKey('dep_date'));
            // Validator::existValueOrError($segment['comp_code'], getErrorCauseByJsonKey('comp_code'));
            // Validator::existValueOrError($segment['flight_number'], getErrorCauseByJsonKey('flight_number'));

            if(count($segment['seats']) <= 0) throw new \Exception(getErrorMessage('notSeatFound'));

            for ($indexSeat = 0; $indexSeat < count($segment['seats']); $indexSeat++) {
                $seat = $segment['seats'][$indexSeat];

                Validator::validateJSONKeys($seat, [ "row", "column", "pax_index"]);
                Validator::existValuesOrError($seat, [
                "row" => 'Linha do assento.',
                "column" => 'Coluna do assento.',
                "pax_index" => 'Passageiro do assento.']);

                array_push($request['sellSeatRequest']['SeatRequests'],
                    array(
                        'FlightDesignator' => array(
                            'CarrierCode' => $segment['comp_code'],
                            'FlightNumber' => $segment['flight_number'],
                            'OpSuffix' => 32
                        ),
                        'STD' => $segment['dep_date'],
                        'DepartureStation' => $segment['from'],
                        'ArrivalStation' => $segment['to'],
                        'PassengerNumbers' => array (
                            $seat['pax_index']
                        ),
                        'Seat' => array(
                            "Row" => $seat['row'],
                            "Column" => $seat['column'],
                            'CabinOfService' => 89
                        ),
                        'SeatPreference' => 'None',
                        'SeatAssignmentLevel' => 'Segment',
                        'CabinOfService' => 89
                    )
                );
            }
        }

        // print_r($request);
        // try {
        //     print_r($this->soapCli->AssignSeats($request));
        //     die();
        // } catch (\Exception $e) {
        //     echo $e->getMessage();
        //     die();
        // }
        return $this->soapCli->AssignSeats($request);
    }

    public function book($session, $requestInfo) {
        $reqMaker = new \Api\Helpers\AzulRequestMaker();
        $request = [];

        // ### Pre-Validation
        Validator::validateJSONKeys($requestInfo, ["trip_info"]);
        Validator::validateJSONKeys($requestInfo, ['pax_info']);
        if($requestInfo['pax_info'] == null || count($requestInfo['pax_info']) <= 0) throw new \Exception(getErrorMessage('wsRequestMissingData') + " Pax");

        // ### End Pre-Validation
        try {
            $request['session'] = array_merge($request, $session);
            $request = array_merge($request, $reqMaker->sellWithKeyRequest($requestInfo));

            if(TEST_MODE) {
                print_r($request);
                return $request;
            }
            $response = $this->soapCli->SellByKey($request);
            return $response;
            // $routes = $regexHandler->getTripsFromRouteKey($requestInfo['journey_keys'][0]);
            // $dates = $regexHandler->getDatesFromRouteKey($requestInfo['journey_keys'][0]);
            // $airCraftCodes = $regexHandler->getAircraftCodes($requestInfo['journey_keys'][0]);

            // $from = $routes[0][0];
            // $to = $routes[0][1];

            // $beginDate = date('Y-m-d', strtotime($dates[0][0]));
            // $endDate = date('Y-m-d', strtotime($dates[0][1]));

            // $requestInfo["trip_info"]["from"] = $from;
            // $requestInfo["trip_info"]["to"] = $to;
            // $requestInfo["trip_info"]["dep_date"] = $beginDate;
            // $requestInfo["trip_info"]["end_date"] = $endDate;
            // $requestInfo["trip_info"]["aircraft_code"] = $airCraftCodes[0];
            // // $response = $this->search($session, $requestInfo);


            // return $this->commitAsHold($session, $requestInfo);
        } catch (\Exception $e) {
            throw $e;
        }
        // return json_decode(json_encode($response), true);
    }

    private function getSellSSR($segment, $indexPax, $typeSSR, $numberSSR) {
        $NEEDED_SEGMENT_INFO = array(
            "from",
            "to",
            "dep_date",
            "comp_code",
            "flight_number",
        );

        Validator::validateJSONKeys($segment, $NEEDED_SEGMENT_INFO);
        Validator::existValueOrError($segment['from'], getErrorCauseByJsonKey('from'));
        Validator::existValueOrError($segment['to'], getErrorCauseByJsonKey('to'));
        Validator::existValueOrError($segment['dep_date'], getErrorCauseByJsonKey('dep_date'));
        Validator::existValueOrError($segment['comp_code'], getErrorCauseByJsonKey('comp_code'));
        Validator::existValueOrError($segment['flight_number'], getErrorCauseByJsonKey('flight_number'));

        $SSRRequest = array(
            "SSRCode" => $typeSSR,
            "SSRNumber" => $numberSSR,
            "STD" => $segment['dep_date'],
            "FlightDesignator" => array(
                "CarrierCode" => $segment['comp_code'],
                "FlightNumber" => $segment['flight_number'],
                "OpSuffix" => 32
            ),
            "DepartureStation" => $segment['from'],
            "ArrivalStation" => $segment['to'],
            "PassengerNumber" => $indexPax
        );

        return $SSRRequest;
    }

    /*
        $ssrRequestType values:
            sell;
            cancel.
    */
    public function operateSSRInf($session, $requestInfo, $ssrRequestType){
        $infs = [];
        $infs = array_filter($requestInfo['pax_info'], function ($pax) {
            return $pax['type'] === 'INF';
        });
        $journeys = $requestInfo['trip_info'];
        if(count($infs) === 0) return false;
        // ### Pre-Validation.
        // Validator::validateJSONKeys($requestInfo, ['trip_info', 'pax_info']);
        // Validator::validateJSONKeys($requestInfo['trip_info'], ['segments']);
        // if($requestInfo['pax_info'] == null || count($requestInfo['pax_info']) <= 0)
            throw new \Exception(getErrorMessage('wsRequestMissingData') . " " . getErrorCauseByJsonKey('pax_info'));

        // TODO
        // Add return key validing return segments.ssrRequestType

        // ### End Pre-Validation.

        $request = [];
        $responses = [];

        $ssrRequestType .= "SSRRequest";
        $request["session"] = $session;
        $request[$ssrRequestType] = array(
            "CollectedCurrencyCode" => "BRL",
            "SSRRequests" => array(
            )
        );

        // segmentType = going/return.
        for ($indexPax = 0; $indexPax < count($infs); $indexPax++) {
            foreach ($journeys as $journey) {
                $segments = $journey['segments'];
                for ($indexSegment = 0; $indexSegment < count($segments); $indexSegment++) {
                    $segment = $segments[$indexSegment];

                    $SSRRequest = $this->getSellSSR($segment, $indexPax, 'INF', $this->actualSSRNumber);

                    array_push($request[$ssrRequestType]["SSRRequests"], $SSRRequest);
                    $this->actualSSRNumber += 1;
                }
            }
        }

        if(count($request[$ssrRequestType]['SSRRequests']) <= 0) return [];
        if(TEST_MODE) {
            print_r($request);
            return $request;
        }
        switch ($ssrRequestType) {
            case 'sellSSRRequest':
                $response = json_decode(json_encode($this->soapCli->SellSSR($request)), true);
                break;
            case 'cancelSSRRequest':
                $response = json_decode(json_encode($this->soapCli->CancelSSR($request)), true);
                break;
            default:
                throw new \Exception('SSR Type not found');
        }
        return $response;
    }

    public function operateSSRBag($session, $segments, $paxs, $ssrRequestType, $isInternational) {

        if(count($paxs) === 0) return false;
        // ### Pre-Validation.
        // if($requestInfo['pax_info'] == null || count($requestInfo['pax_info']) <= 0)
            // throw new \Exception(getErrorMessage('wsRequestMissingData') . " " . getErrorCauseByJsonKey('pax_info'));

        // TODO
        // Add return key validing return segments.

        // ### End Pre-Validation.

        $request = [];
        $response = [];
        $ssrRequestType .= "SSRRequest";
        $request["session"] = $session;
        $request[$ssrRequestType] = array(
            "SSRRequests" => array(
            )
        );

        for ($indexPax = 0; $indexPax < count($paxs); $indexPax++) {
            $pax = $paxs[$indexPax];

            foreach ($segments as $segment) {
                if($pax['baggages'] > 5) throw new \Exception(getErrorMessage('baggageExcess'));
                $bagType = '';

                if(!$isInternational) {
                    $bagType = $pax['baggages'] . "BAG";
                }else {
                    $bagType = "BAS" . $pax['baggages'];
                }
                $SSRRequest = $this->getSellSSR($segment, $indexPax, $bagType, $this->actualSSRNumber);
                array_push($request[$ssrRequestType]["SSRRequests"], $SSRRequest);
                $this->actualSSRNumber += 1;
            }
        }

        if(count($request[$ssrRequestType]['SSRRequests']) <= 0) return [];
        if(TEST_MODE) {
            print_r($request);
            return $request;
        }

        switch ($ssrRequestType) {
            case 'sellSSRRequest':
                $response = json_decode(json_encode($this->soapCli->SellSSR($request)), true);
                break;
            case 'cancelSSRRequest':
                $response = json_decode(json_encode($this->soapCli->CancelSSR($request)), true);
                break;
            default:
                throw new \Exception('SSR Type not found');
        }

        return $response;
    }

    private function getPaymentTypeDescription($methodCode) {
        switch ($methodCode) {
            case 'AG':
                return 'AgencyAccount';
            case 'CC':
                return 'ExternalAccount';
            default:
                throw new \Exception(getErrorMessage('paymentTypeNotFound', $methodCode));
        }
    }

    public function addPayment(Array $session, Array $payment) {
        $request = [];

        $paymentCode = $payment['type'] === 'AG' ? $payment['type'] : $payment['code'];
        $request['session'] = $session;
        $request['payment'] = array(
            'CreatedAgentID' => 0,
            'CreatedDate' => '0001-01-01T00:00:00',
            'ModifiedAgentID' => '0',
            'ModifiedDate' => '0001-01-01T00:00:00',
            'PaymentID' => '0',
            'ReferenceType' => 'Default',
            'ReferenceID' => '0',
            'PaymentMethodType' => $this->getPaymentTypeDescription($payment['type']),
            'PaymentMethodCode' => $paymentCode,
            'CurrencyCode' => 'BRL',
            'NominalPaymentAmount' => '0',
            'CollectedAmount' => '0',
            'QuotedCurrencyCode' => 'BRL',
            'QuotedAmount' => $payment['value'],
            'Status' => 'New',
            'AccountNumber' => $payment['acc_number'],
            'Expiration' => '1800-01-01T00:00:00',
            'AuthorizationStatus' => 'Unknown',
            'ParentPaymentId' => '0',
            'Transferred' => 'false',
            'DCCTransactionID' => '0',
            'DCCRateID' => '00000000-0000-0000-0000-000000000000',
            'DCCStatus' => 'DCCNotOffered',
            'ReconciliationID' => '0',
            'FundedDate' => '1800-01-01',
            'Installments' => '1',
            // 'PaymentText' => '1',
            'ChannelType' => 'API',
            'PaymentNumber' => 0,
            'AccountID' => 0,
            'AccountTransactionID' => 0,
            'InterestAmount' => 0,
            'TaxAmount' => 0
        );
        $request['waiveFee'] = 0;

        if($payment['type'] === 'CC') {
            $request['payment']['PaymentFields'] = [];
            $arrangedPaymentFields = [];
            $arrangedPaymentField = [];

            $arrangedPaymentField['FieldName'] = 'CC::VerificationCode';
            $arrangedPaymentField['FieldValue'] = $payment['acc_sec_code'];
            $arrangedPaymentFields[] = $arrangedPaymentField;

            $arrangedPaymentField['FieldName'] = 'CC::AccountHolderName';
            $arrangedPaymentField['FieldValue'] = $payment['acc_holder_name'];
            $arrangedPaymentFields[] = $arrangedPaymentField;

            $arrangedPaymentField['FieldName'] = 'EXPDAT';
            $arrangedPaymentField['FieldValue'] = $payment['exp_date'] . 'T00:00:00';
            $arrangedPaymentFields[] = $arrangedPaymentField;

            $arrangedPaymentField['FieldName'] = 'AMT';
            $arrangedPaymentField['FieldValue'] = $payment['value'];
            $arrangedPaymentFields[] = $arrangedPaymentField;

            $arrangedPaymentField['FieldName'] = 'ACCTNO';
            $arrangedPaymentField['FieldValue'] = $payment['acc_number'];
            $arrangedPaymentFields[] = $arrangedPaymentField;

            $arrangedPaymentField['FieldName'] = 'NPARC';
            $arrangedPaymentField['FieldValue'] = $payment['num_instal'];
            $arrangedPaymentFields[] = $arrangedPaymentField;
            $request['payment']['PaymentFields'] = $arrangedPaymentFields;
        }

        return $request;
    }

    public function confirmBooking(Array $session, Array $requestInfo) {
        $request = [];

        $booking = $this->getBooking($session, $requestInfo)['GetBookingResult'];
        if($booking['BookingStatus'] !== 'Hold' && $booking['BookingStatus'] !== 'Confirmed') throw new \Exception(getErrorMessage('invalidBookStatusToPayment', $booking['BookingStatus']));
        foreach ($requestInfo['payments'] as $payment) {
            $request = $this->addPayment($session, $payment);

            if(TEST_MODE) {
                print_r($request);
                return $request;
            }

            $this->soapCli->AddPaymentToBooking($request);
        }
        $this->commitUpdate($session, $requestInfo['loc'], $booking);
        return true;
    }

    // public function addFee($session, $requestInfo) {
    //     $reqMaker = new \Api\Helpers\AzulRequestMaker();
    //     $request = [];

    //     Validator::validateJSONKeys($requestInfo, array('clientId'));

    //     $assocResult = $dbClientAssoc->fetchMarkupByClientAndCode($requestInfo["clientId"], 'AD');
    //     $clientResult = $dbClient->fetch(($requestInfo["clientId"]);
    //     print_r($assocResult);
    //     die();
    //     // $request['session'] = array_merge($request, $session);
    //     // $request = array_merge($request, $reqMaker->interestFeeRequest($requestInfo));
    //     // $response = $this->soapCli->AddInterestFee($request);

    //     return json_decode(json_encode($response), true);
    // }

    public function divideBooking($session, $requestInfo) {
        $request = [];

        Validator::validateJSONKeys($requestInfo, array('loc', 'paxs_num', 'received_by'));

        Validator::existValueOrError($requestInfo['loc'], getErrorCauseByJsonKey('loc'));
        Validator::existValueOrError($requestInfo['paxs_num'], getErrorCauseByJsonKey('paxs_num'));
        Validator::existValueOrError($requestInfo['received_by'], getErrorCauseByJsonKey('received_by'));

        // $bookingToDivide = $this->search($session, $requestInfo);
        // $paxCount = count($bookingToDivide['GetBookingResult']['BookingPassengers']);

        // if()
        $request['session'] = array_merge($request, $session);
        $request['divideRequest'] = array(
            "SourceRecordLocator" => $requestInfo['loc'],
            "PassengerNumbers" => array(
            ),
            "AutoDividePayments" => "true",
            "QueueNegativeBalancePNRs" => "true",
            "AddComments" => "true",
            "ReceivedBy" => $requestInfo['received_by'],
            "OverrideRestrictions" => "false"
        );

        foreach ($requestInfo['paxs_num'] as $paxNum) {
            $request['divideRequest']["PassengerNumbers"][] = $paxNum;
        }

        if(TEST_MODE) {
            print_r($request);
            return $request;
        }
        $response = $this->soapCli->Divide($request);

        return json_decode(json_encode($response), true);
    }

    public function divideAndCancelBooking($session, $requestInfo) {
        $request = [];

        Validator::validateJSONKeys($requestInfo, array('loc', 'paxs_num', 'received_by'));

        Validator::existValueOrError($requestInfo['loc'], getErrorCauseByJsonKey('loc'));
        Validator::existValueOrError($requestInfo['paxs_num'], getErrorCauseByJsonKey('paxs_num'));
        Validator::existValueOrError($requestInfo['received_by'], getErrorCauseByJsonKey('received_by'));

        // $bookingToDivide = $this->search($session, $requestInfo);
        // $paxCount = count($bookingToDivide['GetBookingResult']['BookingPassengers']);

        // if()
        $request['session'] = array_merge($request, $session);
        $request['divideRequest'] = array(
            "SourceRecordLocator" => $requestInfo['loc'],
            "PassengerNumbers" => array(
            ),
            "AutoDividePayments" => "true",
            "QueueNegativeBalancePNRs" => "true",
            "AddComments" => "true",
            "ReceivedBy" => $requestInfo['received_by'],
            "OverrideRestrictions" => "false"
        );

        foreach ($requestInfo['paxs_num'] as $paxNum) {
            $request['divideRequest']["PassengerNumbers"][] = $paxNum;
        }

        if(TEST_MODE) {
            print_r($request);
            return $request;
        }
        $response = $this->soapCli->DivideAndCancel($request);

        return json_decode(json_encode($response), true);
    }

    public function getBooking($session, $requestInfo) {
        $request = [];

        Validator::validateJSONKeys($requestInfo, array('loc'));

        $request['session'] = array_merge($request, $session);
        $request['recordLocator'] = $requestInfo['loc'];

        if(TEST_MODE) {
            print_r($request);
            return $request;
        }
        $response = $this->soapCli->GetBooking($request);

        return json_decode(json_encode($response), true);
    }

    public function cancelLoc($session, $requestInfo) {
        $request = [];
        $pnr = [];

        Validator::validateJSONKeys($requestInfo, array('loc'));

        $request['session'] = array_merge($request, $session);

        $pnr = $this->getBooking($session, $requestInfo)['GetBookingResult'];

        if(TEST_MODE) {
            print_r($pnr);
            return $pnr;
        }
        $response = $this->soapCli->CancelAll($request);

        $this->commitUpdate($session, $requestInfo['loc'], $pnr);
        return json_decode(json_encode($response), true);
    }

    public function clearSession($session) {
        $request = array(
            "session" => $session
        );

        $response = $this->soapCli->Clear($request);
    }

    public function logout($session) {
        try {

            $response = $this->soapCli->Logout(new class($session) {
                function __construct($session) {
                    $this->session =
                        new class($session) {
                            function __construct($session) {
                                $this->SessionControl = $session['SessionControl'];
                                $this->SystemType = 'Default';
                                $this->SessionID = $session['SessionID'];
                                $this->SequenceNumber = $session['SequenceNumber'];
                                $this->MessageVersion = $session['MessageVersion'];
                                $this->Signature = $session['Signature'];
                                $this->SessionPrincipal = "";
                                $this->CultureCode = "pt-BR";
                                $this->ChannelType = 'Default';
                                $this->InTransaction = $session['InTransaction'];
                                $this->TransactionDepth = $session['TransactionDepth'];
                                $this->TransactionCount = $session['TransactionCount'];
                                $this->SecureToken = $session['SecureToken'];
                            }
                        };
                }
            });

            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
