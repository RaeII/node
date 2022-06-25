<?php

namespace Service;

use \DataBase\BookingLog;

class BookingLogService extends Service {

    public function __construct() {
        $this->dataBase = new BookingLog();
    }

    /*
        @param $booking Result of getBooking arranged.
    */
    public function create(Array $booking, Array $client) {
        $bookedToLog = [];

        /* 
            @return Sum taxs values from all paxs.
        */
        $paxExtraTaxsValueReducer = function ($carry, $paxTax) {
            // return bcadd($carry, $paxTax['amount']);
            return $carry += $paxTax['amount'];
        };
        /* 
            @return Total amount to pay, suming all paxs taxes, journey tariff and taxes.
        */
        $totalAmountReducer = function ($carry, $value) {
            // return bcadd($carry, $value['total_cost']);
            return $carry += $value['total_cost'];
        };
        /* 
            @return All routes concated.
        */
        $routesReducer = function ($carry, $routeInfo) {
            if($carry != '') {
                return $carry .= " - " . $routeInfo['from'] . " - " . $routeInfo['to'];
            }else {
                return $routeInfo['from'] . " - " . $routeInfo['to'];
            }
        };
        /* 
            @return ADTs.
        */
        $paxADTFilter = function ($segmentTax) {
            return $segmentTax['fare_type'] === 'ADT';
        };
        /* 
            @return CHDs.
        */
        $paxCHDFilter = function ($segmentTax) {
            return $segmentTax['fare_type'] === 'CHD';
        };
        /* 
            @return INFs.
        */
        $paxINFFilter = function ($segmentTax) {
            return $segmentTax['fare_type'] === 'INF';
        };
        /* 
            @return INFs.
        */
        $paxOtherFilter = function ($segmentTax) {
            return $segmentTax['fare_type'] !== 'INF' && $segmentTax['fare_type'] !== 'CHD' && $segmentTax['fare_type'] !== 'ADT';
        };
        /* 
            @return Sum tariff values.
        */
        $segmentTariffValueReducer = function ($carry, $segmentTax) {
            // return bcadd($carry, $segmentTax['amount']);
            return $carry += $segmentTax['amount'];
        };
        /* 
            @return Sum taxs values from all segments taxes.
        */
        $segmentTaxsValueReducer = function ($carry, $paxsFares) {
            return $carry += array_reduce($paxsFares['taxes'], function ($carry, $tax) { 
                if(!str_contains($tax['type'], 'Disconto')) {
                    return $carry += $tax['total']; 
                }
                else {
                    return $carry;
                }
            });
        };
        /* 
            @return Sum promotion discount values from all segments taxes.
        */
        $segmentPomotionValueReducer = function ($carry, $paxsFares) {
            // return bcadd($carry, $segmentTax['promotional']);
            return !empty($paxsFares['promotional']) ? $carry += $paxsFares['promotional'] : $carry;
        };

        $bookedToLog['locator'] = $booking['locator'];
        $bookedToLog['comp_code'] = $booking['company_code'];
        $bookedToLog['full_trip_route'] = '';
        $bookedToLog['pax_qtt'] = count($booking['paxs']);
        $bookedToLog['total_paxs_taxs'] = 0;
        $bookedToLog['total_journeys_taxs'] = 0;
        $bookedToLog['total_tariff'] = 0;
        $bookedToLog['total_amount'] = 0;
        $bookedToLog['promotional_discount'] = 0;
        $bookedToLog['alteration_date'] = date('y/m/d H:i:s');
        $bookedToLog['reg_date'] = date('y/m/d H:i:s');
        $bookedToLog['status'] = $booking['status'];
        $bookedToLog['credential_id'] = $booking['credential_id'];
        $bookedToLog['user_account_id'] = $booking['user_account_id'];
        $bookedToLog['total_amount'] = array_reduce($booking['paxs'], $totalAmountReducer, 0);

        $adtsQtd = array_filter($booking['paxs'], function ($pax) {
            return $pax['type'] === 'ADT';
        });

        $chdsQtd = array_filter($booking['paxs'], function ($pax) {
            return $pax['type'] === 'CHD';
        });

        $infsQtd = array_filter($booking['paxs'], function ($pax) {
            return $pax['type'] === 'INF';
        });

        for ($index = 0; $index < count($booking['journeys']); $index++) { 
            $journey = $booking['journeys'][$index];
            $fare = $journey['fares'][0];
            // print_r($journey);
            $adts =                                     array_filter($fare['paxs_fare'], $paxADTFilter);
            $chds =                                     array_filter($fare['paxs_fare'], $paxCHDFilter);
            $infs =                                     array_filter($fare['paxs_fare'], $paxINFFilter);
            $other =                                    array_filter($fare['paxs_fare'], $paxOtherFilter);

            $bookedToLog['total_journeys_taxs']     +=  ( array_reduce($adts, $segmentTaxsValueReducer, 0) * count($adtsQtd)
                                                        + array_reduce($chds, $segmentTaxsValueReducer, 0) * count($chdsQtd)
                                                        + array_reduce($infs, $segmentTaxsValueReducer, 0) * count($infsQtd)
                                                        + array_reduce($other, $segmentTaxsValueReducer, 0));

            $bookedToLog['total_tariff']            +=  ( array_reduce($adts, $segmentTariffValueReducer, 0) * count($adtsQtd)
                                                        + array_reduce($chds, $segmentTariffValueReducer, 0) * count($chdsQtd)
                                                        + array_reduce($infs, $segmentTariffValueReducer, 0) * count($infsQtd)
                                                        + array_reduce($other, $segmentTariffValueReducer, 0));

            $bookedToLog['promotional_discount']    +=  array_reduce($fare['paxs_fare'], $segmentPomotionValueReducer, 0) * count($booking['paxs']);
            $bookedToLog['full_trip_route']         .=  array_reduce($journey["segments"], $routesReducer, '');
            
            // $bookedToLog['segments']            = array_reduce($journey["segments"], $segmentReducer, $bookedToLog['segments']);
            if($index < count($booking['journeys']) - 1)
                $bookedToLog['full_trip_route'] .= ' | ';
        }

        foreach ($booking['paxs'] as $pax) {
            $bookedToLog['total_paxs_taxs'] += array_reduce($pax['fees'], $paxExtraTaxsValueReducer, 0);
        }
        $totalTaxs = $bookedToLog['total_journeys_taxs'] + $bookedToLog['total_paxs_taxs'] - $bookedToLog['promotional_discount'];
        $bookedToLog['journeys'] = $booking['journeys'];

        // echo "##################### " . $bookedToLog['total_amount'] . " ################# " . $bookedToLog['total_tariff'] + $totalTaxs;die();
        // Case sum is different from sum provided by webservice. 
        $errorSumming = $bookedToLog['total_amount'] != bcadd($bookedToLog['total_tariff'], $totalTaxs, 2) ? 1 : 0;

        return $this->dataBase->create($bookedToLog, $errorSumming);
    }

    public function fetchByLoc(String $loc, Int $userId = null) {
        return $this->dataBase->fetchByLoc($loc, $userId);
    }

    public function updateStatus(Array $data) {
        return $this->dataBase->updateStatus($data['locator'], $data['status']);
    }
}