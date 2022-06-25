<?php

namespace Api\Helpers;

use Api\Helpers\AzulUtil;
use Api\Interfaces\AerialResponseMaker;
use Util\Formatter;

class SabreResponseMaker implements AerialResponseMaker
{

    /*########################### UTIL ###########################*/
    function arrangeSegment($segment)
    {
        $arranged = [];

        $arranged['from'] = $segment['DepartureAirport']['@attributes']['LocationCode'];
        $arranged['to'] = $segment['ArrivalAirport']['@attributes']['LocationCode'];
        $arranged['dep_date'] = explode('T', $segment['@attributes']['DepartureDateTime'])[0];
        $arranged['dep_hour'] = explode('T', $segment['@attributes']['DepartureDateTime'])[1];
        $arranged['arr_date'] = explode('T', $segment['@attributes']['ArrivalDateTime'])[0];
        $arranged['arr_hour'] = explode('T', $segment['@attributes']['ArrivalDateTime'])[1];
        $arranged['flight_number'] = $segment['OperatingAirline']['@attributes']['FlightNumber'];
        $arranged['comp_code'] = $segment['OperatingAirline']['@attributes']['Code'];
        $arranged['book_code'] = $segment['@attributes']['ResBookDesigCode'];

        return $arranged;
    }

    function arrangeSegments(Array $segments) {
        $arrangedSegs = [];

        foreach ($segments as $segment) {
            $toArrange = [];

            if (isset($segment['FlightSegment'][0])) {
                $toArrange = $segment['FlightSegment'];
            } else {
                $toArrange[] = $segment['FlightSegment'];
            }
            $arrangedSegs[] = array_map(array($this, 'arrangeSegment'), $toArrange);
        }

        return $arrangedSegs;
    }

    function taxTypeConversor($type)
    {
        switch ($type) {
            case 'TOTALTAX':
                return 'Taxa de embarque';
        }
        return '';
    }

    function arrangeSearchJourney($seg, $fare) {
        $firstLeg           = $seg[0];
        $arrangedJourney    = [];
        $segmentsOverall    = [];
        $segmentsOverall['from'] = $firstLeg['from'];
        $segmentsOverall['dep_date'] = $firstLeg['dep_date'];
        $segmentsOverall['dep_hour'] = $firstLeg['dep_hour'];
        $segmentsOverall['to'] = $seg[count($seg) - 1]['to'];
        $segmentsOverall['arr_date'] = $seg[count($seg) - 1]['arr_date'];
        $segmentsOverall['arr_hour'] = $seg[count($seg) - 1]['arr_hour'];
        $segmentsOverall['flight_number'] = $firstLeg['flight_number'];
        $segmentsOverall['comp_code'] = $firstLeg['comp_code'];

        $arrangedJourney['segments'] = $seg;
        $arrangedJourney['fares'] = $fare;
        $arrangedJourney['overall'] = $segmentsOverall;

        return $arrangedJourney;
    }

    function reduceSegmentsFlightNumber($acc, $segment) {
        if(!empty($acc)) $acc .= '-';

        return $acc . $segment['flight_number'];
    }

