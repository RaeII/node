<?php
namespace Middleware\Auth;

class UserLevel {
    // function __construct() {
    // }

    private static function userOnePermitedRoutes($route) {
        switch ($route[0]) {
            case 'apis':
                break;
            default:
                throw new \Exception(getErrorMessage('userLevelNotPermitted'));       
        }
    }

    private static function userTwoPermitedRoutes($route) {
        throw new \Exception(getErrorMessage('userLevelNotImplemented'));
    }

    private static function userThreePermitedRoutes($route) {
        if($route[0] != 'apis') throw new \Exception(getErrorMessage('userLevelNotPermitted'));
    }

    public static function validate($level, $route) {
        switch ($level) {
            // Super
            case 0:
                // Can do anything.
                break;
            // Admin
            case 1:
                self::userOnePermitedRoutes($route);
                break;
            // Every STMB Methods
            case 2:
                self::userTwoPermitedRoutes($route);
                break;
            // Every APIS Methods
            case 3:
                self::userThreePermitedRoutes($route);
                break;
            default:
                throw new \Exception(getErrorMessage('userLevelNotFound'));
        }
    }
}

