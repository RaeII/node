<?php
namespace Api\Service;

use \Util\Formatter;
use \Api\Helpers\LatamRequestMaker;
use \Api\Helpers\LatamUtil;
use Api\Helpers\SabreUtil;

class LatamService extends \Api\Service\Service {
    //>>
    function __construct($wsdlUrl, $config) {
        $this->reqMaker = new LatamRequestMaker('LA');
        $this->config = $config;
        $this->actualSSRNumber = 0;

        $wsConfig = [];

        // Login info
        $wsConfig['org'] = 'AOT';
        $wsConfig['domain'] = 'LA';
        // Header info
        $wsConfig['CPAId'] = '';
        $wsConfig['fromPartyId'] = 'URLofAppsclient@yourDomain.com';
        $wsConfig['toPartyId'] = '';
        $wsConfig['conversationId'] = 'LA';
        // Body ini info
        $wsConfig['pcc'] = 'LA';

        $this->wsConfig = $wsConfig;
        $this->sabreUtil = new SabreUtil();
    }

    private function commitUpdate($session, $loc, $pnr) {
    }

    public function commitAsHold($session, $requestInfo, $user) {
    }

    // ################### Secundary methods ###################

    public function travelItineraryRead(String $session, String $locator, String $timeStamp) {
        $wsConfig = $this->wsConfig;
        $req = '';
        $res = '';

        $wsConfig['session']    = $session;
        $wsConfig['timeStamp']  = $timeStamp;
        $wsConfig['endpoint']   = $this->config['endpoint'];

        $req = $this->reqMaker->makeTravelItineraryRead($locator, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'TravelItineraryReadRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['TravelItineraryReadRS'];
    }

    public function contextChangeLLS(String $session, String $timeStamp) {
        $wsConfig = $this->wsConfig;
        $data = [];
        $req = '';
        $res = '';

        $wsConfig['session']    = $session;
        $wsConfig['timeStamp']  = $timeStamp;
        $wsConfig['endpoint']   = $this->config['endpoint'];

        $data['changeDutyCode'] = 4;
        $req = $this->reqMaker->makeContextChangeLLR($data, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'ContextChangeRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['ContextChangeRS'];
    }

    public function designatePrinter(String $session, String $timeStamp) {
        $wsConfig = $this->wsConfig;
        $data = [];
        $req = '';
        $res = '';

        $wsConfig['session']    = $session;
        $wsConfig['timeStamp']  = $timeStamp;
        $wsConfig['endpoint']   = $this->config['endpoint'];

        $data['countryCode'] = '2A';
        $req = $this->reqMaker->makeDesignatePrinter($data, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'ContextChangeRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['DesignatePrinterRS'];
    }

    // ######################################################

    public function logon(Array $credential) {
        $config = [];

        if($credential['endpoint'] === null || strlen($credential['endpoint']) === 0) throw new \Exception(getErrorMessage('missingCredentialInformation'));

        $wsConfig = $this->wsConfig;
        $wsConfig['toPartyId'] = !empty($credential['endpoint']) ? $credential['endpoint'] : '';

        $this->soapCli = new \SoapClient('src/apis/wsdl/SessionCreateRQ/SessionCreateRQ_1.wsdl', $options = array(
            'trace' => 1,
            'exceptions' => 1,
            'encoding' => 'UTF-8'
            )
        );

        $req = $this->reqMaker->makeLogon($credential, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $credential['endpoint'], 'SessionCreateRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res, 'Header');
    }

    public function search($session, $requestInfo) {
        $res = [];
        $wsConfig = [];
        $extraMisc = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session']        =  $session;
        $wsConfig['pcc']            = 'BEL';
        $wsConfig['personalcc']     = 'BEL';
        $wsConfig['accoutingCode']  =  !empty($this->config['accounting_code']) ? $this->config['accounting_code'] : '0G';
        $wsConfig['requestType']    = 'LABRD';
        $wsConfig['serviceTag']     = 'LA';
        $wsConfig['officeCode']     = !empty($this->config['office_code']) ? $this->config['office_code'] : '9975326';
        $wsConfig['defaultTicketingCarrier'] = 'JJ';

        if(!empty($requestInfo['promo_code'])) $extraMisc['promo_code'] = $requestInfo['promo_code'];
        $req = $this->reqMaker->makeSearch($requestInfo['trip_info'][0], $requestInfo['pax_info'], $wsConfig, $extraMisc);


        $soapRes = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AdvancedAirShoppingRQ', '1.1');//????????????????????????????

        $error = $this->sabreUtil->getErrorFromResponse($soapRes);
        if(strlen($error) > 0) throw new \Exception($error);

        $soapRes = Formatter::soapToArray($soapRes);

        if(!empty($soapRes['OTA_AirLowFareSearchRS']['Errors'])) LatamUtil::throwLatamError($soapRes['OTA_AirLowFareSearchRS']);

        $res[] = $soapRes['OTA_AirLowFareSearchRS']['PricedItineraries'];

        if(!empty($requestInfo['trip_info'][0]['back_date'])) {
            $aux = $requestInfo['trip_info'][0];

            $aux['from'] = $requestInfo['trip_info'][0]['to'];
            $aux['to'] = $requestInfo['trip_info'][0]['from'];
            $aux['dep_date'] = $requestInfo['trip_info'][0]['back_date'];

            $req = $this->reqMaker->makeSearch($aux, $requestInfo['pax_info'], $wsConfig, $extraMisc);
            $soapRes = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AdvancedAirShoppingRQ', '1.1');

            $error = $this->sabreUtil->getErrorFromResponse($soapRes);
            if(strlen($error) > 0) throw new \Exception($error);

            $res[] = Formatter::soapToArray($soapRes)['OTA_AirLowFareSearchRS']['PricedItineraries'];
        }

        return $res;
    }

    public function searchCombined($session, $requestInfo) {
        // throw new \Exception(getErrorMessage('feature_not_implemented'));
        $res        = [];
        $wsConfig   = [];
        $extraMisc  = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session']        =  $session;
        $wsConfig['pcc']            = 'BEL';
        $wsConfig['personalcc']     = 'BEL';
        $wsConfig['accoutingCode']  = !empty($this->config['accounting_code']) ? $this->config['accounting_code'] : '0G';
        $wsConfig['requestType']    = 'LABRD';
        $wsConfig['serviceTag']     = 'LA';
        $wsConfig['officeCode']     = !empty($this->config['office_code']) ? $this->config['office_code'] : '9975326';
        $wsConfig['defaultTicketingCarrier'] = 'JJ';

        if(!empty($requestInfo['promo_code'])) $extraMisc['promo_code'] = $requestInfo['promo_code'];

        $req = $this->reqMaker->searchCombined($requestInfo, $wsConfig, $extraMisc);
        $soapRes = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AdvancedAirShoppingRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($soapRes);
        if(strlen($error) > 0) throw new \Exception($error);

        $res[] = Formatter::soapToArray($soapRes)['OTA_AirLowFareSearchRS']['PricedItineraries'];

        return $res;
    }

    public function enhancedSeatMap(String $session, Array $requestInfo, String $serviceClass):Array {
        $res = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['compCode'] = 'LA';
        $wsConfig['cityCode'] = 'BEL';

        $res = array_map(function($segment) use($serviceClass, $wsConfig):Array {
            $res = '';
            $req = '';

            $req = $this->reqMaker->makeEnhancedSeatMap($segment, $serviceClass, $wsConfig);
            $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'EnhancedSeatMapRS', '1.1');

            $error = $this->sabreUtil->getErrorFromResponse($res);
            if(strlen($error) > 0) throw new \Exception($error);

            return Formatter::soapToArray($res)['EnhancedSeatMapRS'];
        }, $requestInfo['segments']);

        return $res;
    }

    public function seatAssign($session, $seatsAssignsInfo) {
        $wsConfig = [];
        $req = '';
        $res = '';

        $wsConfig               = $this->wsConfig;
        $wsConfig['session']    = $session;
        $wsConfig['endpoint']   = $this->config['endpoint'];

        $req = $this->reqMaker->makePassengerDetailsSeatAssign($seatsAssignsInfo, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AirSeatRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        $res = Formatter::soapToArray($res);

        return $res;
    }

    public function enhancedAirBook($session, $req) {
        $wsConfig = '';
        $paxs = array_reduce($req['pax_info'], function ($acc, $pax) {
            if($pax['type'] === 'ADT') $acc['adults'] += 1;
            else if($pax['type'] === 'CHD') $acc['childs'] += 1;
            else if($pax['type'] === 'INF') $acc['infs'] += 1;

            return $acc;
        }, ['adults' => 0, 'childs' => 0, 'infs' => 0]);

        $wsConfig               = $this->wsConfig;
        $wsConfig['session']    = $session;
        $wsConfig['endpoint']   = $this->config['endpoint'];

        $req = $this->reqMaker->makeEnhancedAirBook($req, $paxs, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'EnhancedAirBookRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        $res = Formatter::soapToArray($res);

        if(empty($res['EnhancedAirBookRS'])) throw new \Exception(getErrorMessage('enhancedAirBookNoResponse'));
        if(!empty($res['EnhancedAirBookRS']['ApplicationResults']['Error'])) LatamUtil::throwLatamError($res['EnhancedAirBookRS']);

        return $res['EnhancedAirBookRS'];
    }

    public function passengerDetailsPaxs($session, $request, $user) {
        $req = '';
        $wsConfig = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makePassengerDetailsPaxs($request, $user, $wsConfig);
        $soapRes =  $this->soapCli->__doRequest($req, $this->config['endpoint'], 'PassengerDetailsRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($soapRes);
        if(strlen($error) > 0) throw new \Exception($error);
    }

    public function passengerDetailsEndTransaction($session) {
        $req = '';
        $arrangedRes = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makePassengerDetailsEndTransaction($wsConfig);
        $soapRes =  $this->soapCli->__doRequest($req, $this->config['endpoint'], 'PassengerDetailsRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($soapRes);
        if(strlen($error) > 0) throw new \Exception($error);

        $arrangedRes = Formatter::soapToArray($soapRes);

        if(!isset($arrangedRes['PassengerDetailsRS']) && isset($arrangedRes['Fault'])) throw new \Exception(getErrorMessage('wsError', $arrangedRes['Fault']['faultstring']));
        else if(!isset($arrangedRes['PassengerDetailsRS']) && !isset($arrangedRes['Fault'])) throw new \Exception(getErrorMessage('wsUnrecognizedResponse'));

        return $arrangedRes['PassengerDetailsRS'];
    }

    public function designatePrinterLLS($session, $requestInfo) {
        $req = '';

        $req = $this->reqMaker->makePassengerDetailsEndTransaction($session, $this->config['endpoint']);
        $soapRes = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'DesignatePrinterRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($soapRes);
        if(strlen($error) > 0) throw new \Exception($error);

        return true;
    }

    public function book($session, $requestInfo) {
    }

    private function getSellSSR($segment, $indexPax, $typeSSR, $numberSSR) {
    }

    public function operateSSRInf($session, $requestInfo, $ssrRequestType){
    }

    public function operateSSRBag($session, $requestInfo, $ssrRequestType){
    }

    public function confirmBooking($session, $requestInfo, $accNum) {
    }

    //>>
    public function addPayment(String $session, Array $paymentInfo, String $timeStamp) {
        $wsConfig = $this->wsConfig;
        $req = '';
        $res = '';

        $wsConfig['session']    = $session;
        $wsConfig['endpoint']   = $this->config['endpoint'];
        $wsConfig['timestamp']  = $timeStamp;
        $wsConfig['stationNr']  = 99768594;
        $wsConfig['channelId']  = 'GSA';
        $wsConfig['merchantId'] = 'LA';

        $req = $this->reqMaker->makePayment($paymentInfo, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'PaymentRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['PaymentRS'];
    }

    public function airTicket(String $session, Array $paymentInfo, Array $paymentResponse, String $timeStamp) {
        $wsConfig = $this->wsConfig;

        $wsConfig['session']    = $session;
        $wsConfig['timeStamp']  = $timeStamp;
        $wsConfig['endpoint']   = $this->config['endpoint'];

        array_walk($paymentInfo['payments'], function ($payment) use($wsConfig, $paymentResponse) {
            $req = '';
            $res = '';

            $req = $this->reqMaker->makeAirTicket($payment, $paymentResponse, $wsConfig);
            $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AirTicketRQ', '1.1');

            $error = $this->sabreUtil->getErrorFromResponse($res);
            if(strlen($error) > 0) throw new \Exception($error);

            Formatter::soapToArray($res)['AirTicketRS'];
        });
    }

    public function divideBooking($session, $requestInfo) {
    }

    public function divideAndCancelBooking($session, $requestInfo) {
    }

    public function getBooking($session, $requestInfo) {
        $req = '';
        $res = '';

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makeGetBooking($requestInfo, $wsConfig);

        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'getReservationRQ', '1.1');


        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['GetReservationRS'];
    }

    public function getAncillaryOffers(String $session, Array $reservation, Array $paxs, String $bagCode) {
        $req = '';
        $res = '';

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];
        $wsConfig['cityCode'] = 'BEL';

        $req = $this->reqMaker->makeAncillaryOffers($reservation, $paxs, $bagCode, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'GetAncillaryOffersRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['GetAncillaryOffersRS'];
    }

    //atualiza a rezerva com a bagagem
    public function updateReservation(String $session, Array $bookingInfo, Array $updateData) {
        $req = '';
        $res = '';
        $wsConfig = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makeUpdateReservationSellAncillary($updateData, $bookingInfo, $wsConfig);

        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'UpdateReservationRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['UpdateReservationRS'];
    }
    //>>
    //Pagamento da bagagem
    public function PaymentRQAncillary(String $session, Array $bookingInfo, $timeStamp) {
        $req = '';
        $res = '';
        $wsConfig = [];
        $paymentInfo = [];
        $paymentInfo['paxs'] = $bookingInfo['paxs'];
        $paymentInfo['payments'] = $bookingInfo['payments'];
        $wsConfig = $this->wsConfig;
        $wsConfig['locator'] = $bookingInfo['locator'];
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];
        $wsConfig['timeStamp'] = $timeStamp;
        $wsConfig['stationNr'] = 99768594;
        $wsConfig['channelId'] = 'GSA';

        foreach($bookingInfo['journeys'] as $journey){
                 foreach($journey['segments'] as $segment){
                    $paymentInfo['segments'][] = $segment;
            }
        }

        $req = $this->reqMaker->makePayment($paymentInfo, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'PaymentRQ', '4.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res)['PaymentRS'];
    }

    public function collectMiscFee(String $session, Array $bookingInfo, $timeStamp, $payAncillary) {
        $req = '';
        $res = '';
        $wsConfig = [];
        $wsConfig = $this->wsConfig;
        $wsConfig['locator'] = $bookingInfo['locator'];
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];
        $wsConfig['timeStamp'] = $timeStamp;
        $wsConfig['stationNr']  = 99768594;
        $wsConfig['stationCode']  = 9975326;
        $wsConfig['channelId'] = 'GSA';
        $wsConfig['compCode'] = 'LA';
        $wsConfig['AccountingCity'] = 'BEL';

        foreach($bookingInfo['paxs'] as $pax){
            foreach($pax['fees'] as $segment){
               $paymentInfo['segments'][] = $segment;
           }
        }


        $req = $this->reqMaker->makeCollectMiscFee($bookingInfo, $wsConfig, $payAncillary);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'PaymentRQ', '4.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);
        return Formatter::soapToArray($res)['PaymentRS'];
    }

    /**
     * Cancel reserve on same day of issue
     */
    public function voidTicket(String $session, String $ticketNumber, String $timeStamp) {
        $wsConfig = [];
        $req = '';

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['timeStamp'] = $timeStamp;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makeVoidTicket($ticketNumber, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'VoidTicketRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return $res;
    }

    /**
     * Cancel reserve
     */
    public function cancelIssue(String $session, String $timeStamp) {
        $wsConfig = [];
        $req = '';

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['timeStamp'] = $timeStamp;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makeCancelIssue($wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'OTA_CancelLLSRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return $res;
    }

    public function clearSession($session) {
    }

    public function ignoreTransaction(string $session) {
        $wsConfig = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;

        $header = $this->reqMaker->getHeader('IgnoreTransactionLLSRQ', 'IgnoreTransactionLLSRQ', $wsConfig);

        $req = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sec="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:mes="http://www.ebxml.org/namespaces/messageHeader" xmlns:ns="http://webservices.sabre.com/sabreXML/2011/10">
                {$header}
            <soapenv:Body>
                <ns:IgnoreTransactionRQ ReturnHostCommand="false" Version="2.0.0"/>
            </soapenv:Body>
            </soapenv:Envelope>
            XML;

        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'IgnoreTransactionRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        $res = Formatter::soapToArray($res);

        return $res;
    }

    public function logout($session) {
        $req = $this->reqMaker->logout($session, $this->config['endpoint']);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'SessionCloseRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        $res = Formatter::soapToArray($res);

        return $res;
    }
}
