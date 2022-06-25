<?php

namespace Api\Helpers;

use SimpleXMLElement;

class SabreUtil {
    private function errorFromResponse(SimpleXMLElement $xmlError, int $trys = 0) {
        $res = [];
        $condition = '';

        switch ($trys) {
            case 0:
                $condition = "//Error[@Type='SCHEDULES']";
                break;
            case 1:
                $condition = "//Error[@Type='ERR']";
                break;
            case 2:
                $condition = "//Error//Message";
                break;
            case 3:
                $condition = "//SystemSpecificResults//ErrorMessage";
                break;
            // case 4:
            //     $condition = "//DiagnosticData";
            //     break;
            default:
                throw new \Exception(getErrorMessage('wsNoErrorTreatmentFound'));
        }
        $res = $xmlError->xpath($condition);

        if(count($res) > 0) return $res[count($res) - 1];
        else return $this->errorFromResponse($xmlError, $trys + 1);
    }

    private function faultFromResponse(SimpleXMLElement $xmlError, int $trys = 0) {
        $res = [];
        $condition = '';

        switch ($trys) {
            case 0:
                $condition = "//Fault//StackTrace";
                break;
            default:
                throw new \Exception(getErrorMessage('wsNoErrorTreatmentFound'));
        }
        $res = $xmlError->xpath($condition);
        // var_dump($res);
        if(count($res) > 0) return $res[count($res) - 1];
        else return $this->faultFromResponse($xmlError, $trys + 1);
    }

    public function getErrorFromResponse(String $error) {
        // print_r($error);
        $error = preg_replace("/(<\/?)[\w-]+:([^>]*>)/", "$1$2$3", $error);
        $error = preg_replace('/xmlns[#="\/\.\w:-]*/', "", $error);                                 // Remove xmlns.
        $error = preg_replace('/<[\/]?SOAP|soap[-\w:]+>/', "", $error);                             // Remove SOAP prefix.
        // $error = preg_replace('/SOAP|soap[\-\w:="]+/', "", $error);                              // Remove SOAP attrs.
        $error = preg_replace('/[\w]+:/', "", $error);                                              // Remove prefixs.
        $error = preg_replace('/<\/?[A-Za-z]+:/', "<", $error);                                     // Replace tag prefix to no prefixed tag.
        $error = preg_replace('/timeStamp="[T\d\-:\.]+"/', "", $error);                             // Remove timeStamps.
        $error = preg_replace('/<Header>[\n\t =":<>\w\d\/\-=\\\.!]*<\/Header>/', "", $error);       // Remove Header.
        $errorElem = [];
        $msg = '';
        $xml = new SimpleXMLElement($error);

        if(count($xml->xpath('//Error')) > 0) $errorElem = $this->errorFromResponse($xml);
        else if(count($xml->xpath('//Fault')) > 0) $errorElem = $this->faultFromResponse($xml);
        // else if(count($xml->xpath('//DiagnosticData')) > 0) $errorElem = $this->errorFromResponse($xml);

        $errorElem = json_decode(json_encode($errorElem), true);
        $msg = count($errorElem) > 0 ? (isset($errorElem[0]) ? $errorElem[0] : $errorElem['@attributes']['ShortText']) : '';
 
        return $msg;
    }

}