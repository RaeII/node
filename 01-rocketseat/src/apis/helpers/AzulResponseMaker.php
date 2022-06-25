<?php

namespace Api\Helpers;

use Api\Helpers\AzulUtil;
use Api\Interfaces\AerialResponseMaker;
use Util\Formatter;

class AzulResponseMaker implements AerialResponseMaker {

    private function getPromotionCode($serviceCharges) {
        return array_reduce($serviceCharges, function ($carry, $serviceCharge) {
            if($serviceCharge['ChargeType'] == "PromotionDiscount") {
                if(isset($carry['value'])) {
                    $carry['value'] = bcadd($carry['value'], $serviceCharge['ForeignAmount'], 2);
                }else {
                    $carry['code'] = $serviceCharge['ChargeDetail'];
                    $carry['value'] = (float)$serviceCharge['ForeignAmount'];
                }
            }
            return $carry;
        }, []);
    }

    private function filterOverallTaxs($serviceCharges) {
        $filter = function ($serviceCharge) {
            return ($serviceCharge['ChargeType'] === 'Tax' 
            ||  ($serviceCharge['ChargeType'] !== 'FarePrice' 
            &&  $serviceCharge['ChargeType'] !== 'PromotionDiscount' 
            &&  $serviceCharge['ChargeType'] !== 'Discount'));
        };

        return array_filter($serviceCharges, $filter);
    }

    private function filterPromoCodes($serviceCharges) {
        $filter = function ($serviceCharge) {return $serviceCharge['ChargeType'] === 'PromotionDiscount';};

        return array_filter($serviceCharges, $filter);
    }

    private function filterDiscounts($serviceCharges) {
        $filter = function ($serviceCharge) {return $serviceCharge['ChargeType'] === 'Discount';};

        return array_filter($serviceCharges, $filter);
    }

    private function genExtraTaxsInfos($serviceCharge) {
        $typeConversor = function($type) {
            switch ($type) {
                case 'Discount':
                    return 'Disconto';
                case 'PromotionDiscount':
                    return 'Disconto Promocional';
                case 'Tax':
                    return 'Taxa de embarque';
                case 'TravelFee':
                    return 'Taxa de embarque';
                default:
                    return $type;
            }
        };

        return array(
            'type' => $typeConversor($serviceCharge['ChargeType']),
            'total' => $serviceCharge['Amount']
        );
    }

    private function arrangeSegmentInfo($segmentInfo) {
        return array(
            'from' => $segmentInfo['DepartureStation'],
            'to' => $segmentInfo['ArrivalStation'],
            'dep_date' => explode('T', $segmentInfo['STD'])[0],
            'dep_hour' => explode('T', $segmentInfo['STD'])[1],
            'arr_date' => explode('T', $segmentInfo['STA'])[0],
            'arr_hour' => explode('T', $segmentInfo['STA'])[1],
            'flight_number' => $segmentInfo['FlightDesignator']['FlightNumber'],
            'comp_code' => $segmentInfo['FlightDesignator']['CarrierCode']
        );
    }

