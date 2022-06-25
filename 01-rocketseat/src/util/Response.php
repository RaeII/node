<?php
namespace Util;

/*
    -Cria a resposta em json para o frontend tendo os parametros... 
    -Content: É substituido no html do front. 
    -Message: Sera qualquer mensagem de resposta de operações.
    -Code: Ira validar no front se foi um sucesso ou ocorrou falha a operação.
*/
class Response {

    const OK = 1;
    const ERROR = 0;
    const WARNING = 2;
    
    private $datas = [];
    private $code;
    private $json;

    public static function load($code) {      
        $that = new static;
        $that->code = $code;
        $that->json = array();
        $that->json['code'] = $code;

        header('Content-Type: application/json');
        return $that;
    }

    public function setContent($res = null, $typeError = -1) {
        $this->json["content"] = $res;
        return $this;
    }

    public function setMessage($msg = null) {
        $this->json["message"] = $msg;
        return $this;
    }

    /*
        900 - Bill && Confirmation
        
    */
    public function setErrorCode($code) {
        $this->json["error_code"] = $code;
        return $this;
    }

    public function addData($name, $values) {
        $this->datas[$name] = $values;
        return $this;
    }

    public function addNamedDatas($values) {
        foreach ($values as $key => $value) {
            $this->datas[$key] = $value;
        }
        return $this;
    }

    public function response() {
        if(count($this->datas) > 0) {
            $this->json["extra_datas"] = $this->datas;
        }
        echo json_encode($this->json);
        die();
    }

    public static function sendContent($content, $message = '') {
        echo self::load(Response::OK)
            ->setMessage($message)
            ->setContent($content)
            ->response();
    }

    public static function sendSuccessMessage($message) {
        echo self::load(Response::OK)
        ->setMessage($message)
        ->response();
    }

    public static function sendErrorMessage($message) {
        echo self::load(Response::ERROR)
        ->setMessage($message)
        ->response();
    }

    public static function sendWarningMessage($message) {
        echo self::load(Response::WARNING)
        ->setMessage($message)
        ->response();
    }
}
