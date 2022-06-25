<?php
namespace Controller;

use \Service\UserAccountService;
use \Middleware\Auth\JWT;

class UserAccountController extends Controller {

    public function __construct() {
        $this->service = new UserAccountService();
    }

    public function getById(Int $id) {
        try {
            $response = $this->service->fetchById($id);
            $this->sendContent($response);
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }    
}
?>