    // Arrange Taxes
    private function arrangeFeresInfo($segmentInfo) {
        $arrangedFares = [];
        $azulUtil = new AzulUtil();
        $fares = [];

        $arrangePaxFare = function ($fare) {
            $serviceCharges = $fare['InternalServiceCharges']['ServiceCharge'];
            $taxe = [];
            $taxAmount = 0;
            // $servicesSize = count($serviceCharges);

            // Taxs filtered.
            $discounts = $this->filterDiscounts($serviceCharges);
            $promoCodes = $this->filterPromoCodes($serviceCharges);
            $overallTaxs = $this->filterOverallTaxs($serviceCharges);
            for($index = 1; $index < count($serviceCharges); $index++) { 
                if($serviceCharges[$index]['ChargeType'] != 'PromotionDiscount' && $serviceCharges[$index]['ChargeType'] != 'Discount') {
                    $taxAmount = bcadd($taxAmount, $serviceCharges[$index]['Amount'], 2);
                }else if ($serviceCharges[$index]['ChargeType'] == 'Discount') {
                    $taxAmount = bcsub($taxAmount, $serviceCharges[$index]['Amount'], 2);
                }
            }

            $taxe = array_merge($taxe, array_map(array($this, 'genExtraTaxsInfos'), $discounts));
            $taxe = array_merge($taxe, array_map(array($this, 'genExtraTaxsInfos'), $promoCodes));
            $taxe = array_merge($taxe, array_map(array($this, 'genExtraTaxsInfos'), $overallTaxs));

            $fare = array(
                'fare_type' => $fare['PaxType'],
                'amount' => $serviceCharges[0]['Amount'],
                'taxes' => 
                    $taxe,
                // 'tax_amount' => $taxAmount,
                'promotional' => isset($promoCodes[1]) && isset($promoCodes[1]['Amount']) ? $promoCodes[1]['Amount'] : 0
            );

            return $fare;
        };

        if($segmentInfo) {
            if(isset($segmentInfo['Fare'][0])) {
                foreach ($segmentInfo['Fare'] as $fare) {
                    $fares[] = $fare;
                }
            }else {
                $fares[] = $segmentInfo['Fare'];
            }

            // foreach ($fares as $fare) {
            //     if(isset($fare['PaxFares']['PaxFare'][0])) {
            //         $paxsFares = array_merge($paxsFares, $fare['PaxFares']['PaxFare']);
            //     }else {
            //         $paxsFares[] = $fare['PaxFares']['PaxFare'];
            //     }

            //     foreach ($paxsFares as $paxFares) {
            //         array_push($fares, $arrangePaxFare($paxFares, $fare['SellKey'], $fare['ClassOfService'], $fare['FareBasis']));
            //     }
            // }
            $promotionalCode = '';
            foreach ($fares as $fare) {
                $arrangedFare = [];

                // $arrangedFares[] = 
                $paxsFares = [];
                if(isset($fare['PaxFares']['PaxFare'][0])) {
                    $paxsFares = array_merge($paxsFares, $fare['PaxFares']['PaxFare']);
                }else {
                    $paxsFares[] = $fare['PaxFares']['PaxFare'];
                }

                $promotionalCode = $this->getPromotionCode($paxsFares[0]['InternalServiceCharges']['ServiceCharge']);
                $arrangedFare = array(
                    'service_class' => $fare['ClassOfService'],
                    'fare_basis' => $fare['FareBasis'],
                    'carrier_code' => $fare['CarrierCode'],
                    'product_class_code' => $paxsFares[0]['ProductClass'],
                    'product_class' => $azulUtil->getProductClassName($paxsFares[0]['ProductClass']),
                    'paxs_fare' => [],
                    'promotional_code' => isset($promotionalCode['code']) ? $promotionalCode['code'] : '',
                    // 'tax_amount' => $taxAmount,
                    // 'promotional' => 0.00,
                    // 'promotional_code' => '',
                    "key" => $fare['SellKey']
                );
                foreach ($paxsFares as $paxFares) {
                    $arrangedFare['paxs_fare'][] = $arrangePaxFare($paxFares);
                }
                $arrangedFares[] = $arrangedFare;
            }
        }

        return $arrangedFares;
    }

