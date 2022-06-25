<?php
namespace Controller;

use \Service\UserAccountService;
use \Middleware\Auth\JWT;

class LoginController extends Controller {

    public function __construct() {
        $this->service = new UserAccountService();
    }

    public function login(Array $bodyContent) {
        try {
            $response = $this->service->login($bodyContent);

            $_SESSION['permission_level'] = $response['permission_level'];

            $jwt = new JWT($response['name'], $response['user_id'], $response['clientId']);
            $token = $jwt->generate();
            $token = array('user_id' => $response['user_id'], 'token' => $token);

            $this->sendContent($token);
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function validateLogin(Array $header) {
        try {
            if(!isset($header['HTTP_AUTHORIZATION']) || $header['HTTP_AUTHORIZATION'] == '') throw new \Exception(getErrorMessage('missingJWTToken'));

            if(!str_contains($header['HTTP_AUTHORIZATION'], 'Bearer')) {
                throw new \Exception(getErrorMessage('wrongJWTSignature'));
            }
            $authKey = explode('Bearer ', $header['HTTP_AUTHORIZATION'])[1];
        
            // Check acessing source to save in respective Controller the JWT.
            // Then validate if user has permission to access that route.
    
            JWT::decodeAndValidate($authKey);
            $this->sendContent(json_decode('{"validation_code": 1, "validation_message": "Token valido."}'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }
}
?>