    /*########################### ARRANGERS ###########################*/
    public function arrangeSearch($searchRes, $multiSegment = false)
    {
        $journeys = $searchRes;
        $arrangedRes = [];

        foreach ($journeys as $journey) {
            $arrangedJourney = [];
            $arrangedSegs = [];
            $arrangedFares = [];
            $segmentsContainer = $journey['AirItinerary']['OriginDestinationOptions'];
            $fares = [];
            $segments = [];
            $segmentsOverall = [];

            $fares = array_values($journey['TPA_Extensions']['AdditionalFares']);
            $segments = array_values($segmentsContainer);

            // ############### Segment Arrange
            // foreach ($segments as $segment) {
            //     $toArrange = [];

            //     if (isset($segment['FlightSegment'][0])) {
            //         $toArrange = $segment['FlightSegment'];
            //     } else {
            //         $toArrange[] = $segment['FlightSegment'];
            //     }
            //     $arrangedSegs = array_map(array($this, 'arrangeSegments'), $toArrange);
            // }
            $arrangedSegs = $this->arrangeSegments($segments)[0];

            // print_r($arrangedSegs);die();
            // if(!$multiSegment) {
            //     if(isset($legs[1])) $returnSegToArranged = $legs[1]['FlightSegment'];

            //     if(count($returnSegToArranged) > 0) $returnArrangedSegs = array_map(array($this, 'arrangeSegment'), $returnSegToArranged);

            // }else {
            //     throw new \Exception(getErrorMessage('feature_not_implemented'));
            //     // foreach ($legs as $leg) {
            //     //     $goingArrangedSegs = array_map(array($this, 'arrangeSegment'), $goingSegToArranged);
            //     // }
            // }


            // ############### Fare Arrange
            foreach ($fares as $fare) {
                $fareBreakDowns = [];

                if (isset($fare['AirItineraryPricingInfo']['PTC_FareBreakdowns'])) {
                    $fareBreakDowns = [];
                    $arrangedFare = [];
                    if (isset($fare['AirItineraryPricingInfo']['PTC_FareBreakdowns']['PTC_FareBreakdown'][0])) {
                        $fareBreakDowns = $fare['AirItineraryPricingInfo']['PTC_FareBreakdowns']['PTC_FareBreakdown'];
                    } else {
                        $fareBreakDowns[] = $fare['AirItineraryPricingInfo']['PTC_FareBreakdowns']['PTC_FareBreakdown'];
                    }

                    $fareBasis = array_values($fareBreakDowns[0]['FareBasisCodes']);
                    $arrangedFare['service_class'] = substr($fareBasis[0][0], 0, 1);
                    $arrangedFare['fare_basis'] = $fareBasis[0][0];
                    // $arranged['carrier_code'] = 'AD';
                    $arrangedFare['product_class'] = $fare['AirItineraryPricingInfo']['@attributes']['BrandID'];
                    $arrangedFare['promotional_code'] = "";
                    $arrangedFare['paxs_fare'] = [];
                    $arrangedFare['key'] = "";

                    foreach ($fareBreakDowns as $fareBreakDown) {
                        $fareComponents = $fareBreakDown['PassengerFare']['TPA_Extensions']['FareComponents']['FareComponent'];
                        $fareToArrange = [];
                        $arrangedPaxFare = [];
                        $code = '';
                        $isChild = false;

                        $fareToArrange = $fareComponents;

                        // Check if pax type os CHD.
                        if (!empty($fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'])) {
                            $messages = [];

                            if (isset($fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'][0])) {
                                $messages = $fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'];
                            } else {
                                $messages[] = $fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'];
                            }

                            $isChild = array_filter($messages, function ($message) {
                                return (str_contains($message['@attributes']['Info'], 'CNN NOT APPLICABLE - ADT FARE USED'));
                            });
                        }
                        if (!$isChild) {
                            $code = $fareBreakDown['PassengerTypeQuantity']['@attributes']['Code'];
                        } else {
                            $code = 'CHD';
                        }

                        $arrangedPaxFare = [
                            'fare_type' => $code,
                            'amount' => $fareToArrange['BaseFare']['@attributes']['Amount'],
                            'taxes' => [],
                            'promotional' => 0
                        ];

                        $taxes = [];
                        foreach ($fareToArrange['Taxes'] as $tax) {
                            $taxes[] = [
                                'type' => $this->taxTypeConversor($tax['@attributes']['TaxCode']),
                                'total' => $tax['@attributes']['Amount']
                            ];
                        }

                        $arrangedPaxFare['taxes'] = $taxes;

                        $arrangedFare['paxs_fare'][] = $arrangedPaxFare;
                    }
                    $arrangedFares[] = $arrangedFare;
                    // if(!$multiSegment) {
                    // foreach ($fareBreakDowns as $fareBreakDown) {
                    //     $prices = $fareBreakDown['PassengerFare']['TPA_Extensions']['FareComponents']['FareComponent'];
                    //     $fareBasis = $fareBreakDown['FareBasisCodes']['FareBasisCode'];

                    //     $toArrange = [];
                    //     // $toArrange['service_class'] = ;
                    //     $toArrange['fare_basis'] = $fareBasis[0]['@attributes']['BookingCode'];
                    //     // $toArrange['carrier_code'] = ;
                    //     $arrangedGoingFare = $arrangeFare($prices[0], $fareBasis[0]);
                    //     if(isset($price[1])) $arrangedReturnFare = $arrangeFare($prices[1], $fareBasis[array_search($returnArrangedSegs[0]['from'], array_column($fareBasis, 'DepartureAirportCode'))]);
                    //     // $goingFare = $tripFares[0]
                    // }
                    // }
                }
            }

            // Getting overall info about trip
            $segmentsOverall['from'] = $arrangedSegs[0]['from'];
            $segmentsOverall['dep_date'] = $arrangedSegs[0]['dep_date'];
            $segmentsOverall['dep_hour'] = $arrangedSegs[0]['dep_hour'];
            $segmentsOverall['to'] = $arrangedSegs[count($arrangedSegs) - 1]['to'];
            $segmentsOverall['arr_date'] = $arrangedSegs[count($arrangedSegs) - 1]['arr_date'];
            $segmentsOverall['arr_hour'] = $arrangedSegs[count($arrangedSegs) - 1]['arr_hour'];
            $segmentsOverall['flight_number'] = $arrangedSegs[0]['flight_number'];
            $segmentsOverall['comp_code'] = $arrangedSegs[0]['comp_code'];

            $arrangedJourney['segments'] = $arrangedSegs;
            $arrangedJourney['fares'] = $arrangedFares;
            $arrangedJourney['overall'] = $segmentsOverall;
            $arrangedRes[] = $arrangedJourney;
        }
        return $arrangedRes;
    }

    public function arrangeCombinedSearch($data) {
        $arrangedJourneys   = [];
        $arrangedSegs       = [];
        $arrangedRes        = [];

        foreach ($data[0]['PricedItinerary'] as $itinerary) {
            $journeys   = $itinerary['AirItinerary']['OriginDestinationOptions']['OriginDestinationOption'];
            $fares      = array_values($itinerary['TPA_Extensions']['AdditionalFares']);
            $arrangedFares = [];
            $fareByJourney = [];
            $arrangedSearchJourneys = [];

            $arrangedSegs = $this->arrangeSegments($journeys);

            foreach ($fares as $fare) {
                $fareBreakDowns = [];
                $arrangedFareByjourney = [];

                if (isset($fare['AirItineraryPricingInfo']['PTC_FareBreakdowns'])) {
                    $fareBreakDowns = [];
                    $arrangedFare   = [];
                    $fareByJourney  = [];
                    $arrangedByPaxFares = [];

                    if (isset($fare['AirItineraryPricingInfo']['PTC_FareBreakdowns']['PTC_FareBreakdown'][0])) {
                        $fareBreakDowns = $fare['AirItineraryPricingInfo']['PTC_FareBreakdowns']['PTC_FareBreakdown'];
                    } else {
                        $fareBreakDowns[] = $fare['AirItineraryPricingInfo']['PTC_FareBreakdowns']['PTC_FareBreakdown'];
                    }

                    $fareBasis = $fareBreakDowns[0]['FareBasisCodes']['FareBasisCode'];

                    $arrangedFare['service_class']      = '';
                    $arrangedFare['fare_basis']         = '';
                    // $arranged['carrier_code'] = 'AD';
                    $arrangedFare['product_class']      = $fare['AirItineraryPricingInfo']['@attributes']['BrandID'];
                    $arrangedFare['promotional_code']   = "";
                    $arrangedFare['paxs_fare']          = [];
                    $arrangedFare['key']                = "";

                    foreach ($fareBreakDowns as $fareBreakDown) {
                        $fareComponents = $fareBreakDown['PassengerFare']['TPA_Extensions']['FareComponents'];
                        $fareComponents = isset($fareComponents['FareComponent'][0]) ? $fareComponents['FareComponent'] : [$fareComponents['FareComponent']];
                        $fareToArrange  = [];
                        $code = '';
                        $isChild = false;

                        // Check if pax type os CHD.
                        if (!empty($fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'])) {
                            $messages = [];

                            if (isset($fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'][0])) {
                                $messages = $fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'];
                            } else {
                                $messages[] = $fareBreakDown['PassengerFare']['TPA_Extensions']['Messages']['Message'];
                            }

                            $isChild = array_filter($messages, function ($message) {
                                return (str_contains($message['@attributes']['Info'], 'CNN NOT APPLICABLE - ADT FARE USED'));
                            });
                        }
                        if (!$isChild) {
                            $code = $fareBreakDown['PassengerTypeQuantity']['@attributes']['Code'];
                        } else {
                            $code = 'CHD';
                        }

                        // Arrange fares by pax type.
                        $arrangedByPaxFares[] = array_map(function ($fareComponent) use($code) {
                            $arrangedPaxFare = [
                                'fare_type' => $code,
                                'amount' => $fareComponent['BaseFare']['@attributes']['Amount'],
                                'taxes' => [],
                                'promotional' => 0
                            ];

                            $taxes = [];
                            foreach ($fareComponent['Taxes'] as $tax) {
                                $taxes[] = [
                                    'type' => $this->taxTypeConversor($tax['@attributes']['TaxCode']),
                                    'total' => $tax['@attributes']['Amount']
                                ];
                            }

                            $arrangedPaxFare['taxes'] = $taxes;

                            return $arrangedPaxFare;
                        }, $fareComponents);

                        // $arrangedPaxFares[]
                    }

                    // Arrange fares by journeys.
                    for ($index = 0; $index < count($arrangedByPaxFares); $index++) {
                        // Separate fares by journeys.
                        for ($segIndex = 0; $segIndex < count($arrangedSegs); $segIndex++) {
                            $journeyFares = [];

                            if(empty($arrangedFareByjourney[$segIndex])) {
                                $arrangedFareByjourney[$segIndex] = $arrangedFare;

                                $previousSegs   = array_slice($arrangedSegs, 0, $index);
                                $journeyLegsQtt = array_reduce($previousSegs, function($acc, $seg) {
                                    return $acc + count($seg);
                                }, 0);
                                $arrangedFareByjourney[$segIndex]['service_class'] = substr($fareBasis[$journeyLegsQtt], 0, 1);
                                $arrangedFareByjourney[$segIndex]['fare_basis']    = $fareBasis[$journeyLegsQtt];
                            };

                            $journeyFares = $arrangedByPaxFares[$index][$segIndex];


                            $arrangedFareByjourney[$segIndex]['paxs_fare'][] = $journeyFares;
                        };

                    }
                    // print_r($arrangedFareByjourney);exit;
                    // $arrangedFare['paxs_fare'] = $arrangedByPaxFares[0];
                    // $fareByJourney[] = $arrangedFare;
                    // $arrangedFare['paxs_fare'] = $arrangedByPaxFares[1];
                    // $fareByJourney[] = $arrangedFare;
                    // if(!$multiSegment) {
                    // foreach ($fareBreakDowns as $fareBreakDown) {
                    //     $prices = $fareBreakDown['PassengerFare']['TPA_Extensions']['FareComponents']['FareComponent'];
                    //     $fareBasis = $fareBreakDown['FareBasisCodes']['FareBasisCode'];

                    //     $toArrange = [];
                    //     // $toArrange['service_class'] = ;
                    //     $toArrange['fare_basis'] = $fareBasis[0]['@attributes']['BookingCode'];
                    //     // $toArrange['carrier_code'] = ;
                    //     $arrangedGoingFare = $arrangeFare($prices[0], $fareBasis[0]);
                    //     if(isset($price[1])) $arrangedReturnFare = $arrangeFare($prices[1], $fareBasis[array_search($returnArrangedSegs[0]['from'], array_column($fareBasis, 'DepartureAirportCode'))]);
                    //     // $goingFare = $tripFares[0]
                    // }
                    // }

                    if(!empty($arrangedFareByjourney)) $arrangedFares[] = $arrangedFareByjourney;
                }
            }

            // Separe journeys.
            for ($index = 0; $index < count($arrangedSegs); $index++) {
                $seg            = $arrangedSegs[$index];
                $fares          = [];

                // if(empty($arrangedSearchJourneys[$index])) $arrangedSearchJourneys[$index] = [];
                // print_r($arrangedSegs);
                // print_r($arrangedFares);exit;
                $fares = array_map(function($fares) use($index) {

                    return $fares[$index];
                }, $arrangedFares);

                $arrangedSearchJourneys[$index][]  = $this->arrangeSearchJourney($seg, $fares);
            }

            $possibleCombinations = array_map(function ($journey) {
                return array_reduce($journey[0]['segments'], [$this, 'reduceSegmentsFlightNumber'], '');

                return $journey;
            }, array_slice($arrangedSearchJourneys, 1));

            // Merge all journeys
            for ($index = 0; $index < count($arrangedSearchJourneys); $index++) {
                $arrangedSearchJourneys[0][0]['possible_combinations'] = $possibleCombinations;

                if(empty($arrangedRes[$index])) $arrangedRes[$index] = [];

                $arrangedRes[$index] = array_merge($arrangedRes[$index], $arrangedSearchJourneys[$index]);
            }
        }

        for ($index = 0; $index < count($arrangedRes); $index++) {
            $journeys = $arrangedRes[$index];

            $journeys = array_reduce($journeys, function($acc, $journey) {
                $flightsNumber = array_reduce($journey['segments'], [$this, 'reduceSegmentsFlightNumber'], '');

                if(!empty($acc[$flightsNumber])) {
                    if(!empty($journey['possible_combinations'])) $acc[$flightsNumber]['possible_combinations'] =array_merge($acc[$flightsNumber]['possible_combinations'], $journey['possible_combinations']);
                }else {
                    $acc[$flightsNumber] = $journey;
                }

                return $acc;
            }, []);

            $arrangedRes[$index] = array_values($journeys);
        }

        // foreach ($arrangedRes as $key => $journey) {
        //     array_map(function () {

        //     }, $journey)
        // }
        return $arrangedRes;
    }

    public function arrangeBookingInfo($booking)
    {
        $arrangeSeats = function ($seat) {
            $arrangedSeat = [];

            $arrangedSeat['row']    = str_split($seat['SeatNumber'], 1)[0];
            $arrangedSeat['column'] = str_split($seat['SeatNumber'], 1)[1];
            $arrangedSeat['pax_id'] = $seat['NameNumber'];

            return $arrangedSeat;
        };

        //   $booking = $this->getArr()['GetReservationRS'];
        $fares = [];
        $paxs = [];
        $journeys = [];
        $arrangedJourneys = [];
        $arrangedFare = [];
        $arrangedPaxs = [];
        $arranged = [];
        $segmentsToArrange = [];
        $latamUtil = new LatamUtil();

        $arranged['company_code'] = $booking['Reservation']['POS']['Source']['@attributes']['AirlineVendorID'];
        // To Do
        $arranged['status'] = '';
        $arranged['locator'] = $booking['Reservation']['BookingDetails']['RecordLocator'];
        $arranged['reg_datetime'] = $booking['Reservation']['BookingDetails']['CreationTimestamp'];
        if(isset($booking['Reservation']['PassengerReservation']['Segments']['Segment'][0]))        $segmentsToArrange = $booking['Reservation']['PassengerReservation']['Segments']['Segment'];
        else if(isset($booking['Reservation']['PassengerReservation']['Segments']['Segment']))  $segmentsToArrange[] = $booking['Reservation']['PassengerReservation']['Segments']['Segment'];

        $journeys = array_values(array_reduce($segmentsToArrange, function ($acc, $segment) {
            $index = $segment['Air']['MarriageGrp']['Group'];

            $acc[$index][] = $segment['Air'];
            return $acc;
        }, []));
        $fares = [];
        $paxs = [];


        // Set FARES var
        if (isset($booking['PriceQuote']['PriceQuoteInfo']['Details'][0])) {
            $fares = $booking['PriceQuote']['PriceQuoteInfo']['Details'];
        } else {
            $fares[] = $booking['PriceQuote']['PriceQuoteInfo']['Details'];
        }

        // Set PAXS var
        if (isset($booking['Reservation']['PassengerReservation']['Passengers']['Passenger'][0])) {
            $paxs = $booking['Reservation']['PassengerReservation']['Passengers']['Passenger'];
        } else {
            $paxs[] = $booking['Reservation']['PassengerReservation']['Passengers']['Passenger'];
        }

        /*####################### ARRANGE JOURNEY #########################*/

        foreach ($journeys as $journey) {
            $arrangedSegments = [];

            foreach ($journey as $segment) {
                $seats = [];
                $arrangedSeats = [];

                $arrangedSegment = [];
                $arrangedSegment['from']            = $segment['DepartureAirport'];
                $arrangedSegment['to']              = $segment['ArrivalAirport'];
                $arrangedSegment['dep_date']        = explode('T', $segment['DepartureDateTime'])[0];
                $arrangedSegment['dep_hour']        = explode('T', $segment['DepartureDateTime'])[1];
                $arrangedSegment['arr_date']        = explode('T', $segment['ArrivalDateTime'])[0];
                $arrangedSegment['arr_hour']        = explode('T', $segment['ArrivalDateTime'])[1];
                $arrangedSegment['flight_number']   = $segment['MarketingFlightNumber'];
                $arrangedSegment['comp_code']       = $segment['MarketingAirlineCode'];
                $arrangedSegment['service_class']   = $segment['ClassOfService'];

                if(!empty($segment['Seats'])) {
                    $seats = isset($segment['Seats']['PreReservedSeats']['PreReservedSeat'][0]) ? $segment['Seats']['PreReservedSeats']['PreReservedSeat']
                    : [$segment['Seats']['PreReservedSeats']['PreReservedSeat']];

                    $arrangedSeats = array_map($arrangeSeats, $seats);
                    $arrangedSegment['seats'] = $arrangedSeats;
                }

                $arrangedSegments[] = $arrangedSegment;
            }

            $arrangedJourneys = $arrangedSegments;
        }

        /*######################### ARRANGE FARE #########################*/
        $segmentsInfo = [];

        if (isset($fares[0]['SegmentInfo'][0])) $segmentsInfo = $fares[0]['SegmentInfo'];
        else $segmentsInfo[] = $fares[0]['SegmentInfo'];

        // $fare['details']
        $arrangedFare['service_class'] = $segmentsInfo[0]['Flight']['ClassOfService'];
        $arrangedFare['fare_basis'] = $segmentsInfo[0]['FareBasis'];
        $arrangedFare['carrier_code'] = $segmentsInfo[0]['Flight']['MarketingFlight'];
        $arrangedFare['product_class'] = $latamUtil->getBrandByFareBasis($segmentsInfo[0]['FareBasis']);
        $arrangedFare['paxs_fare'] = [];

        foreach ($fares as $fare) {
            $arrangedPaxFare = [
                'fare_type' => '',
                'amount' => 0,
                'taxes' => []
            ];
            $taxs = [];

            if (!isset($fare['FareInfo']['TaxInfo']['Tax'][0])) {
                $taxs[] = $fare['FareInfo']['TaxInfo']['Tax'];
            } else {
                $taxs = $fare['FareInfo']['TaxInfo']['Tax'];
            }

            $arrangedPaxFare['fare_type'] = $fare['@attributes']['passengerType'];
            $arrangedPaxFare['amount'] = $fare['FareInfo']['BaseFare'];
            $arrangedPaxFare['taxes'] = array_map(function ($tax) {
                return array(
                    'type' => $tax['@attributes']['code'],
                    'total' => $tax['Amount']
                );
            }, $taxs);

            $arrangedFare['paxs_fare'][] = $arrangedPaxFare;
        }

        /*######################### ARRANGE PAX #########################*/

        foreach ($paxs as $pax) {
            $paxArranged    = array(
                "first_name" => "",
                "middle_name" => "",
                "last_name" => "",
                "gender" => "",
                "type" => "",
                "total_cost" => 0,
                "total_tax" => 0,
                "tariff" => 0,
                "fees" => [],
                // "GSR_tickets_number" => []
            );
            $paxCostInfo    = [];
            $paxTotalCost   = [];
            $paxFareInfo    = [];

            if (isset($booking['PriceQuote']['PriceQuoteInfo']['Summary']['NameAssociation'][0])) {
                $paxCostInfo = $booking['PriceQuote']['PriceQuoteInfo']['Summary']['NameAssociation'];
            } else {
                $paxCostInfo[] = $booking['PriceQuote']['PriceQuoteInfo']['Summary']['NameAssociation'];
            }

            $paxArranged['first_name']  = $pax['FirstName'];
            $paxArranged['last_name']   = $pax['LastName'];
            $paxArranged['type']        = substr($pax['@attributes']['referenceNumber'], 0, 3);
            $paxArranged['id_cia']      = $pax['@attributes']['nameId'];

            // if(!empty($pax['SpecialRequests'])) {
            //     $paxArranged['GSR_tickets_number'] = array_reduce($pax['SpecialRequests']['GenericSpecialRequest'], function ($acc, $grs) {
            //         if(!in_array($grs['TicketNumber'], $acc)) $acc[] = $grs['TicketNumber'];

            //         return $acc;
            //     }, []);
            // }
            // Filter pax cost.
            $paxCost = array_values(array_filter($paxCostInfo, function ($paxCost) use ($paxArranged) {
                return ($paxCost['@attributes']['firstName'] === $paxArranged['first_name'] && $paxCost['@attributes']['lastName'] === $paxArranged['last_name']);
            }));

            if (count($paxCost) > 0) {
                $paxTotalCost = $paxCost[0]['PriceQuote']['Amounts']['Total'];
                $paxArranged['total_cost'] = $paxTotalCost;

                $paxFareInfo = array_filter($fares, function($fare) use($paxTotalCost) {
                    return $fare['FareInfo']['TotalFare'] === $paxTotalCost;
                });

                $paxArranged['tariff']    = $paxFareInfo[0]['FareInfo']['BaseFare'];
                $paxArranged['total_tax']       = $paxFareInfo[0]['FareInfo']['TotalTax'];
            }


            if (!empty($pax['MiddleName'])) $paxArranged['middle_name'] = $pax['MiddleName'];
            if (!empty($pax['Gender']))     $paxArranged['gender'] = $pax['Gender'];

            if(!empty($pax['AncillaryServices'])) {
                $ancillaries = isset($pax['AncillaryServices']['AncillaryService'][0]) ? $pax['AncillaryServices']['AncillaryService']
                                    : [$pax['AncillaryServices']['AncillaryService']];

                $paxArranged['fees'] = array_map(function ($ancillary) {
                    $arrangedFee = [];

                    $arrangedFee['ancillary_id']       = $ancillary['@attributes']['id'];
                    $arrangedFee['code']               = !empty($ancillary['SSRCode']) ? $ancillary['SSRCode'] : AerialUtil::getSSRCodeBySubCode($ancillary['RficSubcode']);
                    $arrangedFee['fare_type']          = "SSRFee";
                    $arrangedFee['amount']             = $ancillary['TTLPrice']['Price'];
                    $arrangedFee['Currency']           = $ancillary['TTLPrice']['Currency'];
                    $arrangedFee['price']              = $ancillary['TTLPrice']['Price'];
                    $arrangedFee['emd_number']         = !empty($ancillary['Segment']['EMDNumber']) ? $ancillary['Segment']['EMDNumber'] : '' ;
                    $arrangedFee['commercialName']     = $ancillary['CommercialName'];
                    $arrangedFee['rficCode']           = $ancillary['RficCode'];
                    $arrangedFee['rficSubcode']        = $ancillary['RficSubcode'];
                    $arrangedFee['sSRCode']            = $ancillary['SSRCode'];
                    $arrangedFee['groupCode']          = $ancillary['GroupCode'];
                    $arrangedFee['segmentIndicator']   = $ancillary['SegmentIndicator'];
                    $arrangedFee['refundIndicator']    = $ancillary['RefundIndicator'];
                    $arrangedFee['commisionIndicator'] = $ancillary['CommisionIndicator'];
                    $arrangedFee['interlineIndicator'] = $ancillary['InterlineIndicator'];
                    $arrangedFee['segment']            = !empty($ancillary['Segment']) ? $ancillary['Segment'] : '';

                    return $arrangedFee;
                }, $ancillaries);

                if(count($paxArranged['fees']) > 0) {
                    $paxArranged['total_cost'] += array_reduce($paxArranged['fees'], function(float $acc, Array $fee) : float {
                        $acc += $fee['amount'];

                        return $acc;
                    }, 0.00);
                }
            }

              $arrangedPaxs[] = $paxArranged;
        }
        /*#################################################*/
        $journey = [];

        $journey['segments'] = $arrangedJourneys;
        $journey['fares'][] = $arrangedFare;
        $arranged['journeys'][] = $journey;
        $arranged['paxs'] = $arrangedPaxs;

        return $arranged;
    }

    public function arrangeSeatAviability($seatsInfo)
    {
    }

    public function arrangeDivideBooking($divideResult)
    {
    }

    public function arrangeSeatMap(Array $seatsMap): Array
    {
        $seats = $seatsMap['SeatMap']['Cabin']['Row'];
        $arrangedSeats = [];

        $arrangedSeats['company_code']  = isset($seatsMap['SeatMap']['FareAvailQualifiers']['FareBreakCriteria'][0]) ?
            $seatsMap['SeatMap']['FareAvailQualifiers']['FareBreakCriteria'][0]['@attributes']['governingCarrier'] :
            $seatsMap['SeatMap']['FareAvailQualifiers']['FareBreakCriteria']['@attributes']['governingCarrier'];

        $arrangedSeats['flight_number'] = $seatsMap['SeatMap']['Flight']['Operating'];

        foreach ($seats as $seatRow) {
            $row = $seatRow['RowNumber'];
            $arrangedSeatsRow = [];

            foreach ($seatRow['Seat'] as $seatCol) {
                $seatSides      = isset($seatCol['Location'][0]) ? $seatCol['Location'] : (isset($seatCol['Location']) ? [$seatCol['Location']] : []);
                $status         = $seatCol['Occupation']['Detail'];
                $col            = $seatCol['Number'];

                $seatSideDesc   = array_reduce($seatSides, function (String $acc, Array $side) {
                    if(strlen($acc) === 0) return $side['Detail'];
                    else return "$acc - {$side['Detail']}";
                }, '');

                $arrangedSeatsRow[] = [
                    "location" => $seatSideDesc,
                    "status" => $status,
                    "row" => $row,
                    "column" => $col
                ];
            }
            $arrangedSeats['seats'][] = $arrangedSeatsRow;
        }

        return $arrangedSeats;
    }

    public function arrangePayment(Array $payment): Array
    {
        $response = [];

        if(!empty($payment['Results'])) {
            $authAttributes = $payment['Results']['AuthorizationResult']['@attributes'];

            $response['code']           = $authAttributes['ResponseCode'];
            $response['approval_code']  = $authAttributes['ApprovalCode'];
            $response['supplier_trans_id'] = $authAttributes['SupplierTransID'];
        }

        return $response;
    }

    public function arrangeTicketDocument(Array $ticketDocument): Array
    {
        $arrantedTicketes = [];
        $abbreviateds = [];

        if(empty($ticketDocument['Abbreviated'])) throw new \Exception(getErrorMessage('noTicketDocument'));

        $abbreviateds = isset($ticketDocument['Abbreviated'][0]) ? $ticketDocument['Abbreviated'] : [$ticketDocument['Abbreviated']];
        $arrantedTicketes = array_map(function($abbrv) {
            list(
                'accountingCode' => $accountCode,
                'serialNumber' => $serialNumber,
                'type' => $type
            ) = $abbrv['TicketingDocument']['@attributes'];
            list(
                'FirstName' => $paxFirstName,
                'LastName' => $paxLastName,
                'PassengerType' => $paxType,
            ) = $abbrv['TicketingDocument']['Customer']['Traveler'];

            return [
                'accounting_code' => $accountCode,
                'serial_number' => $serialNumber,
                'type' => $type,
                'pax' => [
                    'first_name' => $paxFirstName,
                    'last_name' => $paxLastName,
                    'type' => $paxType
                ],
                'payment' => [
                    'total' => $abbrv['TicketingDocument']['Payment']['Total']['Amount'],
                    'type' => $abbrv['TicketingDocument']['Payment']['@attributes']['type'],
                    'card' => [
                        'number' => $abbrv['TicketingDocument']['Payment']['Card']['MaskedCardNumber'],
                        'type' => $abbrv['TicketingDocument']['Payment']['Card']['@attributes']['cardType']
                    ]
                ]
            ];
        }, $abbreviateds);

        return $arrantedTicketes;
    }

    public function getTicketingInfo(Array $booking) {
        if(empty($booking['Reservation']['PassengerReservation']['TicketingInfo']) || empty($booking['Reservation']['PassengerReservation']['TicketingInfo']['TicketDetails'])) return [];
        $ticketDetails = isset($booking['Reservation']['PassengerReservation']['TicketingInfo']['TicketDetails'][0])
            ? $booking['Reservation']['PassengerReservation']['TicketingInfo']['TicketDetails']
            : [$booking['Reservation']['PassengerReservation']['TicketingInfo']['TicketDetails']];

        return array_reduce($ticketDetails, function($acc, $ticket) {
            $acc[] = [
                'number' => $ticket['TicketNumber']
            ];

            return $acc;
        }, []);
    }

}
