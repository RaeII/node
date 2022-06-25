<?php

namespace Api\Controller;

use \Api\Controller\Controller;
use Api\Helpers\GolRequestMaker;
use Api\Helpers\GolResponseMaker;
use Api\Helpers\SabreRequestMaker;
use Api\Helpers\SabreResponseMaker;
use \Api\Interfaces\AerialController;
use \Api\Service\GolService;

class GolController extends Controller implements AerialController {

    function __construct($credential) {
        $this->credential   = $credential;
        $this->service      = new GolService($credential['wsdlUrl'], ['endpoint' => $credential['endpoint']]);
        $this->resMaker     = new GolResponseMaker();
    }

    public function logon() {
        $result             = $this->service->logon($this->credential);

        $this->session      = $result['Security']['BinarySecurityToken'];
        $this->timeStamp    = $result['MessageHeader']['MessageData']['Timestamp'];
    
    }

    private function getAncillaryAssignBG(Array $paxs, Array $booking) {
        $paxsBaggagesByTypeReducer = function ($acc, $pax) {
            $labelIndex = '';
            $n = $pax['baggages'];

            switch ($n) {
                case 1:
                    $labelIndex = GolRequestMaker::BAG_1_CODE;
                    break;
                case 2:
                    $labelIndex = GolRequestMaker::BAG_2_CODE;
                    break;
                case 3:
                    $labelIndex = GolRequestMaker::BAG_3_CODE;
                    break;
            }
            if(empty($acc[$labelIndex])) $acc[$labelIndex] = [];

            $acc[$labelIndex][] = $pax;

            return $acc;
        };
        $resMaker           = new SabreResponseMaker();
        $paxsBaggagesByType = [];
        $offers             = [];
        $updateData         = [];

        $paxsBaggagesByType = array_reduce($paxs, $paxsBaggagesByTypeReducer, []);

        $offers = array_map(function ($paxs, $labelIndex) use($booking, $resMaker) {
            $originalAncillaryOffers = [];
            $ancillaryOffers    = [];
            $offerByJourney     = [];
            $arrangingOffer     = [];

            foreach ($booking['journeys'] as $journey) {
                $ancillaryDefs              = [];
                $ancillaryRefs              = [];
                $ancillaryExtraInfo         = [];
                $originalAncillaryOffers    = $this->service->getAncillaryOffers($this->session, $journey, $paxs, $labelIndex);

                $ancillaryDefs = array_values(array_filter($originalAncillaryOffers['AncillaryDefinition'], function ($ancillaryDef) {
                    return ($ancillaryDef['SubCode'] === GolRequestMaker::BAG_1_CODE || $ancillaryDef['SubCode'] === GolRequestMaker::BAG_2_CODE || $ancillaryDef['SubCode'] === GolRequestMaker::BAG_3_CODE);
                }));

                $ancillaryRefs = array_reduce($ancillaryDefs, function ($acc, $ancillaryDef) {
                    $acc[] = $ancillaryDef['@attributes']['id'];

                    return $acc;
                }, []);

                $ancillaryExtraInfo = array_reduce($originalAncillaryOffers['Ancillary'], function ($acc, $ancillary) {
                    $id         = $ancillary['@attributes']['ancillaryDefinitionRef'];
                    $acc[$id]   = $ancillary; 

                    return $acc;
                }, []);

                $ancillaryDefs = array_reduce($ancillaryDefs, function ($acc, $ancillaryDef) {
                    $id     = $ancillaryDef['@attributes']['id'];
                    $acc[$id] = $ancillaryDef; 

                    return $acc;
                }, []);

                $originalAncillaryOffers = array_values(array_filter($originalAncillaryOffers['Offers'], function ($offer) use($ancillaryRefs) {
                    return (!empty($offer['@attributes']['offerId']) && in_array(explode('offer_', $offer['@attributes']['offerId'])[1], $ancillaryRefs)); 
                }));


                $originalAncillaryOffers = array_map(function($offer) use($ancillaryDefs, $ancillaryExtraInfo) {
                    $offerId = explode('offer_', $offer['@attributes']['offerId'])[1];

                    $offer['SubCode']           = $ancillaryDefs[$offerId]['SubCode'];
                    $offer['Description1Code']  = $ancillaryDefs[$offerId]['Description1']['Description1Code'];
                    $offer['CommercialName']    = $ancillaryDefs[$offerId]['CommercialName'];
                    $offer['Group']             = $ancillaryDefs[$offerId]['Group'];
                    $offer['Airline']           = $ancillaryDefs[$offerId]['Airline'];
                    $offer['Vendor']            = $ancillaryDefs[$offerId]['Vendor'];
                    $offer['ElectronicMiscDocType'] = $ancillaryDefs[$offerId]['ElectronicMiscDocType'];
                    $offer['ServiceType']       = $ancillaryExtraInfo[$offerId]['ServiceType'];

                    return $offer;
                }, $originalAncillaryOffers);

                $ancillaryOffers = array_map(function($offer) {
                    return $this->resMaker->arrangeAncillaryOffer($offer);
                }, $originalAncillaryOffers);

                foreach ($paxs as $pax) {
                    $samePax = array_values(array_filter($booking['paxs'], function($bookedPax) use($pax) {
                        return (strtoupper($bookedPax['first_name']) === strtoupper($pax['first_name']) 
                                && strtoupper($bookedPax['last_name']) === strtoupper($pax['last_name']));
                    }));
                    $paxOffer           = [];
                    $paxOriginalOffer   = [];
                    $offerIndex         = -1;
                    $ancillarySubCode   = '';

                    if(count($samePax) === 0) throw new \Exception(getErrorMessage('paxNotFoundOnBooking'));
                    $samePax = $samePax[0];
                    $samePax['baggages'] = $pax['baggages'];

                    $arrangedPaxsInfo[] = $samePax;

                    # code...
                    $condition['journey']   = $journey;
                    $condition['paxs'][]    = $samePax;
    
                    $offerIndex = array_search($samePax['baggages'], array_column($ancillaryOffers, 'sub_code'));
    
                    if($offerIndex === 0) throw new \Exception(getErrorMessage('offerNotFound'));

                    $paxOffer = $ancillaryOffers[$offerIndex];

                    $ancillarySubCode   = $paxOffer['sub_code'] === 1 ? GolRequestMaker::BAG_1_CODE : ($paxOffer['sub_code'] === 2 ? GolRequestMaker::BAG_2_CODE : GolRequestMaker::BAG_3_CODE);
                    $offerIndex         = array_search($ancillarySubCode, array_column($originalAncillaryOffers, 'SubCode'));
                    $paxOriginalOffer   = $originalAncillaryOffers[$offerIndex];

                    $arrangingOffer['original_offer']    = $paxOriginalOffer;
                    $arrangingOffer['offer']             = $paxOffer;
                    $arrangingOffer['condition']         = $condition;

                    $offerByJourney[] = $arrangingOffer;
                }
            }

            return $offerByJourney;
        }, $paxsBaggagesByType, array_keys($paxsBaggagesByType));

        $updateData['ancillary_offers'] = $offers;

        return $updateData;
    }
   
