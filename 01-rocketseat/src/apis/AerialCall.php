<?php

namespace Api;

use \Util\Response;

class AerialCall {

    function __construct() {
        $this->manager = new \Api\Manager();
    }

    /*###################################### UTIL ######################################*/

    private function getCompanysController(String $webServiceCode, bool $isSBS = false) {
        /*
            @return array with all requested Aerial Controllers.
        */
        $instanciateAerialControllers = function ($code, $isSBS) {
            $apiAssocService = new \Service\ApiServiceAssocClientOutService();
            $controllerGen = new \Api\Factory\AerialControllerFactory();
            $aerialControllers = [];

            $wsCredentials = $apiAssocService->fetchByClient(\Api\Controller\Controller::$JWT->getCompanyId(), $code);

            if($wsCredentials <= 0) {
                return null;
            }

            foreach ($wsCredentials as $credential) {
                $credential['password'] = \Util\SecureData::decryptData($credential['password']);
                $ws = $controllerGen->getController($code, $credential);
                $aerialControllers[$credential['apiServiceId']] = $ws;

                if(!$isSBS) $ws->logon();
            }

            return $aerialControllers;
        };

        $webServices = [];
        try {

            if($webServiceCode == null) throw new \Exception (getErrorMessage('wsNotInformed'));

            // Instance aerial controller by request aerial code.
            $code = strtoupper($webServiceCode);

            $res = $instanciateAerialControllers($code, $isSBS);
            if($res != null) $webServices[$code] = $res;

            // foreach ($webServices as $webServicesByCode) {
            //     foreach ($webServicesByCode as $ws) {
            //         if(!$isSBS) $ws->logon();
            //     }
            // }
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }

        return $webServices;
    }

    private function getSingleWebService($payload) {
        if(!isset($payload['ws_op'])) throw new \Exception(getErrorMessage('wsNotFound'));

        return $this->getCompanysController($payload['ws_op']);
    }

    private function getMultiWebService($payload) {
        if(!isset($payload['ws_op']) || count($payload['ws_op']) === 0) throw new \Exception(getErrorMessage('wsNotFound'));
        $webServices = [];

        foreach ($payload['ws_op'] as $wsCode) {
            $webServices = array_merge($webServices, $this->getCompanysController($wsCode));//cada companhia
        }

        return $webServices;
    }

    /*################################## AERIAL METHODS ##################################*/

    public function search(Array $payload) {
        $webServices = [];

        try {
            $webServices = $this->getMultiWebService($payload);
            $this->manager->search($payload, $webServices);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function getSeatAvailability(Array $payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->getSeatAvailability($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function booking($payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->booking($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function seatAssign(Array $payload) {
        $webService = null;

        try {
            if(!isset($payload['loc'])) throw new \Exception(getErrorMessage('missingLoc'));
            if(!isset($payload['ws_op'])) {
                $bookedLogCon = new \Controller\BookingLogController();
                $code = $bookedLogCon->fetchByLoc($payload['loc']);

                if(count($code) === 0) throw new \Exception(getErrorMessage('wsNotInformed'));

                $payload['ws_op'] = $code['company_code'];
            }

            $webService = $this->getSingleWebService($payload);
            $this->manager->seatAssign($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function ancillary(Array $payload) {
        $webService = null;

        try {
            if(!isset($payload['loc'])) throw new \Exception(getErrorMessage('missingLoc'));
            if(!isset($payload['ws_op'])) {
                $bookedLogCon = new \Controller\BookingLogController();
                $code = $bookedLogCon->fetchByLoc($payload['loc']);

                if(count($code) === 0) throw new \Exception(getErrorMessage('wsNotInformed'));

                $payload['ws_op'] = $code['company_code'];
            }

            $webService = $this->getSingleWebService($payload);
            $this->manager->ancillary($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function ancillaryPrice(Array $payload) {
        $webService = null;

        try {

            $webService = $this->getSingleWebService($payload);
            $this->manager->ancillaryPrice($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function commitAsHold(Array $payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->commitAsHold($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function confirmBooking(Array $payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->confirmBooking($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function divideAndCancelBooking(Array $payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->divideAndCancelBooking($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function divideBooking(Array $payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->divideBooking($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function cancelAncillaries(Array $payload) {
        $webService = null;
        $bookedLogCon = null;
        $code = '';

        try {
            if(!isset($payload['loc'])) throw new \Exception(getErrorMessage('missingLoc'));

            if(!isset($payload['ws_op'])) {
                $bookedLogCon = new \Controller\BookingLogController();
                $code = $bookedLogCon->fetchByLoc($payload['loc']);

                if(count($code) === 0) throw new \Exception(getErrorMessage('wsNotInformed'));

                $payload['ws_op'] = $code['company_code'];
            }
            $webService = $this->getSingleWebService($payload);
            $this->manager->cancelAncillaries($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function cancelLoc(Array $payload) {
        $webService = null;
        $bookedLogCon = null;
        $code = '';

        try {
            if(!isset($payload['loc'])) throw new \Exception(getErrorMessage('missingLoc'));
            if(!isset($payload['ws_op'])) {
                $bookedLogCon = new \Controller\BookingLogController();
                $code = $bookedLogCon->fetchByLoc($payload['loc']);

                if(count($code) === 0) throw new \Exception(getErrorMessage('wsNotInformed'));

                $payload['ws_op'] = $code['company_code'];
            }
            $webService = $this->getSingleWebService($payload);
            $this->manager->cancelLoc($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function cancelSSR(Array $payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->cancelSSR($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function getBooking(Array $payload) {
        $webService = null;
        $bookedLogCon = null;
        $code = '';

        try {
            if(!isset($payload['loc'])) throw new \Exception(getErrorMessage('missingLoc'));

            if(!isset($payload['ws_op'])) {
                $bookedLogCon = new \Controller\BookingLogController();
                $code = $bookedLogCon->fetchByLoc($payload['loc']);

                if(count($code) === 0) throw new \Exception(getErrorMessage('wsNotInformed'));

                $payload['ws_op'] = $code['company_code'];
            }
            $webService = $this->getSingleWebService($payload);
            $this->manager->getBooking($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function clearSession(Array $payload) {
        $webService = null;

        try {
            $webService = $this->getSingleWebService($payload);
            $this->manager->booking($payload, $webService);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }
}