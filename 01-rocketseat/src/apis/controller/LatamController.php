<?php

namespace Api\Controller;

use \Api\Controller\Controller;
use Api\Helpers\LatamRequestMaker;
use Api\Helpers\LatamResponseMaker;
use \Api\Interfaces\AerialController;
use \Api\Service\LatamService;
use \Api\Helpers\SabreResponseMaker;
use Api\Helpers\LatamUtil;
use Guzzle\Http\Message\Request;

class LatamController extends Controller implements AerialController {
    private $session = null;

    function __construct($credential) {
        $this->credential = $credential;
        $this->service = new LatamService($credential['wsdlUrl'], $credential);
        $this->resMaker = new LatamResponseMaker();
    }

    // public function checkIsLoggedAndLogIn($logonInfo) {
    //     try {
    //         $this->service->findPersonOnSystem($this->getSession($logonInfo));
    //         return $logonInfo;
    //     } catch (\Exception $e) {
    //         if(strpos($e->getMessage(), 'Session token authentication failure.') !== false) return $this->logon($this->getSession($logonInfo));
    //         else throw $e;
    //     }
    // }

    //pax = ancillary

    private function getAncillaryAssignBG(Array $paxs, Array $booking) {
        $paxsBaggagesByTypeReducer = function ($acc, $pax) {

            $labelIndex = '';
            $n = $pax['baggages'];

            //NUMERO DE BAGAGENS
            //1bg - 0C3
            switch ($n) {
                case 1:
                    $labelIndex = LatamRequestMaker::BAG_1_CODE;
                    break;
                case 2:
                    $labelIndex = LatamRequestMaker::BAG_2_CODE;
                    break;
                case 3:
                    $labelIndex = LatamRequestMaker::BAG_3_CODE;
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

        //coloca pax_info dentro de uma array com a identificação do codigo da bagagem
        $paxsBaggagesByType = array_reduce($paxs, $paxsBaggagesByTypeReducer, []);

        $offers = array_map(function ($paxs, $labelIndex) use($booking, $resMaker) {
            $originalAncillaryOffers = [];
            $ancillaryOffers    = [];
            $offerByJourney     = [];
            $arrangingOffer     = [];

            //para cada viagem
            foreach ($booking['journeys'] as $journey) {

                //retorna as ofertas das bagagens
                $originalAncillaryOffers    = $this->service->getAncillaryOffers($this->session, $journey, $paxs, $labelIndex);
                $ancillaryOffers            = $this->resMaker->arrangeAncillaryOffer($originalAncillaryOffers);

                //para cada pessoa
                foreach ($paxs as $pax) {

                    $samePax = array_values(array_filter($booking['paxs'], function($bookedPax) use($pax) {
                        return (strtoupper($bookedPax['first_name']) === strtoupper($pax['first_name'])
                                && strtoupper($bookedPax['last_name']) === strtoupper($pax['last_name']));
                    }));

                    if(count($samePax) === 0) throw new \Exception(getErrorMessage('paxNotFoundOnBooking'));
                    $samePax = $samePax[0];
                    $samePax['baggages'] = $pax['baggages'];

                    $arrangedPaxsInfo[] = $samePax;
                }

                # code...
                $condition['journey']   = $journey;
                $condition['paxs']      = $arrangedPaxsInfo;

                $arrangingOffer['original_offer']    = $originalAncillaryOffers['AncillaryOffers']['Itinerary']['AncillariesByServiceType'];
                $arrangingOffer['offer']             = $ancillaryOffers;
                $arrangingOffer['condition']         = $condition;
                $offerByJourney[] = $arrangingOffer;
            }

            return $offerByJourney;
        }, $paxsBaggagesByType, array_keys($paxsBaggagesByType));

        $updateData['ancillary_offers'] = $offers;

        return $updateData;
    }

    private function addPayment(Array $paymentInfo) {
        list('locator' => $locator) = $paymentInfo;
        $resMaker           = new SabreResponseMaker();
        $paymentResponse    = [];
        $airTickerResponse  = [];

        $this->service->ignoreTransaction($this->session);
        $this->service->travelItineraryRead($this->session, $locator, $this->timeStamp);
        $this->service->contextChangeLLS($this->session, $this->timeStamp);
        $this->service->designatePrinter($this->session, $this->timeStamp);
        $paymentResponse = $this->service->addPayment($this->session, $paymentInfo, $this->timeStamp);
        $paymentResponse = $resMaker->arrangePayment($paymentResponse);

        $airTickerResponse = $this->service->airTicket($this->session, $paymentInfo, $paymentResponse, $this->timeStamp);
        $this->service->passengerDetailsEndTransaction($this->session);
    }

    private function isAncilaryRequestBG($ancillary) {
        return $ancillary['type'] === 'BG';
    }

    public function saveSession($session) {
        $this->session = $session;
    }

    public function logon() {
        $result = $this->service->logon($this->credential);

        $this->session = $result['Security']['BinarySecurityToken'];
        $this->timeStamp = $result['MessageHeader']['MessageData']['Timestamp'];
    }

    public function priceItinerary ($request) {
    }

    public function search(Array $request):Array {
        $resMaker = new SabreResponseMaker();
        $res = [];

        $res['trips'] = [];

        if(!$request['combined_flights']) {
            $res['trips'] = $this->service->search($this->session, $request);

            $res['trips'] = array_map(function($trip) use ($resMaker) {

                $itinerary = isset($trip['PricedItinerary'][0]) ? $trip['PricedItinerary'] : [$trip['PricedItinerary']];

                return $resMaker->arrangeSearch($itinerary);
            }, $res['trips']);

        }else {
            // throw new \Exception(getErrorMessage('feature_not_implemented'));
            $res['trips'] = $this->service->searchCombined($this->session, $request);
            $res['trips'] = $resMaker->arrangeCombinedSearch($res['trips']);
        }

        $this->service->logout($this->session);
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
        $resMaker = new SabreResponseMaker();
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
        }else {
            LatamUtil::throwLatamError($passengerEndResponse);
        }

        $paxsWithBaggage = array_values(array_filter($request['pax_info'], function($pax) {
            return ($pax['baggages'] > 0);
        }));

        if(count($paxsWithBaggage) > 0) {
            $getBookingReq = [];

            $getBookingReq['loc'] = $response['locator'];
            $this->service->ignoreTransaction($this->session);
            $booking = $this->getBooking($getBookingReq);

            $this->getAncillaryAssignBG($paxsWithBaggage, $booking, $response['locator']);
        }

        // die();
        // print_r($passengerEndResponse);die();
        // $this->service->enhancedAirBook();
        // $responseMaker->arrangeBookingInfo([]);die();
        // $response['loc'] = 'DFGVOY';
        return $response;
    }

    // public function addFee($sessionContext, $request) {
    //     return $this->service->addFee($sessionContext, $request);
    // }

    public function divideBooking(Array $request):Array {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function divideAndCancelBooking(Array $request):Array {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function getSeatAvailability(Array $request):Array {
        $resMaker = new SabreResponseMaker();
        $bookingReq = [];
        $booking = [];
        $seatsMaps = [];
        $arrangedSeatsMaps = [];

        $arrangedSeatsMaps['airplanes'] = [];
        $bookingReq['loc'] = $request['loc'];
        $this->service->ignoreTransaction($this->session);
        $booking = $this->getBooking($bookingReq);
        // print_r($booking);die();

        foreach ($booking['journeys'] as $journey) {
            $serviceClass = $journey['fares'][0]['service_class'];
            $seatsMaps = $this->service->enhancedSeatMap($this->session, $request, $serviceClass);

            foreach ($seatsMaps as $seatsMap) {
                $arrangedSeatsMaps['airplanes'][] = $resMaker->arrangeSeatMap($seatsMap);
            }
        }

        return $arrangedSeatsMaps;
    }

    public function seatAssign(Array $request):bool {
        $bookedSegments = [];
        $res = [];

        if(empty($request['segments'])) throw new \Exception(getErrorMessage('incorretJSONStuct', 'trip_info'));

        $booking = $this->getBooking($request);
        foreach ($booking['journeys'] as $journey) {
            $bookedSegments = array_merge($bookedSegments, $journey['segments']);
        }

        // Map booked segment to add sequence index on request body.
        $request['segments'] = array_map(function($segment) use($bookedSegments) {
            // Search same segment on booked segments.
            $sameSegment = array_search($segment['from'], array_column($bookedSegments, 'from'));

            if(empty($sameSegment) && $sameSegment !== 0) throw new \Exception(getErrorMessage('segmentNotBooked'));
            $segment['sequence_index'] = ($sameSegment + 1);

            return $segment;
        }, $request['segments']);

        $res = $this->service->seatAssign($this->session, $request);
        // print_r($res);
        return true;
    }

    // TOCHANGE
    //>>
    public function ancillary(Array $request) {
        $timeStamp = $this->timeStamp;
        $baggagesAnc = array_filter($request['ancillaries'], [$this, 'isAncilaryRequestBG']);
        $bookingReq = [];
        $booking = [];
        $bookingReq['loc'] = $request['loc'];
        $this->service->ignoreTransaction($this->session);
        $booking = $this->getBooking($bookingReq);

          //para cada ancillary
          foreach ($baggagesAnc as $baggageAnc) {
              //retorna com a bagagem na regerva
              $bgOffers = $this->getAncillaryAssignBG($baggageAnc['paxs_info'], $booking);
              $updateRes = $this->service->updateReservation($this->session, $booking, $bgOffers);
          }



          $booking = $this->getBooking($bookingReq);
          $booking['payments'] = array_filter($request['payments']);
          //$_SESSION['check'] = true;
          $payAncillary = $this->service->PaymentRQAncillary($this->session, $booking,$timeStamp);
          $collectMiscFee = $this->service->collectMiscFee($this->session, $booking, $timeStamp, $payAncillary);
          print_r($collectMiscFee);exit;
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
    //>>
    public function getBooking(Array $request):Array {
        $responseMaker = new \Api\Helpers\SabreResponseMaker();
        $booking = $this->service->getBooking($this->session, $request);
        //  if($_SESSION['check']){
        //      $_SESSION['check'] = false;
        //      print_r($booking);exit;
        //   }
        return $responseMaker->arrangeBookingInfo($booking);
    }

    // public function cancelLoc(Array $request):Array {
    //     $responseMaker = new \Api\Helpers\SabreResponseMaker();
    //     $booking = $this->service->getBooking($this->session, $request);

    //     $tickets = $responseMaker->getTicketingInfo($booking);

    //     $this->service->contextChangeLLS($this->session, $this->timeStamp);
    //     $this->service->designatePrinter($this->session, $this->timeStamp);

    //     // array_walk($booking['paxs'], function ($pax) {
    //     //     $grsTickets = $pax['GSR_tickets_number'];

    //     //     array_walk($grsTickets, function ($grs) {
    //     //         $this->service->voidTicket($this->session, $grs, $this->timeStamp);
    //     //     });
    //     // });
    //     array_walk($tickets, function ($ticket) {
    //         $number = $ticket['number'];

    //         $this->service->voidTicket($this->session, $number, $this->timeStamp);
    //     });

    //     $this->service->passengerDetailsEndTransaction($this->session);

    //     return $this->getBooking($request);
    // }

    public function cancelAncillaries(Array $request):Array {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function cancelLoc(Array $request):Array {
        $responseMaker = new \Api\Helpers\SabreResponseMaker();

        $this->service->contextChangeLLS($this->session, $this->timeStamp);
        $this->service->designatePrinter($this->session, $this->timeStamp);

        $booking = $this->service->getBooking($this->session, $request);

        $booking = $responseMaker->arrangeBookingInfo($booking);
        $regDateTime = $booking['reg_datetime'];
        $actualDateTime = date('Y-m-dT23:59:00');

        if(strtotime($regDateTime) < strtotime($actualDateTime)) {
            $tickets = $responseMaker->getTicketingInfo($booking);

            $this->service->cancelIssue($this->session, $this->timeStamp);
            array_walk($tickets, function ($ticket) {
                $number = $ticket['number'];

                $this->service->voidTicket($this->session, $number, $this->timeStamp);
            });
        }else {
            $this->service->cancelIssue($this->session, $this->timeStamp);
        }

        $this->service->passengerDetailsEndTransaction($this->session);

        return $this->getBooking($request);
    }

    public function cancelSSR($request) {
    }

    public function commitAsHold($request) {
    }

    public function clearSession() {
    }

    public function logout(Array $response):bool {
        $this->service->logout($this->session);

        return true;
    }
}