    private function isAncilaryRequestBG($ancillary) {
        return $ancillary['type'] === 'BG';
    }

    public function saveSession($session) {

    }

    private function addPayment(Array $paymentInfo) {
        list('locator' => $locator) = $paymentInfo;
        $resMaker           = new SabreResponseMaker();
        $paymentResponse    = [];
        $airTickerResponse  = [];

        $this->service->ignoreTransaction($this->session);
        $this->service->contextChangeLLS($this->session, $this->timeStamp);
        $this->service->designatePrinter($this->session, $this->timeStamp);
        $this->service->getBooking($this->session, ['loc' => $locator]);
        $paymentResponse = $this->service->addPayment($this->session, $paymentInfo, $this->timeStamp);
        $paymentResponse = $resMaker->arrangePayment($paymentResponse);
        $airTickerResponse = $this->service->airTicket($this->session, $paymentInfo, $paymentResponse, $this->timeStamp);

        $this->service->passengerDetailsEndTransaction($this->session);
    }

    public function search(Array $request):Array {
        $resMaker   = new SabreResponseMaker();
        $res        = [];

        $res['trips'] = [];
        if(!$request['combined_flights']) {
            $res['trips'] = $this->service->search($this->session, $request);

            $res['trips'] = array_map(function($trip) {
                $resMaker = new \Api\Helpers\SabreResponseMaker();
                return $resMaker->arrangeSearch($trip['PricedItinerary']);
            }, $res['trips']);
        }else {
            $res['trips'] = $this->service->searchCombined($this->session, $request);
            $res['trips'] = $resMaker->arrangeCombinedSearch($res['trips']);
        }
        // $this->service->logout($this->session);

        return $res;
    }

    public function confirmBooking(Array $request, Int $credentialId):Array {

        if(!empty($request['payments'])) {
            $booking = $this->getBooking($request);
            $segments = [];

            $segments = array_reduce($booking['journeys'], function ($acc, $joruney) {
                $acc = array_merge($acc, $joruney['segments']);

                return $acc;
            }, []);

            $paymentInfo['locator']  = $booking['locator'];
            $paymentInfo['segments'] = $segments;
            $paymentInfo['paxs']     = $booking['paxs'];
            $paymentInfo['payments'] = $request['payments'];

            $this->addPayment($paymentInfo);
        }

        return $this->getBooking($request);
    }

