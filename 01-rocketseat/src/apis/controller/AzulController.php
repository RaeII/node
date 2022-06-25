<?php

namespace Api\Controller;

use \Api\Controller\Controller;
use \Api\Interfaces\AerialController;
use \Api\Service\AzulService;

class AzulController extends Controller implements AerialController {
    private $session = null;

    function __construct(Array $credential) {
        $this->credential = $credential;
        $this->service = new AzulService($credential['wsdlUrl']);
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
    public function populate() {
        $this->service->populate();
    }

    public function saveSession($session) {
        $this->session = $session;
    }

    public function logon() {
        $session = $this->service->logon($this->credential);
        $this->session = $session['LogonResult']['SessionContext'];

        // echo base64_encode(json_encode($session['LogonResult']['SessionContext']));
        // echo '####### Token ----> ';print_r($this->session['SecureToken']);
    }

    public function priceItinerary ($request) {
        $this->service->priceItinerary($this->session, $request);
    }

    public function search(Array $request):Array {
        $responseMaker = new \Api\Helpers\AzulResponseMaker();
        $serviceResponse = [];
        $response = [];
        $arrangedSearch = [];

        $response['trips'] = [];
        if(!$request['combined_flights']) {
            $serviceResponse = $this->service->search($this->session, $request);

            foreach ($serviceResponse as $trip) {
                $response['trips'][] = $responseMaker->arrangeSearch($trip);
            }
        }else {
            $serviceResponse = $this->service->searchCombined($this->session, $request);
            $arrangedSearch = $responseMaker->arrangeSearchCombined($serviceResponse);

            foreach ($request['trip_info'] as $tripReq) {
                $response['trips'][] = array_filter($arrangedSearch, function($journey) use ($tripReq){
                    return ($tripReq['from'] === $journey['overall']['from']);
                });
            }
        }

        return $response;
    }

    public function confirmBooking(Array $request, Int $credentialId):Array {
        // $credentialsDb = new \DataBase\ApiService();
        $response = [];
        $paymentFormService = new \Service\PaymentFormService();

        foreach ($request['payments'] as &$payment) {
            if(!isset($payment['acc_number'])) {
                $paymentForm = $paymentFormService->fetchByServiceCredential($credentialId, $payment['type']);

                if(count($paymentForm) <= 0) throw new \Exception(getErrorMessage('paymentTypeNotReg', $payment['type']));
                $payment = array_merge($payment, $paymentForm[0]);
            }
        }
        unset($payment);
        $this->service->confirmBooking($this->session, $request);

        return $this->getBooking($request);
        // $paymentForms

        // $credential = $credentialsDb->fetch($request['code']);
        // $lgName = preg_replace('/\D*/', '', $credential['loginName']);
        // return $this->service->confirmBooking($this->session, $request, $lgName);
    }

    public function book(Array $request):Array {
        $userAccService = new \Service\UserAccountService();
        $aerialUtil = new \Api\Helpers\AerialUtil;
        $response = [];
        $isInternational = false;
        $paxsWithBags = array_filter($request['pax_info'], function ($pax){
            return (isset($pax['baggages']) && $pax['baggages'] > 0);
        });

        $user = $userAccService->fetchById(self::$JWT->getId());
        $this->clearSession();
        try {
            $isInternational = $aerialUtil->isTripInternational($request['trip_info'][0]['segments']);
            $this->service->book($this->session, $request);
            $this->service->operateSSRInf($this->session, $request, 'sell');

            foreach ($request['trip_info'] as $segments) {
                $this->service->operateSSRBag($this->session, $segments['segments'], $paxsWithBags, 'sell', $isInternational);
            }
            // $this->service->seatAssign($this->session, $request);

            $response = $this->commitAsHold($request, $user);
        } catch (\Exception $e) {
            $this->clearSession();
            throw $e;
        }
        return $response;
    }

    // public function addFee($sessionContext, $request) {
    //     return $this->service->addFee($sessionContext, $request);
    // }

    public function ancillary(Array $request) {
        $aerialUtil = new \Api\Helpers\AerialUtil;
        $response = [];
        $booking = [];
        $isInternational = false;

        $booking = $this->service->getBooking($this->session, $request)['GetBookingResult'];
        foreach ($request['ancillaries'] as $ancillary) {
            $isInternational = $aerialUtil->isTripInternational($ancillary['segments']);

            switch ($ancillary['type']) {
                case 'BG':
                    $this->service->operateSSRBag($this->session, $ancillary['segments'], $ancillary['paxs_info'], 'sell', $isInternational);
                    break;
                default:
                    throw new \Exception(getErrorMessage('ancillaryTypeNotFound'));
            }
        }
        $response = $this->service->commitUpdate($this->session, $request['loc'], $booking);

        return $response;
    }

    public function divideBooking(Array $request):Array {
        $responseMaker = new \Api\Helpers\AzulResponseMaker();

        $this->getBooking($request);
        $response = $this->service->divideBooking($this->session, $request);
        $response = $responseMaker->arrangeDivideBooking($response);
        return $response;
    }

    public function divideAndCancelBooking(Array $request):Array {
        $this->getBooking($request);
        $this->service->divideAndCancelBooking($this->session, $request);
        return $this->getBooking($request);
    }

    public function getSeatAvailability(Array $request):Array {
        $responseMaker = new \Api\Helpers\AzulResponseMaker();

        // $booking = $this->service->getBooking($sessionContext, $request);
        // return $this->service->getSeatAvailability($sessionContext, $booking["GetBookingResult"]);
        // return $this->service->getSeatAvailability($sessionContext, $request);
        $response = $this->service->getSeatAvailability($this->session, $request);
        $response = $responseMaker->arrangeSeatAviability($response);
        return $response;
    }

    public function seatAssign(Array $request):bool {
        $booking = $this->getBooking($request);
        $this->service->seatAssign($this->session, $request);

        return true;
    }

    public function getBooking(Array $request):Array {
        $responseMaker = new \Api\Helpers\AzulResponseMaker();

        $response = $this->service->getBooking($this->session, $request);
        if(count($response) <= 0) return [];
        $response = $responseMaker->arrangeBookingInfo($response);

        // if(isset($response['journeys'])) {
        //     $response['journeys'] = $this->applyMarkups($response['journeys'], self::$JWT->getCompanyId());
        //     $response = $responseMaker->updatePaxTotalValue($response);
        // }

        return $response;
    }

    public function cancelAncillaries(Array $request):Array {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function cancelLoc(Array $request):Array {
        $responseMaker = new \Api\Helpers\AzulResponseMaker();
        $response = $this->service->cancelLoc($this->session, $request);

        $booking = $this->getBooking($request);
        return $booking;
    }

    public function cancelSSR($request) {
        $response = [];
        array_push($response, $this->service->operateSSRInf($this->session, $request, 'cancel'));
        array_push($response, $this->service->operateSSRBag($this->session, $request, 'cancel'));
        return $response;
    }

    public function ancillaryPrice(Array $request) {
        throw new \Exception(getErrorMessage('feature_not_implemented'));
    }

    public function commitAsHold($request) {
        $userAccService = new \Service\UserAccountService();
        $response = [];
        $arrangedRes = [];

        $response = $this->service->commitAsHold($this->session, $request);
        $arrangedRes['locator'] = $response['CommitResult']['RecordLocator'];
        return $arrangedRes;
    }

    public function clearSession() {
        $this->service->clearSession($this->session);
    }

    public function logout(Array $response):bool {
        $this->service->logout($response);

        return true;
    }
}