    // Getting overall info about trip
    private function arrangeOverallInfo($segments) {
        $lastSegment = $segments[count($segments) - 1];
        $segmentsOverall = [];
        $connectionsCount = count($segments) - 1;

        
        $segmentsOverall['from'] = $segments[0]['from'];
        $segmentsOverall['dep_date'] = $segments[0]['dep_date'];
        $segmentsOverall['dep_hour'] = $segments[0]['dep_hour'];
        $segmentsOverall['to'] = $lastSegment['to'];
        $segmentsOverall['arr_date'] = $lastSegment['arr_date'];
        $segmentsOverall['arr_hour'] = $lastSegment['arr_hour'];
        $segmentsOverall['flight_number'] = $segments[0]['flight_number'];
        $segmentsOverall['comp_code'] = $segments[0]['comp_code'];
        
        $depDate = new \DateTime($segmentsOverall['dep_date'] . 'T' . $segmentsOverall['dep_hour']);
        $arrDate = new \DateTime($segmentsOverall['arr_date'] . 'T' . $segmentsOverall['arr_hour']);
        $dateDiff = $depDate->diff($arrDate);
        $hoursToAdd = $dateDiff->format('%a') * 24;
        $hours = $dateDiff->format('%h') * 1;

        $diffFormated = ($hours + $hoursToAdd) . $dateDiff->format('h %imin');

        $segmentsOverall['duration'] = $diffFormated;
        $segmentsOverall['connections'] = $connectionsCount;
        return $segmentsOverall;
    }
    /*
        {
            "journeys": [
                {
                    "segments": [
                        {
                            "trip_info": {
                                "from": "FLN",
                                "to": "VCP",
                                "dep_date": "2021-03-26",
                                "arr_date": "2021-03-26",
                                "flight_number": 4199,
                                "comp_code": "AD"
                            }
                        },
                        {
                            "trip_info": {
                                "from": "VCP",
                                "to": "CWB",
                                "dep_date": "2021-03-26",
                                "arr_date": "2021-03-26",
                                "flight_number": 4064,
                                "comp_code": "AD"
                            }
                        }
                    ],
                    "fares": [
                        {
                            "carrier_code": "AD",
                            "product_class": "F+",
                            "fare_type": "ADT",
                            "amount": 675.9000,
                            "tax_amount": 38.38,
                            "key": "0~N~~N07LGBG~07LG~~8~X:"
                        },
                        {
                            "carrier_code": "AD",
                            "product_class": "PR",
                            "fare_type": "ADT",
                            "amount": 770.9000,
                            "tax_amount": 38.38,
                            "key": "0~N~~N07LGAD~07LG~~9~X"
                        }
                    ],
                    "key": "AD~4199~~~FLN~03/26/202106: 05~VCP~03/26/202107: 20~^AD~4064~~~VCP~03/26/202108: 45~CWB~03/26/202109: 45~"
                }
            ]
        }
    */
    public function arrangeSearch($searchRes) {
        if(!isset($searchRes['GetAvailabilityResult']) || count($searchRes['GetAvailabilityResult']['Schedule']) == 0) {
            return [];
        }
        try {
            $journeysAux = $searchRes['GetAvailabilityResult']['Schedule']['JourneyDateMarket']['Journeys'];
            $journeys = [];
            $result = [];

            if(count($journeysAux) <= 0) {
                return [];
            }
            if(isset($journeysAux['InventoryJourney'][0])) {
                $journeys = $journeysAux['InventoryJourney'];
            }else {
                $journeys[] = $journeysAux['InventoryJourney'];
            }

            $result = [];
            foreach ($journeys as $journey) {
                $segment = $journey['Segments']['Segment'];
                $segmentToArrange = [];
                
                $arrangedSegments = [];
                $arrangedSegments['segments'] = [];
                $arrangedSegments['fares'] = [];
                $arrangedSegments['overall'] = [];

                if(isset($segment[0])) {
                    $segmentToArrange = $segment;
                }else {
                    array_push($segmentToArrange, $segment);
                }

                foreach ($segmentToArrange as $segmentInfo) {
                    array_push($arrangedSegments['segments'], $this->arrangeSegmentInfo($segmentInfo));
                }
                $arrangedSegments['fares'] = $this->arrangeFeresInfo($journey['Fares']);

                $arrangedSegments['key'] = $journey['SellKey'];

                $arrangedSegments['overall'] = $this->arrangeOverallInfo($arrangedSegments['segments']);
                array_push($result, $arrangedSegments);

            }
            
        } catch (\Exception $e) {
            throw new \Exception(getErrorMessage('responseMakerError'));
        }

        return $result;
    }

    public function arrangeSearchCombined($searchRes) {
        if(count($searchRes) === 0) {
            return [];
        }
        $arrangedJourneys = [];

        foreach ($searchRes as $trip) {
            $journeys = $trip['JourneyDateMarket']['Journeys']['InventoryJourney'];
            $arrangedJourney = [];
            foreach ($journeys as $journey) {
                $segmentsArranged = [];
                $segmentsFares = [];
                $overallInfo = [];
                $segmentsToArrange = isset($journey['Segments']['Segment'][0]) ? $journey['Segments']['Segment'] : [$journey['Segments']['Segment']];
                $faresToArrange = $journey['Fares'];
                $segmentsArranged = array_map(function($segment) {
                    return $this->arrangeSegmentInfo($segment);
                }, $segmentsToArrange);

                $segmentsFares = $this->arrangeFeresInfo($faresToArrange);
                $overallInfo = $this->arrangeOverallInfo($segmentsArranged);

                $arrangedJourney['segments'] = $segmentsArranged;
                $arrangedJourney['fares'] = $segmentsFares;
                $arrangedJourney['overall'] = $overallInfo;

                $arrangedJourneys[] = $arrangedJourney;
            }
        }

        return $arrangedJourneys;
    }