    public function book(Array $request):Array {
        $userAccService = new \Service\UserAccountService();
        $user = (int)$userAccService->fetchById(self::$JWT->getId());
        $passengerEndResponse = [];
        $response = [];
        $paxsWithBaggage = [];

        $this->service->enhancedAirBook($this->session, $request);
        $this->service->passengerDetailsPaxs($this->session, $request, $user);
        $passengerEndResponse = $this->service->passengerDetailsEndTransaction($this->session);

        if(isset($passengerEndResponse['ItineraryRef'])) {
            $response['locator'] = $passengerEndResponse['ItineraryRef']['@attributes']['ID'];
        }

        $this->service->ignoreTransaction($this->session);
        // else {
        //     LatamUtil::throwLatamError($passengerEndResponse);
        // }

        // $paxsWithBaggage = array_values(array_filter($request['pax_info'], function($pax) {
        //     return (count($pax['baggages']) > 0);
        // }));

        // if(count($paxsWithBaggage) > 0) {
        //     $this->service->ignoreTransaction($this->session);
            
        // }
        // print_r($passengerEndResponse);die();
        // $this->service->enhancedAirBook();
        // $responseMaker->arrangeBookingInfo([]);die();
        // $response['locator'] = 'WTDCBU';
        // $response['locator'] = $passengerEndResponse['locator'];
        return $response;
    }

