<?php

namespace Api;

use Util\Formatter;
use \Util\Response;
use \Util\Validator;

class Manager {

    private $webServices = [];
    private $client = [];
    // //array[CompanyId] = $credential.
    // private $credentialsOrderedByCompId = [];

    function __construct() {
        $this->client = (new \Service\ClientOutService())->fetch(\Api\Controller\Controller::$JWT->getCompanyId());
    }

    private function occultCredentialId($id) {
        $result = '';
        foreach (str_split($id) as $num) {
            $asciiNum = (int)ord($num);

            $result .= chr($asciiNum * 2 + 1);
        }

        return $result;
    }

    private function unoccultCredentialId($letters) {
        $result = '';
        foreach (str_split($letters) as $letter) {
            $asciiNum = (int)ord($letter);

            if($asciiNum % 2 == 0) {
                $result .= chr($asciiNum / 2 - 1);
            }else {
                $result .= chr($asciiNum / 2);
            }
        }

        return $result;
    }

    private function getLocEmitter($loc) {
        $bookingLog = new \Controller\BookingLogController();
        $perm_level = $_SESSION['permission_level'];
        if($perm_level == 0) {
            return $bookingLog->fetchByLoc($loc);
        }else {
            return $bookingLog->fetchByLoc($loc, \Api\Controller\Controller::$JWT->getId());
        }
    }

    private function logBookingOnDatabase($credentialId, $bookingRes) {
        $bookingLog = new \Controller\BookingLogController();
        $bookingReg = new \Controller\BookingRegController();
        $bookingRes['credential_id'] = $credentialId;
        $bookingRes['user_account_id'] = \Api\Controller\Controller::$JWT->getId();
        $bookingLog->create($bookingRes, $this->client['clientId']);
        $bookingReg->create($bookingRes, $this->client['clientId']);

        return (bool)$bookingLog->fetchByLoc($bookingRes['locator'])['error_summing'];
    }

    // private function logBookingContactOnDataBase($locator, $contacs) {
    //     array_find()
    // }

    private function logDividedBookingOnDatabase($booking, $parentLoc) {
        $bookingReg = new \Service\BookingRegService();
        $bookingRegLoc = new \Service\BookingRegLocsService();
        $toLog = [];

        $bookedReg = $bookingReg->fetchByLocator($parentLoc);

        $toLog['locator'] = $booking['locator'];
        $toLog['description'] = NULL;
        $bookingRegLoc->create($toLog, $bookedReg['id']);
        return true;
    }

    private function logStatusOnBooking($booking, $status) {
        $bookingLog = new \Service\BookingLogService();
        $toLog = [];

        $toLog['locator'] = $booking['locator'];
        $toLog['status'] = $status;
        $bookingLog->updateStatus($toLog);
        return true;
    }

    /*
        - Only the user that have been emitted the PNR can view and edit it.
            With exception to Super users (Level 0), that can see any PNR and edit any PNR.
        - Case not Super user (Level 0), will be selected by actual user and loc on database
            to check if is that user that emitted this loc.
    */
    private function getCredentialId($request, $webServices) {
        $perm_level = $_SESSION['permission_level'];
        $credentialId = 0;

        if(!isset($request['loc']) || $request['loc'] == '') throw new \Exception(getErrorMessage('missingLoc'));

        // Normal Users can`t use code, only Super users. Then only Super users an infer Code.
        if(!isset($request['code'])) {
            $bookingLogData = $this->getLocEmitter($request['loc']);
            if(count($bookingLogData) <= 0) throw new \Exception(getErrorMEssage('locNotFoundOrUsetNotIssuer'));
            $credentialId = $bookingLogData['api_service_credential_id'];
        }else if (isset($request['code']) && $perm_level == '0') {
            $credentialId = $this->unoccultCredentialId($request['code']);
        }else {
            throw new \Exception(getErrorMEssage('userLevelNotPermitted'));
        }

        $airCode = $request['ws_op'];
        if(!isset($webServices[$airCode][$credentialId])) {
            throw new \Exception(getErrorMessage('credentialCodeNotFound'));
        }

        return $credentialId;
    }

    private function getSBSCredentialId($request) {
        $perm_level = $_SESSION['permission_level'];
        $credentialId = 0;

        if (isset($request['code']) && $perm_level == '0') {
            $credentialId = $this->unoccultCredentialId($request['code']);
        }else {
            throw new \Exception(getErrorMEssage('userLevelNotPermitted'));
        }

        return $credentialId;
    }

    private function unsertJourneysKeys($journeys) {
        foreach ($journeys as &$journey) {
            foreach ($journey['fares'] as &$fares) {
                unset($fares['promotional_code']);
                // print_r($fares['paxs_fare']);die();
                foreach ($fares['paxs_fare'] as &$paxFare) {
                    unset($paxFare['promotional']);
                }
                unset($paxFare);
            }
            unset($fares);
            unset($journey['key']);
        }
        unset($journey);
        return $journeys;
    }

    private function filterPaxFareDiscount($taxes) {
        $filter = function ($taxe) {return $taxe === 'Disconto';};

        return array_filter($taxes, $filter);
    }