    // public function arrangeSearch($searchResult, $company) {
    //     switch ($company) {
    //         case 'AD':
    //             return $this->arrangeAzulSearch($searchResult);
    //             break;
    //         default:
    //             throw new \Exception (getErrorMessage('wsNotFound'));
    //             break;
    //     }
    // }

    /*
        {
            'seats': 
            [
                {
                    'location': 'Window'
                    'status': 'Open'
                    'row': '1'
                    'column': '2'
                }
            ]
        }

    */

    // public function arrangeSeatAviability($seatsInfo) {
    //     $arrangedSeatsFeeByGroupReducer = function ($acc, $seatFee) {
    //         $fee = $seatFee['PassengerFee']['ServiceCharges']['ServiceCharge'];
    //         $arrangedFee = [];

    //         $arrangedFee['value'] =         $fee['ForeignAmount'];
    //         $arrangedFee['seat_group'] =    $seatFee['SeatGroup'];
    //         $acc[] =   $arrangedFee;

    //         return $acc;
    //     };
    //     $seatArranger = function ($seat) {
    //         $arrangedSeat = [];

    //         $arrangedSeat = array(
    //             'location' => $seat['Location'],
    //             'status' => $seat['SeatAvailability'],
    //             'row' => $seat['SeatRow'],
    //             'column' => $seat['SeatColumn'],
    //             'seat_group' => $seat['SeatGroup']
    //         );
    //         return $arrangedSeat;
    //     };

    //     try {
    //         $arrangedSeats = [];

    //         $arrangedSeats["airplanes"] = [];
    //         foreach ($seatsInfo as $seatInfo) {
    //             $cabinSeats = $seatInfo['AircraftConfiguration']['AircraftCabins']['AircraftCabin']['AircraftSeats']['AircraftCabinSeat'];
    //             $seatsFee = [];
    //             $arrangingSeats = [];
    //             $flightNumber = $seatInfo['InventoryLegs']['FlightNumber'];
                
    //             if(!empty($seatInfo['SeatGroupPassengerFees'])) {
    //                 $seatsFee = array_reduce(array_values($seatInfo['SeatGroupPassengerFees']), $arrangedSeatsFeeByGroupReducer, []);
    //             }

    //             if(!empty($cabinSeats)) {
    //                 $arrangingSeats['company_code'] = 'AD';
    //                 $arrangingSeats['flight_number'] = $flightNumber;
    //                 foreach ($cabinSeats as $cabinSeat) {
    //                     $seats = $cabinSeat['AircraftCabinSeat'];
        
    //                     if(!isset($arrangingSeats['seats'])) $arrangingSeats['seats'] = [];
    //                     $arrangingSeats['seats'][] = array_map($seatArranger, $seats);

    //                     if(!empty($seatsFee)){
    //                         $arrangingSeats['fees'] = [];
    //                         $arrangingSeats['fees'] = array_merge($arrangingSeats['fees'], $seatsFee);
    //                     }
    //                 }
    //                 $arrangedSeats["airplanes"][] = $arrangingSeats;
    //             }
    //             // else {
    //             //     $arrangedSeats["seats"][$flightNumber] = $arrangeSeats($arrangedSeats["seats"][$flightNumber], $cabinSeats);
    //             // }
    //         }
    //     } catch (\Exception $e) {
    //         throw new \Exception(getErrorMessage('responseMakerError'));
    //     }

    //     return $arrangedSeats;
    // }

