<?php

namespace Api\Factory;

class AerialControllerFactory {
    public function getController($wsName, $credentialId) {
        switch ($wsName) {
            case 'AD':
                return new \Api\Controller\AzulController($credentialId);
            case 'LA':
                return new \Api\Controller\LatamController($credentialId);
            case 'G3':
                return new \Api\Controller\GolController($credentialId);
            default:
                throw new \Exception(getErrorMessage('wsNotFound'));
                break;
        }
    }
}