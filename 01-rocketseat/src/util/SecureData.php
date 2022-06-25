<?php

namespace Util;

use \Util\Validator;

class SecureData {

    private static function securer($data, $secureType) {
        switch ($secureType) {
            case 'all':
                $data = addslashes(strip_tags(trim($data))); //executa todas as verificações abaixo com excessão do email
                break;
            case 'script':
                $data = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $data); //remove tags scripts
                break;
            case 'html':
                $data = strip_tags($data); //remove tags html
                break;
            case 'strings':
                $data = trim($data); //remove espaços no começo e no final das strings
                break;
            default:
                throw new \Exception(getErrorMessage('securerDataTypeNotFound'));
        }
        return $data;
    }

    public static function secure($data,  $required = null, $secureType = 'all') {
        try {
            Validator::existValueOrError($data, $required);
            if (gettype($data) == 'array') {
                foreach($data as $key => $value) {
                    $data[$key] = SecureData::secure($value, $required, $secureType);
                }
            }elseif (gettype($data) == 'string') {
                $data = SecureData::securer($data, $secureType);
            }else if (gettype($data) == 'integer' || gettype($data) == 'boolean' || gettype($data) == 'double') {
                return $data;
            }
            else {
                throw new \Exception(getErrorMessage('dataTypeNotFound'));
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $data;
    }

    public static function encryptData($data) {
        $cipher =  'AES-256-CBC';

        // $Chave =  random_bytes(32);
        $IV = getenv('IV'); 
        $key = getenv('enc_secret_key');

        $cipherText = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $IV);
        $result = base64_encode($IV.$cipherText);

        return $result;
    }

    public static function decryptData($data) {
        $cipher =  'AES-256-CBC';

        // $Chave =  random_bytes(32);
        $IV = getenv('IV'); 
        $key = getenv('enc_secret_key');

        $result = base64_decode($data);

        $cipherText = mb_substr($result, openssl_cipher_iv_length($cipher), null, '8bit');

        //$Chave = pack('H*', 'be3494ff4904fd83bf78e3cec0d38ddbf48d0a6a666be05420667a5a7d2c4e0d');
        $IV = mb_substr($result, 0, openssl_cipher_iv_length($cipher), '8bit');

        $clearText = openssl_decrypt($cipherText, $cipher, $key, OPENSSL_RAW_DATA, $IV);
        return $clearText;
    }
    // private static function validator($data, $validateType) {
    //     switch ($validateType) {
    //         case 'email':
    //             $data = filter_var($data, FILTER_VALIDATE_EMAIL); //valida um email
    //             if (empty($data) && !empty($required)) {
    //                 throw new \Exception('O E-mail informado é inválido!', 1);
    //             }
    //             break;
    //         default:
    //             throw new \Exception(getErrorMessage('validatorTypeNotFound'));
    //     }
    //     return true;
    // }

    // public static function validate($data, $required = null, $validateType = 'all') {
        
    //     try {
    //         SecureData::existValueOrError($data, $required);
    //         if (gettype($data) == 'array') {
    //             foreach ($data as $key => $value) {
    //                 SecureData::validator($value);
    //             }
    //         }elseif (gettype($data) == 'string') {
    //             SecureData::validator($data);
    //         }else {
    //             throw new \Exception(getErrorMessage('dataTypeNotFound'));
    //         }
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    //     return true;
    // }
}