    public function arrangeSeatAviability($seatsInfo) {
        $arrangedSeatsFeeByGroupReducer = function ($acc, $seatFee) {
            $fee = $seatFee['PassengerFee']['ServiceCharges']['ServiceCharge'];
            $arrangedFee = [];

            $arrangedFee['value'] =         $fee['ForeignAmount'];
            $arrangedFee['seat_group'] =    $seatFee['SeatGroup'];
            $acc[] =   $arrangedFee;

            return $acc;
        };
        $seatArranger = function ($seat) {
            $arrangedSeat = [];

            $arrangedSeat = array(
                'location' => $seat['Location'],
                'status' => $seat['SeatAvailability'],
                'row' => $seat['SeatRow'],
                'column' => $seat['SeatColumn'],
                'seat_group' => $seat['SeatGroup']
            );
            return $arrangedSeat;
        };

        try {
            $arrangedSeats = [];

            $arrangedSeats["airplanes"] = [];
            foreach ($seatsInfo as $seatInfo) {
                $cabinSeats = $seatInfo['AircraftConfiguration']['AircraftCabins']['AircraftCabin']['AircraftSeats']['AircraftCabinSeat'];
                $seatsFee = [];
                $arrangingSeats = [];
                $flightNumber = $seatInfo['InventoryLegs']['FlightNumber'];
                
                if(!empty($seatInfo['SeatGroupPassengerFees'])) {
                    $seatsFee = array_reduce(array_values($seatInfo['SeatGroupPassengerFees']), $arrangedSeatsFeeByGroupReducer, []);
                }

                if(!empty($cabinSeats)) {
                    $arrangingSeats['company_code'] = 'AD';
                    $arrangingSeats['flight_number'] = $flightNumber;
                    foreach ($cabinSeats as $cabinSeat) {
                        $seats = $cabinSeat['AircraftCabinSeat'];
        
                        if(!isset($arrangingSeats['seats'])) $arrangingSeats['seats'] = [];
                        $arrangingSeats['seats'][] = array_map($seatArranger, $seats);

                        if(!empty($seatsFee)){
                            $arrangingSeats['fees'] = [];
                            $arrangingSeats['fees'] = array_merge($arrangingSeats['fees'], $seatsFee);
                        }
                    }
                    $arrangedSeats["airplanes"][] = $arrangingSeats;
                }
                // else {
                //     $arrangedSeats["seats"][$flightNumber] = $arrangeSeats($arrangedSeats["seats"][$flightNumber], $cabinSeats);
                // }
            }
        } catch (\Exception $e) {
            throw new \Exception(getErrorMessage('responseMakerError'));
        }

        return $arrangedSeats;
    }
    /*
        {
            'id': ,
            'status': ,
            'locator': ,
            'paxs': 
            [
                {
                    'id': ,
                    'first_name': ,
                    'last_name': ,
                    'gender': ,
                    'type': ,
                    'total_cost': ,
                    'balance_due': ,
                    'fee'; {
                         {
                            'code': 'INF',
                            'fare_type': 'SSRFee',
                            'amount': 0.0
                            'charge_detail': 'for free',
                        },
                        {
                            'code': 'EAF',
                            'fare_type': 'SeatFee',
                            'amount': 35.9000,
                            'charge_detail': 'FLN-VCP',
                        },
                        {
                            'code': 'EAF',
                            'fare_type': 'SeatFee',
                            'amount': 35.9000,
                            'charge_detail': 'VCP-BSB',
                        }
                    }
                    'inf': {
                        'first_name': ,
                        'last_name': ,
                        'gender'; ,
                    }
                }
            ]
            'journey' {
                'segments': [
                    {
                        'from': 'FLN',
                        'to': 'VCP',
                        'dep_date': "2021-03'26',
                        'arr_date': "2021-03'26',
                        'flight_number': 4199,
                        'comp_code': 'AD',
                        'seats': 
                        [
                            {
                            'paxId': 1,
                            'row': 1,
                            'column': 1,
                            }
                        ]
                    },
                    {
                        'from": 'VCP',
                        'to": 'CWB',
                        'dep_date": '2021-03-26',
                        'arr_date": '2021-03-26',
                        'flight_number": 4064,
                        'comp_code': 'AD',
                        'seats':
                        [
                            {
                                'paxId': 1,
                                'row': 1,
                                'column': 1,
                            }
                        ]
                    }
                ],
                "fares": [
                    {
                        'carrier_code": 'AD',
                        'product_class": 'F+',
                        'fare_type": 'ADT',
                        'amount": 675.9000,
                        'tax_amount": 38.38,
                    }
                ],
            }
        }
    */

