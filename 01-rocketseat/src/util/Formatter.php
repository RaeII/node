<?php
namespace Util;

class Formatter {
    public static function money(float $value) {
        return number_format($value, 2, ',', '.');
    }

    public static function soapToArray(String $soap, String $splitOn = null) {
        $res = preg_replace("/(<\/?)[\w-]+:([^>]*>)/", "$1$2$3", $soap);

        $xml = new \SimpleXMLElement($res);

        if($splitOn) {
            $content = $xml->xpath("//$splitOn")[0];
        }else {
            $content = $xml->xpath('//Body')[0];
        }


        $array = json_decode(json_encode($content), true);
        return $array;
    }

    public static function date(String $date) {
        if(str_contains($date, '/')) {
            $date = str_replace('/', '-', $date);
        }

        return date('Y-m-d', strtotime($date));
    }
}