    private function filterPaxFareTax($taxes) {
        $filter = function ($taxe) {return $taxe === 'Taxa';};

        return array_filter($taxes, $filter);
    }

    private function getMarkupPercValue(float $tariffValue, float $perc) {
        return bcmul(bcdiv($perc, 100, 2), $tariffValue, 2);
    }

    private function applyMarkups(float $value, float $totalValue, float $totalPercValue, Array $config, String $valueType) {
        $totalByValue = 0;

        if($config['applyMarkup'] == 1) {
            $totalByValue = bcadd($value, $totalValue, 2);

            $value = bcadd($totalPercValue, $totalByValue, 2);
        }

        return $value;
    }

    private function applyDiscounts(float $value, float $discountValue, Array $config, String $valueType) {
        if($config['fareEqualNet'] == 1) {
            $value = bcadd($value, $discountValue, 2);
        }else {
            if($valueType == 'TR') {
                $value = bcsub($value, $discountValue, 2);
            }
        }
        // else if ($config['applyPromoCodeRepass'] == 1)

        return $value;
    }

    private function applyRepassValue(float $value, float $totalPromoCode, Array $config) {
        $repassPct = $config['promoCodeRepassValue'];

        if($config['applyPromoCodeRepass'] == 1) {
            $totalRepass = bcmul(bcdiv($repassPct, 100, 2), $totalPromoCode, 2);
            $value = bcsub($value, $totalRepass, 2);
        }

        return $value;
    }

    private function applyOverAndDiscounts(Array $response, String $locator = null) {
        if(!isset($response['journeys']) || count($response['journeys']) === 0) {
            return $response;
        }
        $journeys = &$response['journeys'];
        $paxs = [];
        if(isset($response['paxs'])) {
            $paxs = &$response['paxs'];
        }
        $clientAssocService = new \Service\ClientAssocMarkupAssocCompanyOutService();
        $bkgRegSrv = new \Service\BookingRegService();
        $bkgRegMarkupsSrv = new \Service\BookingRegMarkupsService();
        $bkgRegDiscountSrv = new \Service\BookingRegDiscountService();
        $config = $this->client;
        $totalMarkups = [];
        $totalDiscount = 0;
        $sumTariffPercValue = 0;

        /*
            $locator equal null when searching voos.
            $locator diff null when using getBooking.
        */
        if($locator == null) {
            $totalMarkups = $clientAssocService->fetchSumByClient($this->client['clientId']);
        }else {
            $config = $bkgRegSrv->fetchByLocator($locator);
            $totalDiscount = $bkgRegDiscountSrv->fetchSumByLocator($locator);
            $totalMarkups = $bkgRegMarkupsSrv->fetchSumByLocator($locator);
        }

        if($config['fareEqualNet'] == 1) {
            $totalMarkups = $clientAssocService->fetchByClientAndRole($this->client['clientId'], 'DU');
            $totalDiscount = 0;
        }

        // Apply Markup, Discounts and Repass Value on Segment Values.
        foreach ($journeys as &$journey) {
            $fares = &$journey['fares'];
            foreach ($fares as &$fare) {

                foreach ($fare['paxs_fare'] as &$paxFare) {
                    $percValue = 0;

                    if(count($totalMarkups) > 0 && $totalMarkups['total_perc'] != 0 ){
                        $percValue = $this->getMarkupPercValue($paxFare['amount'], $totalMarkups['total_perc']);
                        $sumTariffPercValue += $percValue;
                    }

                    // ###########
                    if(count($totalMarkups) > 0) $paxFare['amount'] = $this->applyMarkups($paxFare['amount'], $totalMarkups['total_value'], $percValue, $config, 'TR');
                    if($totalDiscount != 0) $paxFare['amount'] = $this->applyDiscounts($paxFare['amount'], $totalDiscount, $config, 'TR');
                    if($totalDiscount != 0) $paxFare['amount'] = $this->applyRepassValue($paxFare['amount'], $totalDiscount, $config);
                    // // ###########
                    // if(isset($paxFare['promotional'])) $paxFare['promotional'] = number_format($paxFare['promotional'], 2, ',', '.');
                    // $paxFare['amount'] = number_format($paxFare['amount'], 2, ',', '.');
                    // $paxFare['tax_amount'] = number_format($paxFare['tax_amount'], 2, ',', '.');
                    // $paxFare['amount'] = round($paxFare['amount'], 2);
                }
                unset($paxFare);
            }
            unset($paxsFares);
        }
        unset($journey);

        // Apply Markup, Discounts and Repass Value on Pax Values.
        foreach ($paxs as &$pax) {
            if(count($totalMarkups) > 0 && $totalMarkups['total_value'] != 0 || $totalMarkups['total_perc'] != 0) {
                $pax['total_cost']      = $this->applyMarkups($pax['total_cost'], $totalMarkups['total_value'], $sumTariffPercValue, $config, 'PX');
                $pax['total_tariff']    = $this->applyMarkups($pax['total_tariff'], $totalMarkups['total_value'], $sumTariffPercValue, $config, 'PX');
            }
            // if(count($totalMarkups) > 0 && $totalMarkups['total_value'] != 0 || $totalMarkups['total_perc'] != 0) $pax['balance_due'] = $this->applyMarkups($pax['balance_due'], $totalMarkups['total_value'], $sumTariffPercValue, $config, 'PX');

            if($totalDiscount != 0) {
                $pax['total_cost']      = $this->applyDiscounts($pax['total_cost'], $totalDiscount, $config, 'PX');
                $pax['total_tariff']    = $this->applyDiscounts($pax['total_tariff'], $totalDiscount, $config, 'PX');
            }
            // if($totalDiscount != 0) $pax['balance_due'] = $this->applyDiscounts($pax['balance_due'], $totalDiscount, $config, 'PX');

            if($totalDiscount != 0) {
                $pax['total_cost']      = $this->applyRepassValue($pax['total_cost'], $totalDiscount, $config);
                $pax['total_tariff']    = $this->applyRepassValue($pax['total_tariff'], $totalDiscount, $config);
            }
            // if($totalDiscount != 0) $pax['balance_due'] = $this->applyRepassValue($pax['balance_due'], $totalDiscount, $config);

            // $pax['total_cost'] = round($pax['total_cost'], 2);
            // $pax['balance_due'] = round($pax['balance_due'], 2);
        }
        unset($pax);
        unset($paxs);

        return $response;
    }

