<?php

namespace Util;

class Validator {
    public static function existValueOrError($data, $required) {
        if (!empty($required) && empty($data) && $data !== 0) {
            if (gettype($data) == 'array') {
                throw new \Exception('Pelo menos um dos campos não foi informado!');
            }else{
                $result = getErrorMessage('missingField', $required);
                throw new \Exception($result);
            }
        }
    }

    public static function validKeyOrError($array, $key) {
        if(!isset($array[$key])) throw new \Exception(getErrorMessage('incorretJSONStuct', $key));
    }

    public static function validateJSONKeys(Array $JSONArray, Array $keys) {
        foreach ($keys as $key) {
            Validator::validKeyOrError($JSONArray, $key);
        }
    }

    public static function existValuesOrError(Array $array, Array $keysToError) {
        foreach ($keysToError as $key => $value) {
            Validator::existValueOrError($array[$key], $value);
        }
    }

    public static function phoneNumber($phoneNumber) {
        if(!preg_match("/^[0-9]{2}[0-9]{5}[0-9]{4}$/", $phoneNumber)) throw new \Exception(getErrorMessage('incorretPhoneNumberFormat'));
    }
}