<?php
namespace Controller;

use \Service\AccessLogService;

class AccessLogController extends Controller {

    public function __construct() {
        $this->service = new AccessLogService();
    }

    public function create($data) {
        try {
            $this->service->create($data);
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }
}
?>