    private function arrangePaxFees($pax) {
        $arrangedFees = [];
        $toArrange = [];

        $arrangeFees = function($fee, $code, $fareType) {
            $arranged = [];

            $arranged['code'] = $code;
            $arranged['fare_type'] = $fareType;
            $arranged['amount'] = ($fee['ChargeType'] != 'Discount' ? '' : '-') . $fee['Amount'];        
            $arranged['charge_detail'] = $fee['ChargeDetail'];

            return $arranged;
        };

        if(isset($pax['PassengerFees']['PassengerFee'])) {
            $fees = $pax['PassengerFees']['PassengerFee'];

            if(isset($fees[0])) {
                $toArrange = $fees;
            }else {
                array_push($toArrange, $fees);
            }

            foreach ($toArrange as $fee) {

                if(isset($fee['ServiceCharges']['ServiceCharge'][0])) {
                    foreach ($fee['ServiceCharges']['ServiceCharge'] as $serviceCharge) {
                        array_push($arrangedFees, $arrangeFees($serviceCharge, $fee['FeeCode'], $fee['FeeType']));
                    }
                }else {
                    array_push($arrangedFees, $arrangeFees($fee['ServiceCharges']['ServiceCharge'], $fee['FeeCode'], $fee['FeeType']));
                }
            }
        }
        return $arrangedFees;
    }

    private function getPaxDocs($doc) {
        if($doc['DocTypeCode'] === 'CPF') return array('cpf' => $doc['DocNumber']);
    }

    private function _arrangeBookingPaxInfo($pax) {
        $arrangedPax = [];

        $arrangedPax['first_name'] = $pax['Name']['FirstName'];
        $arrangedPax['middle_name'] = $pax['Name']['MiddleName'];
        $arrangedPax['last_name'] = $pax['Name']['LastName'];
        $arrangedPax['gender'] = $pax['Gender'];
        $arrangedPax['birth_date'] = $pax['DOB'];
        // if(isset($pax['Name']['CPF'])) $arrangedPax['CPF'] = $pax['Name']['CPF'];
        $arrangedPax['type'] = $pax['PaxPriceType']['PaxType'];
        $arrangedPax['total_cost'] = $pax['TotalCost'];
        $arrangedPax['balance_due'] = $pax['BalanceDue'];
        $arrangedPax['fees'] = [];

        $arrangedPax['fees'] = $this->arrangePaxFees($pax);
        if(isset($pax['PassengerInfant']['DOB'])) {
            $inf = $pax['PassengerInfant'];

            $arrangedInf['first_name'] = $inf['Name']['FirstName'];
            $arrangedPax['middle_name'] = $inf['Name']['MiddleName'];
            $arrangedInf['last_name'] = $inf['Name']['LastName'];
            $arrangedInf['gender'] = $inf['Gender'];
            // if(isset($inf['Name']['CPF'])) $arrangedInf['CPF'] = $inf['Name']['CPF'];
            $arrangedInf['birth_date'] = $inf['DOB'];

            $arrangedPax['inf'] = $arrangedInf;
        }
        return $arrangedPax;
    }

