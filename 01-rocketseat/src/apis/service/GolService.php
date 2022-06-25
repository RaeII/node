<?php

namespace Api\Service;

use Api\Helpers\GolRequestMaker;
use Api\Helpers\SabreUtil;
use \Util\Formatter;

class GolService extends \Api\Service\Service {
    function __construct($wsdlUrl, $config) {
        $this->config = $config;
        $this->reqMaker = new GolRequestMaker('G3');

        $wsConfig = [];

        $wsConfig['org'] = 'AAP';
        $wsConfig['domain'] = 'G3';
        $wsConfig['fromPartyId'] = '';
        $wsConfig['toPartyId'] = '';
        $wsConfig['CPAId'] = 'G3';
        $wsConfig['conversationId'] = 'ECOMPONENT';
        $wsConfig['pcc'] = 'HDQ';

        $this->wsConfig = $wsConfig;
        $this->sabreUtil = new SabreUtil();
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

    // #########################################################

    function logon(Array $credential) {
        $config = [];

        if($credential['endpoint'] === null || strlen($credential['endpoint']) === 0) throw new \Exception(getErrorMessage('missingCredentialInformation'));

        $this->soapCli = new \SoapClient("http://service.e-component.com/GWSSeparado/SessionCreateRQService.svc?wsdl", $options = array(
            'trace' => 1,
            'exceptions' => 1,
            'encoding' => 'UTF-8'
            )
        );

        $req = $this->reqMaker->makeLogon($credential, $this->wsConfig);
        $res = $this->soapCli->__doRequest($req, $credential['endpoint'], 'SessionCreateRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        return Formatter::soapToArray($res, 'Header');
    }

    function search($sessionContext, $requestInfo) {
        $wsConfig = $this->wsConfig;
        $res = [];

        $wsConfig['session'] =  $sessionContext;
        $wsConfig['pcc']            = 'SAO';
        $wsConfig['fromPartyId']    = 'WebServiceClient';
        $wsConfig['toPartyId']      = 'WebServiceSupplier';
        $wsConfig['personalcc']     = 'SAO';
        $wsConfig['accoutingCode']  = !empty($this->config['accounting_code']) ? $this->config['accounting_code'] : 'BD';
        $wsConfig['requestType']    = 'ADVBRD';
        $wsConfig['serviceTag']     = 'G3';
        $wsConfig['officeCode']     = !empty($this->config['office_code']) ? $this->config['office_code'] : '0304470';
        $wsConfig['defaultTicketingCarrier'] = 'G3';

        $wsConfig['airLowFareSearchAttrs'] = [];
        $wsConfig['airLowFareSearchAttrs']['ResponseType']      = 'OTA';
        $wsConfig['airLowFareSearchAttrs']['ResponseVersion']   = '5.1.0';
        $wsConfig['airLowFareSearchAttrs']['xmlns:xs']          = 'http://www.w3.org/2001/XMLSchema';
        $wsConfig['airLowFareSearchAttrs']['xmlns']             = 'http://www.opentravel.org/OTA/2003/05';
        $wsConfig['airLowFareSearchAttrs']['xmlns:xsi']         = 'http://www.w3.org/2001/XMLSchema-instance';

        $req = $this->reqMaker->makeSearch($requestInfo['trip_info'][0], $requestInfo['pax_info'], $wsConfig);
        $soapRes = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AdvancedAirShoppingRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($soapRes);
        if(strlen($error) > 0) throw new \Exception($error);

        $res[] = Formatter::soapToArray($soapRes)['OTA_AirLowFareSearchRS']['PricedItineraries'];

        if(isset($requestInfo['trip_info'][0]['back_date'])) {
            $aux = $requestInfo['trip_info'][0];

            $aux['from'] = $requestInfo['trip_info'][0]['to'];
            $aux['to'] = $requestInfo['trip_info'][0]['from'];
            $aux['dep_date'] = $requestInfo['trip_info'][0]['back_date'];

            $req = $this->reqMaker->makeSearch($aux, $requestInfo['pax_info'], $wsConfig);
            $soapRes =  $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AdvancedAirShoppingRQ', '1.1');

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
        $wsConfig['pcc']            = 'SAO';
        $wsConfig['personalcc']     = 'SAO';
        $wsConfig['accoutingCode']  = !empty($this->config['accounting_code']) ? $this->config['accounting_code'] : 'BD';
        $wsConfig['requestType']    = 'ADVBRD';
        $wsConfig['serviceTag']     = 'G3';
        $wsConfig['officeCode']     = !empty($this->config['office_code']) ? $this->config['office_code'] : '0304470';
        $wsConfig['defaultTicketingCarrier'] = 'G3';

        $wsConfig['airLowFareSearchAttrs'] = [];
        $wsConfig['airLowFareSearchAttrs']['ResponseType']      = 'OTA';
        $wsConfig['airLowFareSearchAttrs']['ResponseVersion']   = '5.1.0';
        $wsConfig['airLowFareSearchAttrs']['xmlns:xs']          = 'http://www.w3.org/2001/XMLSchema';
        $wsConfig['airLowFareSearchAttrs']['xmlns']             = 'http://www.opentravel.org/OTA/2003/05';
        $wsConfig['airLowFareSearchAttrs']['xmlns:xsi']         = 'http://www.w3.org/2001/XMLSchema-instance';

        if(!empty($requestInfo['promo_code'])) $extraMisc['promo_code'] = $requestInfo['promo_code'];

        $req = $this->reqMaker->searchCombined($requestInfo, $wsConfig, $extraMisc);
        $soapRes = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AdvancedAirShoppingRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($soapRes);
        if(strlen($error) > 0) throw new \Exception($error);

        $res[] = Formatter::soapToArray($soapRes)['OTA_AirLowFareSearchRS']['PricedItineraries'];

        return $res;
    }

    function getSeatAvailability($sessionContext, $requestInfo) {

    }

    function seatAssign($sessionContext, $requestInfo) {

    }

    public function enhancedAirBook($session, $req) {
        $wsConfig = [];

        $paxs = array_reduce($req['pax_info'], function ($acc, $pax) {
            if($pax['type'] === 'ADT') $acc['adults'] += 1;
            else if($pax['type'] === 'CHD') $acc['childs'] += 1;
            else if($pax['type'] === 'INF') $acc['infs'] += 1;

            return $acc;
        }, ['adults' => 0, 'childs' => 0, 'infs' => 0]);

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makeEnhancedAirBook($req, $paxs, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'EnhancedAirBookRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
        if(strlen($error) > 0) throw new \Exception($error);

        $res = Formatter::soapToArray($res);
        if(empty($res['EnhancedAirBookRS'])) throw new \Exception(getErrorMessage('enhancedAirBookNoResponse'));
        // if(!empty($res['EnhancedAirBookRS']['ApplicationResults']['Error'])) LatamUtil::throwLatamError($res['EnhancedAirBookRS']);

        return $res['EnhancedAirBookRS'];
    }

    public function passengerDetailsPaxs($session, $request, $user) {
        $req = '';
        $res = '';
        $wsConfig = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $wsConfig['passengerDetailsRQ'] = [];
        $wsConfig['passengerDetailsRQ']['xmlns'] = 'http://services.sabre.com/sp/pd/v3_4';

        $req = $this->reqMaker->makePassengerDetailsPaxs($request, $user, $wsConfig);
        $res =  $this->soapCli->__doRequest($req, $this->config['endpoint'], 'PassengerDetailsRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);
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

    function book($sessionContext, $requestInfo) {

    }

    function operateSSRInf($sessionContext, $requestInfo, $ssrRequestType) {

    }

    function operateSSRBag($sessionContext, $requestInfo, $ssrRequestType) {

    }

    function confirmBooking($sessionContext, $requestInfo, $accNum) {

    }

    public function addPayment(String $session, Array $paymentInfo, String $timeStamp) {
        $wsConfig = $this->wsConfig;
        $req = '';
        $res = '';

        $wsConfig['session']    = $session;
        $wsConfig['endpoint']   = $this->config['endpoint'];
        $wsConfig['timestamp']  = $timeStamp;
        $wsConfig['stationNr']  = 99768594;
        $wsConfig['channelId']  = 'GSA';
        $wsConfig['merchantId'] = 'G3';

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

    function divideBooking($sessionContext, $requestInfo) {

    }

    function divideAndCancelBooking($sessionContext, $requestInfo) {

    }

    function getBooking($sessionContext, $requestInfo) {
        $req = '';
        $res = '';

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $sessionContext;
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


    
    public function ticketingDocument(String $session, Array $data) {
        $req = '';
        $res = '';
        $wsConfig = [];

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makeTicketingDocument($data, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'GetTicketingDocumentRQ', '1.1');

        $error = $this->sabreUtil->getErrorFromResponse($res);

        if(strlen($error) > 0) throw new \Exception($error);
        return Formatter::soapToArray($res)['GetTicketingDocumentRS'];
    }

    public function aerTicket(String $session, Array $data) {
        $req = '';
        $res = '';
        $wsConfig = [];

        if(empty($data['transaction_action']))  throw new \Exception(getErrorMessage('missingDataToOperation', 'AerTicket: Transaction action'));
        if(empty($data['payment']))             throw new \Exception(getErrorMessage('missingDataToOperation', 'AerTicket: Payment'));
        if(empty($data['pax']))                 throw new \Exception(getErrorMessage('missingDataToOperation', 'AerTicket: Pax'));
        if(empty($data['type']))                throw new \Exception(getErrorMessage('missingDataToOperation', 'AerTicket: Tipo'));
        if(empty($data['serial_number']))       throw new \Exception(getErrorMessage('missingDataToOperation', 'AerTicket: Serial Number'));
        if(empty($data['accounting_code']))     throw new \Exception(getErrorMessage('missingDataToOperation', 'AerTicket: Accounting Code'));

        $wsConfig = $this->wsConfig;
        $wsConfig['session'] = $session;
        $wsConfig['endpoint'] = $this->config['endpoint'];

        $req = $this->reqMaker->makeAERTicket($data, $wsConfig);
        $res = $this->soapCli->__doRequest($req, $this->config['endpoint'], 'AER_RQ', '1.1');
        // print_r($res);
        $error = $this->sabreUtil->getErrorFromResponse($res);

        if(strlen($error) > 0) throw new \Exception($error);
        return Formatter::soapToArray($res)['AER_RS'];
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

    function clearSession($sessionContext) {

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

    function logout($sessionContext) {

    }
}