    public function divideBooking(Array $request):Array {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function ancillary(Array $request) {
        $baggagesAnc = array_filter($request['ancillaries'], [$this, 'isAncilaryRequestBG']);
        $bookingReq = [];
        $booking = [];

        $bookingReq['loc'] = $request['loc'];
        $this->service->ignoreTransaction($this->session);
        $booking = $this->getBooking($bookingReq);

        foreach ($baggagesAnc as $baggageAnc) {
            $bgOffers = $this->getAncillaryAssignBG($baggageAnc['paxs_info'], $booking);
            $updateRes = $this->service->updateReservation($this->session, $booking, $bgOffers);
        }

        $this->service->passengerDetailsEndTransaction($this->session);
    }

    public function ancillaryPrice(Array $request) {
        $baggagesAnc    = array_filter($request['ancillaries'], [$this, 'isAncilaryRequestBG']);
        $booking        = [];
        $bgOffers       = [];
        $offersByBGType = [];
        $offers         = [];

        $this->service->ignoreTransaction($this->session);
        if(!empty($request['loc'])) {
            $booking = $this->getBooking($request);
        }

        foreach ($baggagesAnc as $baggageAnc) {
            if(empty($booking)) $booking = $baggageAnc;
            $offer = [];

            $offersByBGType = $this->getAncillaryAssignBG($baggageAnc['paxs'], $booking);
            // $offersByBGType = $this->getAncillaryAssignBG($paxs, $booking);
            $offer = array_map(function ($journeyOffer) {
                return array_map(function($offerToArrange) {
                    unset($offerToArrange['original_offer']);
    
                    return $offerToArrange;
                }, $journeyOffer);
            }, $offersByBGType['ancillary_offers']);

            $offers = array_merge($offers, $offer);
        }

        return $offers;
    }

    public function divideAndCancelBooking(Array $request):Array {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function getSeatAvailability(Array $request):Array {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function seatAssign(Array $request):bool {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function getBooking(Array $request):Array {
        $responseMaker = new \Api\Helpers\SabreResponseMaker();
        $booking = $this->service->getBooking($this->session, $request);

        return $responseMaker->arrangeBookingInfo($booking);
    }

    /**
     * @param array $booking            arranged getBooking response.
     * @param array $ticketsDocumnet    arranged ticketsDocument response.
     */
    private function _cancelAncillaries(Array $booking, Array $ticketsDocument) {
        $processAERRQS = null;

        $processAERRQS = function ($document) use($booking) {
            $filterSamePax = function($pax) use($document) {
                return ($pax['first_name'] === $document['pax']['first_name'] &&
                $pax['last_name'] === $document['pax']['last_name']);
            };

            $samePax = array_values(array_filter($booking['paxs'], $filterSamePax))[0];

            $document['pax']['id_cia'] = $samePax['id_cia'];
            $document['transaction_action'] = 'refund';
            $this->service->aerTicket($this->session, $document);

            $document['transaction_action'] = 'ticket_retained';
            $this->service->aerTicket($this->session, $document);
        };

        $documentsTypeTicket = array_filter($ticketsDocument, function ($ticketDocument) {
            return $ticketDocument['type'] === 'TKT';
        });
        $documentsTypeEMD = array_filter($ticketsDocument, function ($ticketDocument) {
            return $ticketDocument['type'] === 'EMD';
        });

        if(!empty($documentsTypeTicket)) {
            array_walk($documentsTypeTicket, $processAERRQS);
        }
        $this->service->passengerDetailsEndTransaction($this->session);


        if(!empty($documentsTypeEMD)) {
            array_walk($documentsTypeEMD, $processAERRQS);
        }
        $this->service->passengerDetailsEndTransaction($this->session);
    }

    /**
     * @param array $request Request payload.
     */
    public function cancelAncillaries(Array $request):Array {
        $responseMaker      = null; 
        $booking            = [];
        $arrangedBooking    = [];
        $ticketsDocument    = [];
        $arrangedTicketsDocument = [];

        $responseMaker      = new \Api\Helpers\SabreResponseMaker();
        $booking            = $this->service->getBooking($this->session, $request);
        $arrangedBooking    = $responseMaker->arrangeBookingInfo($booking);

        $ticketsToUpdate = array_reduce($arrangedBooking['paxs'], function ($acc, $pax) use($request) {
            $aux = array_reduce($pax['fees'], function ($accFees, $fee) use($request) {
                if(!empty($request['tickets_number'])) {
                    if(array_search($fee['emd_number'], $request['tickets_number']) !== false) $accFees[] = $fee;
                } else {
                    $accFees[] = $fee;
                }

                return $accFees;
            }, []);
            $acc = array_merge($acc, $aux);

            return $acc;
        }, []);

        $this->service->updateReservation($this->session, $arrangedBooking, ['delete_ancillaries' => $ticketsToUpdate]);
        $this->service->passengerDetailsEndTransaction($this->session);

        $booking            = $this->service->getBooking($this->session, $request);
        $arrangedBooking    = $responseMaker->arrangeBookingInfo($booking);

        $data = [
            'locator'       => $request['loc'],
            'ticket_number' => '',
            'issue_date'    => explode('T', $arrangedBooking['reg_datetime'])[0]
        ];

        if(!empty($request['tickets_number'])) {
            array_walk($request['tickets_number'], function ($ticketNumber) use($data, $arrangedBooking, $responseMaker) {
                $data['ticket_number'] = $ticketNumber;

                $ticketsDocument            = $this->service->ticketingDocument($this->session, $data);
                $arrangedTicketsDocument    = $responseMaker->arrangeTicketDocument($ticketsDocument);
                $this->_cancelAncillaries($arrangedBooking, $arrangedTicketsDocument);
            });
        }else {
            $ticketsDocument            = $this->service->ticketingDocument($this->session, $data);
            $arrangedTicketsDocument    = $responseMaker->arrangeTicketDocument($ticketsDocument);
            $this->_cancelAncillaries($arrangedBooking, $arrangedTicketsDocument);
        }

        return [];
    }

    public function cancelLoc(Array $request):Array {
        $responseMaker = new \Api\Helpers\SabreResponseMaker();
        $ticketsDocument = [];
        $arrangedTicketsDocument = [];

        $this->service->contextChangeLLS($this->session, $this->timeStamp);
        $this->service->designatePrinter($this->session, $this->timeStamp);
        
        $booking = $this->service->getBooking($this->session, $request);

        $arrangedBooking    = $responseMaker->arrangeBookingInfo($booking);
        $regDateTime        = date($arrangedBooking['reg_datetime']);
        $actualDateTime     = date('Y-m-dT23:59:00');

        $data = [
            'locator'       => $arrangedBooking['locator'],
            'issue_date'    => explode('T', $arrangedBooking['reg_datetime'])[0]
        ];
        $ticketsDocument            = $this->service->ticketingDocument($this->session, $data);
        $arrangedTicketsDocument    = $responseMaker->arrangeTicketDocument($ticketsDocument);
        
        if(strtotime($regDateTime) <= strtotime($actualDateTime) && count($arrangedTicketsDocument) === 0) {
            $this->service->cancelIssue($this->session, $this->timeStamp);
            $tickets = $responseMaker->getTicketingInfo($booking);

            array_walk($tickets, function ($ticket) {
                $number = $ticket['number'];

                $this->service->voidTicket($this->session, $number, $this->timeStamp);
            });
        }else {
            $this->service->cancelIssue($this->session, $this->timeStamp);
        }
        $this->service->passengerDetailsEndTransaction($this->session);

        $this->service->ignoreTransaction($this->session);        
        $booking = $this->service->getBooking($this->session, $request);
        $this->_cancelAncillaries($arrangedBooking, $arrangedTicketsDocument);
        
        $this->service->ignoreTransaction($this->session);        
        // print_r($arrangedTicketsDocument);
        return $this->getBooking($request);
    }

    public function clearSession() {

    }

    public function logout(Array $response):bool {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

}