    public function arrangeBookingInfo($booking) {
        // print_r($booking);die();
        try {
            $arrangeSegment = function($segment) {
                $seats = [];

                $arranged['from'] = $segment['DepartureStation'];
                $arranged['to'] = $segment['ArrivalStation'];
                $arranged['dep_date'] = explode('T', $segment['STD'])[0];
                $arranged['dep_hour'] = explode('T', $segment['STD'])[1];
                $arranged['arr_date'] = explode('T', $segment['STA'])[0];
                $arranged['arr_hour'] = explode('T', $segment['STA'])[1];
                $arranged['flight_number'] = $segment['FlightDesignator']['FlightNumber'];
                $arranged['comp_code'] = $segment['FlightDesignator']['CarrierCode'];
                
                if(isset($segment['PaxSegmentServices']['PaxSegmentService']) 
                    && isset($segment['PaxSegmentServices']['PaxSegmentService']['SeatAssignments'])) {

                    $seats = $segment['PaxSegmentServices']['PaxSegmentService'];
                    $seatsToArrange = [];

                    if(isset($seats[0])) {
                        foreach ($seats as $seat) {
                            $seatsToArrange[] = $seat;                        
                        }
                    }else {
                        $seatsToArrange[] = $seats;
                    }

                    foreach ($seatsToArrange as $seatToArrange) {
                        $seat = [];

                        $seat['pax_id'] = $seatToArrange['PassengerID'];
                        $seat['row'] = $seatToArrange['SeatAssignments']['Row'];
                        $seat['column'] = $seatToArrange['SeatAssignments']['Column'];

                        $arranged['seats'][] = $seat;
                    }        
                }

                return $arranged;
            };

            $arrangePayment = function ($payment) {
                $arranged = [];
                $status = '';

                $payment['Status'] === 'Confirmed' && $status = 'confirmed';
                $payment['Status'] === 'Hold' && $status = 'hold';
                $payment['Status'] === 'Closed' && $status = 'closed';
                $status === '' && $status = $payment['Status'];

                $arranged['payment_method'] = $payment['PaymentMethodType'];
                $arranged['currency_code'] = $payment['CurrencyCode'];
                $arranged['nominal_amount'] = $payment['NominalPaymentAmount'];
                $arranged['collected_amount'] = $payment['CollectedAmount'];
                $arranged['quoted_amount'] = $payment['QuotedAmount'];
                $arranged['status'] = $status;
                $arranged['account_number'] = $payment['AccountNumber'];
                $arranged['authorization_code'] = $payment['AuthorizationCode'];
                $arranged['authorization_status'] = $payment['AuthorizationStatus'];
                $arranged['payment_number'] = $payment['PaymentNumber'];
                $arranged['parent_payment_id'] = $payment['ParentPaymentId'];

                return $arranged;
            };

            $arrangeComments = function ($comment) {
                $arrangedComments = [];

                $arrangedComments['text'] = $comment['CommentText'];
                return $arrangedComments;
            };

            $infos = $booking['GetBookingResult'];
            $paxs = !empty($infos['BookingPassengers']['BookingPassenger'][0]) ? $infos['BookingPassengers']['BookingPassenger'] : [$infos['BookingPassengers']['BookingPassenger']];
            $payments = $infos['Payments'];
            $journeys = [];
            $segments = [];
            $fares = [];
            $comments = isset($infos['BookingComments']['BookingComment']) 
                    ? $infos['BookingComments']['BookingComment'] : [];

            if(isset($infos['JourneyServices']['JourneyService'])) {
                if(isset($infos['JourneyServices']['JourneyService'][0])) {
                    foreach ($infos['JourneyServices']['JourneyService'] as $journey) {
                        $journeys[] =  $journey;
                    }
                }else if(isset($infos['JourneyServices']['JourneyService']['Segments'])){
                    $journeys[] =  $infos['JourneyServices']['JourneyService'];
                }
            }


            $arrangedBooking = [];

            $arrangedBooking['id'] = $infos['BookingID'];
            $arrangedBooking['company_code'] = "AD";
            $arrangedBooking['status'] = $infos['BookingStatus'];
            $arrangedBooking['locator'] = $infos['RecordLocator'];
            $arrangedBooking['booking_date'] = '';

            if(str_contains($infos['BookingDate'], '.')) {
                $arrangedBooking['booking_date'] = explode('.', $infos['BookingDate'])[0];
            }
            $arrangedBooking['journeys'] = [];
            if(isset($infos['ParentRecordLocator'])) {
                $arrangedBooking['parent_locator'] = $infos['ParentRecordLocator'];
            }
            $arrangedBooking['paxs'] = [];

            foreach ($journeys as $journey) {
                $arrangedSegment = [];
                $arrangedFare = [];
                $arrangedJourney = [];
                $arrangedJourney['segments'] = [];
                $arrangedJourney['fares'] = [];
                $segments = isset($journey['Segments']['Segment']) ? $journey['Segments']['Segment'] : [];
                $fares = $journey['Fares'];
                $auxSegments = [];

                if(isset($segments[0])) {
                    foreach ($segments as $segment) {
                        array_push($auxSegments, $segment);
                    }
                }else if(count($segments) > 0){
                    array_push($auxSegments, $segments);
                } 
                $segments = $auxSegments;
                foreach ($segments as $segment) {
                    array_push($arrangedSegment, $arrangeSegment($segment));

                    // if(isset($segment['PaxSegmentServices']) && isset($segment['PaxSegmentServices']['PaxSegmentService'])) {
                    //     $aux['dep'] = $segment['DepartureStation'];
                    //     $aux['arr'] = $segment['ArrivalStation'];
                    //     $aux['pax_segment_services'] = $segment['PaxSegmentServices']['PaxSegmentService'];
                    //     $paxSegmentServices[] = $aux;
                    // }
                }

                $arrangedJourney['segments'] = $arrangedSegment;
                // $paxFares = [];
                // if(isset($fares[0])) {
                //     foreach ($fares as $fare) {
                //         if(isset($fare['PaxFares']['PaxFare'][0])) {
                //             foreach($fare['PaxFares']['PaxFare'][0] as $paxFare) {
                //                 array_push($paxFares, $paxFare);
                //             }
                //         }else {
                //             array_push($paxFares, $fare['PaxFares']['PaxFare']);
                //         }
                //     }
                // }else if(count($fares) > 0) {
                //     if(isset($fares['PaxFares']['PaxFare'][0])) {
                //         foreach($fares['PaxFares']['PaxFare'] as $paxFare) {
                //             array_push($paxFares, $paxFare);
                //         }
                //     }else {
                //         array_push($paxFares, $fares['PaxFares']['PaxFare']);
                //     }
                // }
                // foreach ($paxFares as $fare) {
                //     array_push($arrangedFare, $arrangeFare($fare));
                // }
                $fares = $this->arrangeFeresInfo($fares);
                if(count($fares) > 0) $arrangedJourney['fares'] = $fares;
                
                $arrangedJourney['overall'] = $this->arrangeOverallInfo($arrangedSegment);
                $arrangedBooking['journeys'][] = $arrangedJourney;
            }

            $arrangedPaxs = [];
            foreach ($paxs as $pax) {
                $arrangedPax = [];
                $arrangedPax = $this->_arrangeBookingPaxInfo($pax);
                if(isset($pax['PassengerTravelDocs'])) {
                    $docs = array_values($pax['PassengerTravelDocs']);

                    foreach ($docs as $doc) {

                        $arrangedPax = array_merge($arrangedPax, $this->getPaxDocs($doc));
                    }
                }
                array_push($arrangedPaxs, $arrangedPax);
            }

            $arrangedBooking['paxs'] = array_merge($arrangedBooking['paxs'], $arrangedPaxs);

            $arrangedComments = [];
            if(isset($comments[0])) {
                foreach ($comments as $comment) {
                    array_push($arrangedComments, $arrangeComments($comment));
                }
            }else if(count($comments) > 0){
                array_push($arrangedComments, $arrangeComments($comments));
            }
            $arrangedBooking['comments'] = $arrangedComments;

            $arrangedPayment = [];            
            $paymentsToArrange = [];

            if(isset($payments['Payment'][0])) {
                $paymentsToArrange = $payments['Payment'];
            }else if(isset($payments['Payment']['Status'])){
                $paymentsToArrange[] = $payments['Payment'];
            }

            foreach ($paymentsToArrange as $paymentToArrange) {
                $arrangedPayment[] = $arrangePayment($paymentToArrange);
            }
            $arrangedBooking['payments'] = $arrangedPayment;
        } catch (\Exception $e) {
            throw new \Exception(getErrorMessage('responseMakerError'));
        }

        return $arrangedBooking;
    }

