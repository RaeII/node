<?php
namespace Controller;

use \Service\FareRuleService;

class FareRuleController extends Controller {

    public function __construct() {
        $this->service = new FareRuleService();
    }

    public function fetchAll() {
        $response = $this->service->fetchAll();
        $this->sendContent($response);
    }
}
?>