    private function roundJourneyValues($journey) {
        $roundFareValues = function ($fare) {
            $fare['paxs_fare'] = array_map(function ($paxFare) {
                $paxFare['amount'] = number_format($paxFare['amount'], 2, '.', '');

                // Round taxes
                $paxFare['taxes'] = array_map(function ($tax) {
                    $tax['total'] = number_format($tax['total'], 2, '.', '');

                    return $tax;
                }, $paxFare['taxes']);

                return $paxFare;
            }, $fare['paxs_fare']);

            return $fare;
        };

        $journey['fares'] = array_map($roundFareValues, $journey['fares']);
        return $journey;
    }

    private function roundPaxValues($pax) {
        $pax['total_cost'] = number_format($pax['total_cost'], 2, '.', '');
        $pax['balance_due'] = number_format($pax['balance_due'], 2, '.', '');

        return $pax;
    }

    // private function getMoreToOverallInfo ($journey) {
    //     $connectionsCount = count($journey['segments']) - 1;

    //     $depDate = new \DateTime($journey['overall']['dep_date'] . 'T' . $journey['overall']['dep_hour']);
    //     $arrDate = new \DateTime($journey['overall']['arr_date'] . 'T' . $journey['overall']['arr_hour']);
    //     $dateDiff = $depDate->diff($arrDate);
    //     $hoursToAdd = $dateDiff->format('%a') * 24;
    //     $hours = $dateDiff->format('%h') * 1;

    //     $diffFormated = ($hours + $hoursToAdd) . $dateDiff->format('h %imin');

    //     $overall = $journey['overall'];
    //     $overall['duration'] = $diffFormated;
    //     $overall['connections'] = $connectionsCount;

    //     return $overall;
    // }

