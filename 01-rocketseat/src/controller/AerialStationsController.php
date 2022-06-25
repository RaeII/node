<?php
namespace Controller;

use \Service\AerialStationsService;

class AerialStationsController extends Controller {

    public function __construct() {
        $this->service = new AerialStationsService();
    }

    public function fetch() {
        try {
            $this->sendContent($this->service->fetch());
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function fetchLike($payload) {
        try {
            $this->sendContent($this->service->fetchLike($payload));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }
}
?>