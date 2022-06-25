<?php
namespace Controller;

use \Service\PaymentFormService;

class PaymentFormController extends Controller {

    public function __construct() {
        $this->service = new PaymentFormService();
    }

    public function fetchByServiceCredential(Int $id, String $method) {
        try {
            $response = $this->service->fetchByServiceCredential($id, $method);
            $this->sendContent($response);
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }    
}
?>