    public function arrangeDivideBooking($divideResult) {
        try {
            $divide = $divideResult['DivideResult'];
            $arranged = [];
    
            $arranged['total_cost'] = $divide['TotalCost'];
            $arranged['balance_due'] = $divide['BalanceDue'];
            $arranged['locator'] = $divide['ChildRecordLocator'];
        } catch (\Exception $e) {
            throw new \Exception(getErrorMessage('responseMakerError'));
        }

        return $arranged;
    }
    // public function arrangeSeatAviability($seatResult, $company) {
    //     switch ($company) {
    //         case 'AD':
    //             return $this->arrangeAzulSeatAviability($searchResult);
    //             break;
    //         default:
    //             throw new \Exception (getErrorMessage('wsNotFound'));
    //             break;
    //     }
    // }

    public function updatePaxTotalValue($arrangedBooking) {
        $totalJourneyValue = 0;

        foreach ($arrangedBooking['journeys'] as $journey) {
            foreach ($journey['fares'] as $tax) {
                $totalJourneyValue = bcadd($totalJourneyValue, $tax['amount'], 2);
                $totalJourneyValue = bcadd($totalJourneyValue, $tax['tax_amount'], 2);
            }
        }

        foreach ($arrangedBooking['paxs'] as &$pax) {
            $totalByPaxFee = 0;
            $totalSumByPax = 0;

            foreach ($pax['fees'] as $fee) {
                $totalByPaxFee = bcadd($totalByPaxFee, $fee['amount'], 2);
            }

            $totalSumByPax = bcadd($totalByPaxFee, $totalJourneyValue, 2);
            $pax['total_cost'] = $totalSumByPax;
            $pax['balance_due'] = $totalSumByPax;
        }
        unset($pax);

        return $arrangedBooking;
    }
}