    public function search($request, $webServicesByCode) {
        $searchesResults = [];
        $searchesResults['trips'] = [];
        $response = [];
        $response['trips'] = [];
        $userLevel = $_SESSION['permission_level'];
        $extraToPackInfo = [
            'promo_code'            => '',
            'credential_id'         => 0,
            'credential_username'   => ''
        ];

        // Get best value from any voo searched by promo codes.
        $getLessPricesFromJourney = function ($journeys, $lessValueResult) {
            $sumTaxes = function ($fares) {

                return array_reduce($fares, function ($carry, $fare) {
                    $adtFare    = $fare['paxs_fare'][0];
                    $totalTax   = array_reduce($adtFare['taxes'], function ($carry, $tax) {
                        return $carry + $tax['total'];
                    }, 0);

                    return $carry + $adtFare['amount'] + $totalTax - $adtFare['promotional'];
                }, 0);
            };

            for ($index = 0; $index < count($journeys); $index++) {
                $journeysLessValue = $lessValueResult;
                /*
                    Check if departure date is equal.
                    False: Maybe voo is sold.
                */
                if($journeysLessValue[$index]['segments'][0]['dep_date'] != $journeys[$index]['segments'][0]['dep_date']) continue;

                // echo '  Sum Index:  ' . $sumTaxes($journeys[$index]['fares']); echo '  Sum Less:  ' . $sumTaxes($journeysLessValue[$index]['fares']);
                if($sumTaxes($journeys[$index]['fares']) < $sumTaxes($journeysLessValue[$index]['fares'])) {
                    $lessValueResult['journeys'][$index] = $journeys[$index];
                }
            }

            return $lessValueResult;
        };

        $addCredentialIdToResult = function ($searchesResults, $credentalId) {
            if(count($searchesResults) <= 0) {
                return [];
            }

            foreach ($searchesResults['journeys'] as &$voo) {
                $voo["code"] = $credentalId;
            }
            unset($voo);

            return $searchesResults;
        };

        $sortByValue = function ($previous, $journey) {
            $previousValue = $previous['fares'][0]['paxs_fare'][0]['amount'];
            $actualvalue = $journey['fares'][0]['paxs_fare'][0]['amount'];

            if ((float)$previousValue === (float)$actualvalue) {
                return 0;
            }

            return ((float)$previousValue < (float)$actualvalue) ? -1 : 1;
        };

        //>>
        //retorna os resultados da pesquisa
        $packAndAddSearchResults = function ($controllerResponse, $searchesResults, $extraToPackInfo) {
            $packBase64Keys = function ($journeys, $extraToPackInfo) {
                list(
                    'promo_code'            => $promoCode,
                    'credential_id'         => $credentialId,
                    'credential_username'   => $credentialUserName,
                    'credential_label'      => $credentialLabel
                ) = $extraToPackInfo;

                foreach ($journeys as &$journey) {
                    foreach ($journey['fares'] as &$fares) {
                        $extraInfo['journey_key']   = isset($journey['key']) ? $journey['key'] : '';
                        $extraInfo['tax_key']       = isset($fares['key']) ? $fares['key'] : '';
                        $extraInfo['product_class'] = $fares['product_class'];

                        $extraInfo['cp']        = !empty($fares['promotional_code']) ? $promoCode['promo_code_id'] : '0';
                        $extraInfo['cp_code']   = !empty($promoCode['promo_code']) ? $promoCode['promo_code'] : '';
                        $extraInfo['ci']        = $credentialId;
                        $extraInfo['clabel']    = $credentialLabel;
                        if($this->client['show_credentials_info']) {
                            $extraInfo['cusername'] = $credentialUserName;
                        }
                        // $this->occultCredentialId($credentialId));
                        // unset($fares['promotional_code']);
                        // unset($fares['promotional']);
                        $fares['key'] = base64_encode(json_encode($extraInfo));
                    }
                    unset($fares);
                    // unset($journey['key']);
                }
                unset($journey);

                return $journeys;
                // $response['going'] = $addCredentialIdToResult($response['going'], $this->occultCredentialId($credentialId));
                // $response['return'] = $addCredentialIdToResult($response['return'], $this->occultCredentialId($credentialId));
            };

            for ($index = 0; $index < count($controllerResponse['trips']); $index++) {
                $trip = $controllerResponse['trips'][$index];

                $trip = $packBase64Keys($trip, $extraToPackInfo);

                if(isset($searchesResults['trips'][$index]['journeys'])) {
                    $searchesResults['trips'][$index]['journeys'] = array_merge($searchesResults['trips'][$index]['journeys'], $trip);
                }else {
                    $searchesResults['trips'][$index]['journeys'] = $trip;
                }
            }

            return $searchesResults;

        };
        // if(!isset($request['trip_info']))   throw new \Exception(getErrorMessage('incorretJSONStuct', 'trip_info'));

        Validator::validateJSONKeys($request, array('combined_flights', 'trip_info', "pax_info"));
        Validator::existValueOrError($request['combined_flights'], 'Voos combinados');
        Validator::existValueOrError($request['pax_info'], 'Passageiros');


        // Validate And Format payload fields.
        $request['trip_info'] = array_map(function ($route) {
            // ########### Trip Info Validation
            $NEEDED_TRIP_INFO = array(
                "from",
                "to",
                "dep_date"
            );


            Validator::validateJSONKeys($route, $NEEDED_TRIP_INFO);
            Validator::existValuesOrError($route, [
                'from' => 'De',
                'to' => 'Para',
                'dep_date' => 'Data de partida'
            ]);

            // ##### Format Dates
            $route['dep_date'] = Formatter::date($route['dep_date']);
            // #####

            if(strtotime($route['dep_date']) < strtotime('now')) throw new \Exception(getErrorMessage('invalidDate', 'Partida'));
            if(!empty($route['back_date']) && strtotime($route['back_date']) < strtotime($route['dep_date'])) throw new \Exception(getErrorMessage('invalidDate', 'volta'));

            $route['from'] = strtoupper($route['from']);
            $route['to'] = strtoupper($route['to']);

            return $route;
        }, $request['trip_info']);




        // ########### Pax Info Validation
        if(count($request['pax_info']) <= 0) throw new \Exception(getErrorMessage('wsRequestMissingData') + 'Paxs');
        Validator::validateJSONKeys($request['pax_info'], array('adults', 'childs', 'infs'));

        Validator::existValueOrError($request['pax_info']['adults'], 'Quantidade ADT');
        Validator::existValueOrError($request['pax_info']['childs'], 'Quantidade CHD');
        Validator::existValueOrError($request['pax_info']['infs'], 'Quantidade INF');
        // ###########

        try {

            // If promo code is applicable, serch with all promo code availables.
            if($this->client['applyPromoCode'] == 1) {
                $promoCodeAssocServ = new \Service\ClientAssocPromoCodeService();
                // $promoCodeDB = new \DataBase\PromoCode();

                // $promoCodeArrangedByPc = $promoCodeDB->fetchAllResultArrangedByPromoCode();
                $clientId = $this->client['clientId'];

                foreach ($webServicesByCode as $code => $webServices) {
                    $promoCodesByCompany = $promoCodeAssocServ->fetchByClientAndCompany($clientId, $code);

                    $promoCodesToNegate = array_reduce($promoCodesByCompany, function ($acc, $pc) {
                        $acc[] = $pc['promo_code_id'];

                        return $acc;
                    }, []);

                    foreach ($webServices as $credentialId => $ws) {

                        $promoCodesByCredential = $promoCodeAssocServ->fetchByClientAndCredential($clientId, $credentialId, $promoCodesToNegate);
                        $promoCodes             = array_merge($promoCodesByCredential, $promoCodesByCompany);
                        $lessValueResult        = [];

                        if(!empty($request['promo_code'])) $promoCodes[] = ['promo_code' => $request['promo_code']];

                        // Search With promo codes availables.
                        if(count($promoCodes) > 0) {
                            foreach ($promoCodes as $promoCode) {
                                $request['promo_code'] = $promoCode['promo_code'];


                                //resposta

                                $response = $ws->search($request);
                                // $response['going'] = $addCredentialIdToResult($response['going'], $this->occultCredentialId($credentialId));
                                // $response['return'] = $addCredentialIdToResult($response['return'], $this->occultCredentialId($credentialId));
                                if(count($lessValueResult) != 0) {
                                    for ($index = 0; $index < count($response); $index ++) {
                                        $lessValueResult[$index] = $getLessPricesFromJourney($response['trips'][$index], $lessValueResult[$index]);
                                    }
                                }else {
                                    $lessValueResult = $response['trips'];
                                }

                                $extraToPackInfo['promo_code']          = $promoCode;
                                $extraToPackInfo['credential_id']       = $credentialId;
                                $extraToPackInfo['credential_username'] = $ws->getCredentialUsername();
                                $extraToPackInfo['credential_label']    = $ws->getCredentialLabel();

                                $searchesResults = $packAndAddSearchResults($response, $searchesResults, $extraToPackInfo);

                            }
                        }else {//EX LA - CADA COMPANHIA SERA UMA WS

                            foreach ($webServicesByCode as $code => $webServices) {
                                foreach ($webServices as $credentialId => $ws){

                                    $response = $ws->search($request);

                                    $extraToPackInfo['credential_id']       = $credentialId;
                                    $extraToPackInfo['credential_username'] = $ws->getCredentialUsername();
                                    $extraToPackInfo['credential_label']    = $ws->getCredentialLabel();
                                    $searchesResults = $packAndAddSearchResults($response, $searchesResults, $extraToPackInfo);
                                }
                            }
                        }
                    }
                }
            }else {
                // Remove Promo code from request case not is SUPER USER.
                if(isset($request['promo_code']) && $_SESSION['permission_level'] != 0) {
                    unset($request['promo_code']);
                }

                foreach ($webServicesByCode as $code => $webServices) {
                    foreach ($webServices as $credentialId => $ws) {
                        $response = $ws->search($request);

                        $extraToPackInfo['crednetial_id']       = $credentialId;
                        $extraToPackInfo['credential_username'] = $ws->getCredentialUsername();
                        $extraToPackInfo['credential_label']    = $ws->getCredentialLabel();
                        $searchesResults = $packAndAddSearchResults($response, $searchesResults, $extraToPackInfo);
                    }
                }
            }

            for ($index = 0; $index < count($searchesResults['trips']); $index++) {
                $trip = $searchesResults['trips'][$index];

                // $trip['journeys'] = array_map(function ($journey) {
                //     $journey['overall'] = $this->getMoreToOverallInfo($journey);
                //     return $journey;
                // }, $trip['journeys']);

                $trip = $this->applyOverAndDiscounts($trip);

                if($userLevel != 0) {
                    $trip = $this->unsertJourneysKeys($trip);
                }
                $trip['journeys'] = array_map(array($this, 'roundJourneyValues'), $trip['journeys']);

                usort($trip['journeys'], $sortByValue);
                $searchesResults['trips'][$index] = $trip;
            }

            Response::sendContent($searchesResults);//resposta para a pesquisa
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }
//////////////////////////////////////////////////////////////////////
    private function AddClientInfoToBkContacts($bookingContacts) {
        $clientInfoSvc = new \Service\ClientInfoService();
        $arrangedBkContatcs = [];
        $clientContact = [];
        $issuerContact = [];
        $issuerContactIndex = -1;

        $clientInfo = $clientInfoSvc->fetch(\Api\Controller\Controller::$JWT->getCompanyId());

        if(count($clientInfo) === 0) throw new \Exception(getErrorMessage('missingClientInfo'));

        for ($index = 0; $index < count($bookingContacts) ; $index++) {
            $bookingContact = $bookingContacts[$index];

            if(!empty($bookingContact['type']) && $bookingContact['type'] === 'ems') {
                $issuerContactIndex = $index;
                $issuerContact = $bookingContact;
                break;
            }
        }
        // $issuerContact = array_filter($bookingContacts, function ($bookingContacts) {
        //     return !empty($bookingContacts['type']) && $bookingContacts['type'] === 'ems';
        // });

        $clientContact['email']                 = $clientInfo['email'];
        $clientContact['phone']                 = $clientInfo['ddd'] . $clientInfo['phone_number'];
        $clientContact['public_place']          = $clientInfo['public_place'];
        $clientContact['address_number']        = $clientInfo['address_number'];
        $clientContact['address_complement']    = $clientInfo['address_complement'];
        $clientContact['cnpj']                  = $clientInfo['cnpj'];
        if(!empty($clientInfo['country_code']))  $clientContact['phone']            = $clientInfo['country_code'] . $clientContact['phone'];
        if(!empty($clientInfo['city']))          $clientContact['city']             = $clientInfo['city'];
        if(!empty($clientInfo['province_code'])) $clientContact['province_code']    = $clientInfo['province_code'];
        if(!empty($clientInfo['postal_code']))   $clientContact['postal_code']      = $clientInfo['postal_code'];

        if(count($issuerContact) === 0) {
            $userAccService = new \Service\UserAccountService();
            $accInfo = $userAccService->fetchById(\Api\Controller\Controller::$JWT->getId());

            $clientContact['first_name'] = $accInfo['name'];
            $clientContact['last_name'] = $accInfo['last_name'];
        }else {
            $clientContact['first_name'] = $issuerContact['first_name'];
            $clientContact['last_name'] = $issuerContact['last_name'];
            if(!empty($issuerContact['sufix']))         $clientContact['sufix']         = $issuerContact['sufix'];
            if(!empty($issuerContact['middle_name']))   $clientContact['middle_name']   = $issuerContact['middle_name'];

            unset($bookingContacts[$issuerContactIndex]);
        }
        $arrangedBkContatcs[] = $clientContact;
        $arrangedBkContatcs = array_merge($arrangedBkContatcs, $bookingContacts);

        return $arrangedBkContatcs;
    }

    private function paymentValidation($request, $isConfirmMethod = false) {
        $perm_level = $_SESSION['permission_level'];

        if(empty($request['payments']) && ((int)$perm_level) !== 0 ||
        !$isConfirmMethod && $request['confirm'] === 1 && empty($request['payments'])) throw new \Exception(getErrorMessage('paymentNotInformed'));
        if(!empty($request['payments'])) {
            foreach ($request['payments'] as $payment) {
                if(empty($payment['type'])) throw new \Exception(getErrorMessage('paymentFieldNotInformed', 'Tipo'));
                if(empty($payment['value'])) throw new \Exception(getErrorMessage('paymentFieldNotInformed', 'Valor'));

                if($payment['type'] === 'CC') {
                    if(empty('code')) throw new \Exception(getErrorMessage('paymentFieldNotInformed', 'Codigo'));
                    if(empty('acc_holder_name')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Nome do portador'));
                    if(empty('acc_sec_code')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Codigo de Seguranca'));
                    if(empty('acc_number')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Numero'));
                    if(empty('exp_date')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Data de vencimento'));
                    if(empty('num_instal')) throw new \Exception(getErrorMessage('installmentsNotInformed'));
                }else if($payment['type'] === 'CD') {
                    if(empty('code')) throw new \Exception(getErrorMessage('paymentFieldNotInformed', 'Codigo'));
                    if(empty('acc_holder_name')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Nome do portador'));
                    if(empty('acc_sec_code')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Codigo de Seguranca'));
                    if(empty('acc_number')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Numero'));
                    if(empty('exp_date')) throw new \Exception(getErrorMessage('cardFieldNotInformed', 'Data de vencimento'));
                }
            }
        }
    }

    public function booking($request, $webService) {
        $code = $request['ws_op'];
        $credentialId = null;
        $journeys = [];
        $requests = [];
        $response = [];
        $errorMessage = '';
        $perm_level = $_SESSION['permission_level'];

        // ##### Functional functions
        $base64UnpackNArrange = function ($journey) {
            $unpackBase64AndArrange = function ($bases64) {
                $promoCodeDB = new \DataBase\PromoCode();
                $arranged = [];
                $keys = json_decode(base64_decode($bases64), true);
                $promoCodeArrangedById = $promoCodeDB->fetchAllResultArrangedById();

                // if($wsCode == 'AD') {
                $arranged['journey'] = $keys['journey_key'];
                $arranged['tax'] = $keys['tax_key'];
                // }
                $arranged['ci'] = $keys['ci'];
                if($keys['cp'] != '0') {
                    $arranged['cp'] = $promoCodeArrangedById[$keys['cp']];
                }else {
                    $arranged['cp'] = '';
                }

                return $arranged;
            };

            $journey['key'] = $unpackBase64AndArrange($journey['key']);
            return $journey;
        };
        $journeyByCredentialReducer = function ($arranged, $journey) {
            if(!isset($arranged[$journey['key']['ci']])) {
                $arranged[$journey['key']['ci']] = [];
            }

            array_push($arranged[$journey['key']['ci']], $journey);
            return $arranged;
        };
        $paxDateFormat = function ($pax) {
            if(empty($pax['birth_date'])) throw new \Exception(getErrorMessage('missingField'));

            $pax['birth_date'] = Formatter::date($pax['birth_date']);
            return $pax;
        };

        try {
            $request['booking_contacts'] = $this->addClientInfoToBkContacts($request['booking_contacts']);

            Validator::validateJSONKeys($request, array('pax_info'));
            if($perm_level === 0 && !isset($request['confirm'])) throw new \Exception(getErrorMessage('missingConfirmation'));
            $this->paymentValidation($request);

            $request['pax_info'] =          array_map($paxDateFormat, $request['pax_info']);
            $journeys =                     array_map($base64UnpackNArrange, $request['trip_info']);
            $arrangedJourneyByCredential =  array_reduce($journeys, $journeyByCredentialReducer, []);
            // Journey Arrange TripKeys
            $requests =                     array_map(function ($journeyByCredential) use ($request){
                $arrangedRequest = $request;
                $arrangedRequest['trip_info'] = $journeyByCredential;

                return $arrangedRequest;
            }, $arrangedJourneyByCredential);

            foreach ($requests as $request) {
                $bookingRes         = [];
                $getBookingRes      = [];
                $code               = $request['ws_op'];
                $journey            = $request['trip_info'];
                $credentialId       = $journey[0]['key']['ci'];
                $getBookingRequest  = [];
                $getBookingRes      = [];

                if($request['confirm'] === 0 && ((int)$perm_level) !== 0) throw new \Exception(getErrorMessage('holdNotPermitted'));
                if(empty($webService[$code][$credentialId])) throw new \Exception(getErrorMessage('wsNotFound'));

                $ws                 = $webService[$code][$credentialId];
                $request['code']    = $credentialId;

                $bookingRes = $ws->book($request);

                $getBookingRequest['ws_op'] = $request['ws_op'];
                $getBookingRequest['loc']   = $bookingRes['locator'];
                $getBookingRequest['code']  = $request['code'];

                $getBookingRes = $ws->getBooking($getBookingRequest);

                try {
                    $bookingToLog = $getBookingRes;
                    $bookingToLog['loc'] = $getBookingRes['locator'];
                    $errorSumming = false;
                    $errorSumming = $this->logBookingOnDatabase($credentialId, $bookingToLog);
                    if($errorSumming) $errorMessage = getErrorMessage('differenceBetweenTotalValue');
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                }

                if($request['confirm'] === 1) {
                    //Just confirm.
                    $confirmReq = [];

                    $confirmReq['loc'] = $bookingRes['locator'];
                    $confirmReq['payments'] = $request['payments'];
                    $getBookingRes = $ws->confirmBooking($confirmReq, $credentialId);
                }

                $this->logStatusOnBooking($getBookingRes, 'Confirmed');
                $getBookingRes = $this->applyOverAndDiscounts($getBookingRes, $getBookingRes['locator']);
                // print_r($getBookingRes);die();
                $getBookingRes['journeys'] = $this->unsertJourneysKeys($getBookingRes['journeys']);
                $getBookingRes['journeys'] = array_map(array($this, 'roundJourneyValues'), $getBookingRes['journeys']);
                $response[] = $getBookingRes;
            }
            Response::sendContent($response, $errorMessage);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    // public function addFee($request) {
    //     try {
    //         $airlineCod = $request['ws_op'];
    //         $ws = $this->webServices[$airlineCod];

    //         $response = $ws->addFee($request);
    //         Response::sendContent($response);
    //     } catch (\Exception $e) {
    //         Response::sendErrorMessage($e->getMessage());
    //     }
    // }

    public function divideBooking($request, $webService) {
        try {
            $code = $request['ws_op'];

            $credentalId = $this->getCredentialId($request, $webService);
            $ws = $webService[$code][$credentalId];
            $response = $ws->divideBooking($request);

            // $response['locator'] = 'RJ4JMD';
            $getBookingRequest['loc'] = $response['locator'];

            $booking = $ws->getBooking($getBookingRequest);
            $this->logDividedBookingOnDatabase($booking, $request['loc']);
            $booking = $this->applyOverAndDiscounts($booking, $booking['locator']);
            $booking['journeys'] = $this->unsertJourneysKeys($booking['journeys']);
            Response::sendContent($booking);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function divideAndCancelBooking($request, $webService) {
        try {
            $code = $request['ws_op'];

            $credentalId = $this->getCredentialId($request, $webService);
            $ws = $webService[$code][$credentalId];
            $response = $ws->divideAndCancelBooking($request);
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function getSeatAvailability($request, $webService) {
        $credentialId = null;
        $airCode = '';

        try {
            // if(!isset($request['code']) || $request['code'] == '') throw new \Exception('invalidCredentialCode');

            $credentialId = !isset($request['code']) ? $this->unoccultCredentialId($request['code']) : $this->getCredentialId($request, $webService);
            $airCode = $request['ws_op'];

            $ws = $webService[$airCode][$credentialId];
            $ws->getBooking($request);
            $response = $ws->getSeatAvailability($request);
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function seatAssign($request, $webService) {
        try {
            if(!isset($request['loc']) || isset($request['session_token']) && strlen($request['session_token']) <= 0) throw new \Exception(getErrorMessage('missingSessionToken'));

            // $NEEDED_SEGMENT_FIELDS = array(
            //     "from",
            //     "to",
            //     "dep_date",
            //     "comp_code",
            //     "flight_number",
            //     "seats"
            // );

            Validator::existValueOrError($request, "segments");

            $code = $request['ws_op'];
            $credentialId = $this->getCredentialId($request, $webService);
            $ws = $webService[$code][$credentialId];

            if(isset($request['loc']) || isset($request['session_token']) && strlen($request['session_token']) > 0) {
                $ws->getBooking($request);
            }else {
                $ws->saveSession(json_decode(base64_decode($request['session_token']), true));
            }

            $response = $ws->seatAssign($request);
            $response = $ws->getBooking($request);
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function ancillary($request, $webService) {
        $booking = [];

        try {
            if(empty($request['loc'])) throw new \Exception(getErrorMessage('missingSessionToken'));

            $code = $request['ws_op'];
            $credentialId = $this->getCredentialId($request, $webService);
            $ws = $webService[$code][$credentialId];

            $response = $ws->ancillary($request);
            $response = $ws->getBooking($request);
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }


    public function ancillaryPrice($request, $webService) {
        $booking    = [];
        $ws         = null;
        try {
            if(empty($request['ancillaries']) && empty($request['loc'])) throw new \Exception(getErrorMessage('incorretJSONStuct', 'Ancilares'));

            $code = $request['ws_op'];

            if(!empty($request['loc'])) {
                $credentialId   = $this->getCredentialId($request, $webService);
                $ws             = $webService[$code][$credentialId];
            }else {
                $keys   = array_keys($webService[$code]);
                $ws     = $webService[$code][$keys[0]];
            }

            $response = $ws->ancillaryPrice($request);

            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function confirmBooking($request, $webService) {
        $response = [];
        try {
            // throw new \Exception(getErrorMessage('feature_not_implemented'));
            $code = $request['ws_op'];

            $credentialId = (int)$this->getCredentialId($request, $webService);
            $ws = $webService[$code][$credentialId];

            $this->paymentValidation($request, true);

            $booking = $ws->confirmBooking($request, $credentialId);

            $booking = $this->applyOverAndDiscounts($booking, $booking['locator']);
            $booking['journeys'] = $this->unsertJourneysKeys($booking['journeys']);
            $booking['journeys'] = array_map(array($this, 'roundJourneyValues'), $booking['journeys']);

            $this->logStatusOnBooking($booking, 'Confirmed');
            $response[] = $booking;
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function getBooking($request, $webService) {
        $airCode = '';
        $credentialId = null;
        $errorMessage = '';
        $book = [];
        try {
            $airCode = $request['ws_op'];

            $credentialId = $this->getCredentialId($request, $webService);

            $ws = $webService[$airCode][$credentialId];
            $book = $ws->getBooking($request);
            try {
                $this->logBookingOnDatabase($credentialId, $book);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
            }
            $book = $this->applyOverAndDiscounts($book, $book['locator']);

            if(isset($book['journeys'])) $book['journeys'] = $this->unsertJourneysKeys($book['journeys']);
            if(isset($book['journeys'])) $book['journeys'] = array_map(array($this, 'roundJourneyValues'), $book['journeys']);
            if(isset($book['paxs'])) $book['paxs'] = array_map(array($this, 'roundPaxValues'), $book['paxs']);

            Response::sendContent($book, $errorMessage);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function cancelAncillaries(Array $request, $webService) {
        $response = [];
        try {
            $code = $request['ws_op'];
            $credentialId = $this->getCredentialId($request, $webService);
            $ws = $webService[$code][$credentialId];

            $response = $ws->cancelAncillaries($request);
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function cancelLoc($request, $webService) {
        $response = [];
        try {
            $test['locator'] = $request['loc'];
            $code = $request['ws_op'];
            $credentialId = $this->getCredentialId($request, $webService);
            $ws = $webService[$code][$credentialId];

            $response = $ws->cancelLoc($request);
            $this->logStatusOnBooking($test, 'Closed');
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    public function cancelSSR($request, $webService) {
        try {
            $code = $request['ws_op'];
            $credentialId = $this->getSBSCredentialId($request);
            $ws = $webService[$code][$credentialId];
            $ws->saveSession(json_decode(base64_decode($request['session_token']), true));

            $response = $ws->cancelSSR($request);
            Response::sendContent($response);
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }

    // public function bookingByKey($journeyKey) {
    //     foreach ($this->webServices as $key => $ws) {
    //         $logonRes = $ws->logon();

    //         $session = $ws->getSession($logonRes);
    //         $this->webServicesConstructor[$key]->sellWithKeyRequest($);
    //         $bookingRes = $ws->bookingByKey($session, $journeyKey);

    //         // print_r($searchRes);
    //     }
    // }

    // public function commitAsHold($request, $webService) {
    //     try {
    //         $code = $request['ws_op'];
    //         $credentialId = $this->getSBSCredentialId($request);
    //         $ws = $webService[$code][$credentialId];
    //         $ws->saveSession(json_decode(base64_decode($request['session_token']), true));
    //         $response = $ws->commitAsHold($request);
    //         Response::sendContent($response);
    //     } catch (\Exception $e) {
    //         Response::sendErrorMessage($e->getMessage());
    //     }
    // }

    public function clearSession($request, $webService) {
        try {
            $credentialId = $this->getSBSCredentialId($request);
            $ws = $webService[$request['ws_op']][$credentialId];
            $ws->saveSession(json_decode(base64_decode($request['session_token']), true));
            $ws->clearSession();
            Response::sendSuccessMessage(getSuccessApisMessage('sessionCleared'));
        } catch (\Exception $e) {
            Response::sendErrorMessage($e->getMessage());
        }
    }
}
