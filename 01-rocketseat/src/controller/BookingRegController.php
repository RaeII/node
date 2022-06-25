<?php
namespace Controller;

use \Service\BookingRegService;

class BookingRegController extends Controller {

    public function __construct() {
        $this->service = new BookingRegService();
    }

    public function create(Array $booking, Int $clientId) {
        $bkgRegSrvc = new \Service\BookingRegMarkupsService();
        $clientSrvc = new \Service\ClientOutService();
        $markupAssocSrvc = new \Service\ClientAssocMarkupAssocCompanyOutService();
        $bkgRegDiscountSrvc = new \Service\BookingRegDiscountService();
        $bkgRegLocsSrvc = new \Service\BookingRegLocsService();
        $promotionalTaxsFilter = function ($tax) { 
            return (isset($tax['promotional_code']) && $tax['promotional_code'] !== "") ;
        };

        $client = $clientSrvc->fetch($clientId);
        $markups = $markupAssocSrvc->fetchByClient($clientId);

        $toInsert['fare_equal_net'] = $client['fareEqualNet'];
        $toInsert['apply_promo_code'] = $client['applyPromoCode'];
        $toInsert['apply_promo_code_repass'] = $client['applyPromoCodeRepass'];
        $toInsert['promo_code_value_repass'] = $client['promoCodeRepassValue'];
        $toInsert['apply_markup'] = $client['applyMarkup'];

        $this->service->startTransaction('mysql');
        $bkgRegId = $this->service->create($toInsert);

        $toInsert = [];

        $toInsert['locator'] = $booking['locator'];
        $toInsert['description'] = NULL;
        $bkgRegLocsSrvc->create($toInsert, $bkgRegId);

        if($client['applyMarkup'] == 1 && $client['fareEqualNet'] == 0) {
            foreach ($markups as $markup) {
                $bkgRegSrvc->create($markup, $bkgRegId);
            }
        }

        // $taxs = [];
        // foreach ($booking['journeys'] as $journey) {
        //     $tariffsWithPromotion = array_filter($journey['fares'], $promotionalTaxsFilter);

        //     if(count($tariffsWithPromotion) > 0) {
        //         // $tariffsWithPromotion[0]['paxs_fare'][0];
        //         $taxs = array_reduce($tariffWithPromotion['paxs_fare'], function ($acc, $promotional) {print_r($promotional);die();
        //             $arrangedTax['value'] = $promotional['promotional'];
        //             $arrangedTax['code'] = $promotional['promotional_code'];
        //             $arrangedTax['pax_id'] = -1;
        //             $acc[] = $arrangedTax;
        //             return $acc;
        //         }, $taxs);
        //     }

        // }

        // foreach ($taxs as $tax) {
        //     $bkgRegDiscountSrvc->create($tax, $bkgRegId);
        // }
        $this->service->commit('mysql');
    }
}
?>