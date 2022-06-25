<?php
require_once 'src/vendor/autoload.php';

use \PlugRoute\PlugRoute;
use \PlugRoute\RouteContainer;
use \PlugRoute\Http\RequestCreator;
use \PlugRoute\Http\Request;
use \Middleware\Auth\JWT;
use \Middleware\Auth\UserLevel;
use \Util\Response;

$route = new PlugRoute(new RouteContainer(), RequestCreator::create());

function validateUserLevel($url, $JWT) {
    $userAccController = new Service\UserAccountService();

    $response = $userAccController->fetchById($JWT->getId());
    $_SESSION['permission_level'] = $response['permission_level'];
    UserLevel::validate($response['permission_level'], $url);
}

function validateAndSave(Array $header, String $url) {
    // Url Handler
    $urlByParts = explode('/', $url);
    array_splice($urlByParts, 0, 1);

    // if(DEV_MODE && MODE_LEVEL == 0) {
    //     return;
    // }

    if(!isset($header['HTTP_AUTHORIZATION']) || $header['HTTP_AUTHORIZATION'] == '') throw new \Exception(getErrorMessage('missingJWTToken'));

    if(!str_contains($header['HTTP_AUTHORIZATION'], 'Bearer')) {
        throw new \Exception(getErrorMessage('wrongJWTSignature'));
    }
    $authKey = explode('Bearer ', $header['HTTP_AUTHORIZATION'])[1];

    // Check acessing source to save in respective Controller the JWT.
    // Then validate if user has permission to access that route.
    if($urlByParts[0] == 'apis') {
        \Api\Controller\Controller::$JWT = JWT::decodeAndValidate($authKey);
        validateUserLevel($urlByParts, \Api\Controller\Controller::$JWT);
    }else {
        \Controller\Controller::$JWT = JWT::decodeAndValidate($authKey);
        validateUserLevel($urlByParts, \Controller\Controller::$JWT);
    }

    // = JWT::decodeAndValidate($authKey);
}

function validateRequest(Array $request, String $url) {
    $urlByParts = explode('/', $url);
    array_splice($urlByParts, 0, 1);

    if($urlByParts[0] == 'apis') {
        if(!isset($request['ws_op'])) throw new \Exception(getErrorMessage('wsNotInformed'));
    }
}

function logAction() {
    $service = new \Service\AccessLogService();
    $content = [];

    $ip = $_SERVER['REMOTE_ADDR'];
    $uri = $_SERVER['REQUEST_URI'];
    $payload = file_get_contents('php://input');;
    $method = $_SERVER['REQUEST_METHOD'];
    $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : NULL;
    $xff = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : NULL;

    $content['ip'] = $ip;
    $content['uri'] = $uri;
    $content['payload'] = $payload;
    $content['method'] = $method;
    $content['auth'] = $auth;
    $content['xff'] = $xff;

    $service->create($content);
}

try {
    $route->post('/validate_login', function (Request $request) {
        $loginController = new \Controller\LoginController();

        $loginController->validateLogin($request->headers());
    });

    $route->group(['prefix' => '/stmb'], function ($route) {
        $route->post('/login', function (Request $request) {
            $loginController = new \Controller\LoginController();
            logAction();

            $loginController->login($request->all());

        });

        // User Handler
        $route->group(['prefix' => '/user'], function ($route) {
            // $route->post('/create', function (\Front\Controller\ApiController $apiController, Request $request) {
            //     validateAndSave($request->headers(), $request->getUrl());

            //     $apiController->createApi($request->all());
            // });

            // $route->delete('/{id}/assoc/client/{client_id}', function (\Front\Controller\ApiController $apiController, Request $request) {
            //     validateAndSave($request->headers(), $request->getUrl());

            //     $apiController->removeAssoc($request->parameter('id'), $request->parameter('client_id'));
            // });

            // // $route->delete('/{id}', function (\Front\Controller\ApiController $apiController, Request $request) {
            // //     validateAndSave($request->headers(), $request->getUrl());

            // //     $apiController->removeApi($request->parameter('id'));
            // // });

            // $route->put('/{id}', function (\Front\Controller\ApiController $apiController, Request $request) {
            //     validateAndSave($request->headers(), $request->getUrl());

            //     $apiController->update($request->all(), $request->parameter('id'));
            // });

            // $route->post('/{id}/assoc/client/{client_id}', function (\Front\Controller\ApiController $apiController, Request $request) {
            //     validateAndSave($request->headers(), $request->getUrl());

            //     $apiController->addAssoc($request->parameter('id'), $request->parameter('client_id'));
            // });

            $route->get('/{id}', function (\Controller\UserAccountController $userController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $userController->getById($request->parameter('id'));
            });

            // $route->get('', function (\Front\Controller\ApiController $apiController, Request $request) {
            //     validateAndSave($request->headers(), $request->getUrl());

            //     $apiController->getApis();
            // });
        });

        // Apis Handler
        $route->group(['prefix' => '/api'], function ($route) {
            $route->post('/create', function (\Front\Controller\ApiController $apiController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $apiController->createApi($request->all());
            });

            $route->delete('/{id}/assoc/client/{client_id}', function (\Front\Controller\ApiController $apiController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $apiController->removeAssoc($request->parameter('id'), $request->parameter('client_id'));
            });

            // $route->delete('/{id}', function (\Front\Controller\ApiController $apiController, Request $request) {
            //     validateAndSave($request->headers(), $request->getUrl());

            //     $apiController->removeApi($request->parameter('id'));
            // });

            $route->put('/{id}', function (\Front\Controller\ApiController $apiController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $apiController->update($request->all(), $request->parameter('id'));
            });

            $route->post('/{id}/assoc/client/{client_id}', function (\Front\Controller\ApiController $apiController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $apiController->addAssoc($request->parameter('id'), $request->parameter('client_id'));
            });

            $route->get('', function (\Front\Controller\ApiController $apiController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $apiController->getApis();
            });
        });

        // Client Assoc Promo code Handler
        $route->group(['prefix' => '/client_assoc_promo_code'], function ($route) {
            $route->post('/create', function (\Front\Controller\ClientAssocPromoCodeController $capcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $capcController->create($request->all());
            });

            $route->delete('/{id}', function (\Front\Controller\ClientAssocPromoCodeController $capcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $capcController->remove($request->parameter("id"));
            });

            $route->put('/{id}', function (\Front\Controller\ClientAssocPromoCodeController $capcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $capcController->update($request->all(), $request->parameter("id"));
            });

            $route->get('/{id}', function (\Front\Controller\ClientAssocPromoCodeController $capcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $capcController->fetch($request->parameter("id"));
            });

            $route->get('', function (\Front\Controller\ClientAssocPromoCodeController $capcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $capcController->fetchAll();
            });
        });

        // Promo code Handler
        $route->group(['prefix' => '/promo_code'], function ($route) {
            $route->post('/create', function (\Front\Controller\PromoCodeController $pcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $pcController->create($request->all());
            });

            $route->delete('/{id}', function (\Front\Controller\PromoCodeController $pcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $pcController->remove($request->parameter("id"));
            });

            $route->put('/{id}', function (\Front\Controller\PromoCodeController $pcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $pcController->update($request->all(), $request->parameter("id"));
            });

            $route->get('/{id}', function (\Front\Controller\PromoCodeController $pcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $pcController->fetch($request->parameter("id"));
            });

            $route->get('', function (\Front\Controller\PromoCodeController $pcController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $pcController->fetchAll();
            });
        });

        // Clients Handler
        $route->group(['prefix' => '/client'], function ($route) {
            $route->post('/create', function (\Front\Controller\ClientController $clientController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $clientController->createClient($request->all());
            });

            $route->delete('/{id}', function (\Front\Controller\ClientController $clientController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $clientController->removeClient($request->parameter("id"));
            });

            $route->put('/{id}', function (\Front\Controller\ClientController $clientController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $clientController->updateClient($request->all(), $request->parameter("id"));
            });

            $route->get('/{id}', function (\Front\Controller\ClientController $clientController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $clientController->getClient($request->parameter("id"));
            });

            $route->get('', function (\Front\Controller\ClientController $clientController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $clientController->getClients();
            });
        });

        // Company Handler
        $route->group(['prefix' => '/company'], function ($route) {
            $route->get('/{id}', function (\Front\Controller\CompanyController $companyController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $companyController->getCompany($request->parameter("id"));
            });

            $route->get('/', function (\Front\Controller\CompanyController $companyController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $companyController->getCompanys();
            });
        });

        // Client Assoc Markup Assoc Company Handler
        $route->group(['prefix' => '/client_assoc_markup_assoc_company'], function ($route) {
            $route->delete('/{id}', function (\Front\Controller\ClientAssocMarkupAssocCompanyController $controller, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $controller->remove($request->parameter("id"));
            });

            $route->post('/', function (\Front\Controller\ClientAssocMarkupAssocCompanyController $controller, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $controller->create($request->all());
            });
        });

        // Markup Handler
        $route->group(['prefix' => '/markup'], function ($route) {
            // $route->post('/{id}/markup_assoc/{id_client}', function (\Front\Controller\MarkupController $markupController, Request $request) {
            //     validateAndSave($request->headers());

            //     $markupController->addMarkupAssoc($request->parameter("id"), $request->parameter("id_client"));
            // });

            // $route->post('/{id}/assoc_company/{id_company}', function (\Front\Controller\MarkupController $markupController, Request $request) {
            //     validateAndSave($request->headers());

            //     $markupController->addAssocCompany($request->parameter("id"), $request->parameter("id_company"));
            // });

            $route->post('/create', function (\Front\Controller\MarkupController $markupController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $markupController->addMarkup($request->all());
            });

            // $route->delete('/{id}/markup_assoc/{id_assoc}', function (\Front\Controller\MarkupController $markupController, Request $request) {
            //     validateAndSave($request->headers());

            //     $markupController->removeMarkupAssoc($request->parameter("id_assoc"));
            // });

            $route->delete('/{id}', function (\Front\Controller\MarkupController $markupController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $markupController->removeMarkup($request->parameter("id"));
            });

            $route->put('/{id}', function (\Front\Controller\MarkupController $markupController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $markupController->update($request->all(), $request->parameter("id"));
            });

            $route->get('/{id}', function (\Front\Controller\MarkupController $markupController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $markupController->fetch($request->parameter("id"));
            });

            $route->get('', function (\Front\Controller\MarkupController $markupController, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $markupController->fetchAll();
            });
        });

        $route->group(['prefix' => '/aerial_stations'], function ($route) {
            $route->get('/like/{code}', function (\Controller\AerialStationsController $controller, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $controller->fetchLike($request->parameter('code'));
            });

            $route->get('/', function (\Controller\AerialStationsController $controller, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $controller->fetch();
            });
        });

        $route->group(['prefix' => '/fare_rules'], function ($route) {
            $route->get('/', function (\Controller\FareRuleController $controller, Request $request) {
                validateAndSave($request->headers(), $request->getUrl());

                $controller->fetchAll();
            });
        });
    });

    $route->group(['prefix' => '/apis'], function ($route) {
        $route->post('/login', function (Request $request) {
            $loginController = new \Controller\LoginController();
            $loginController->login($request->all(), $request->getUrl());

            logAction();
        });

        $route->post('/booking', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            logAction();
            $arCall = new \Api\AerialCall();
            $arCall->booking($request->all());
        });

        // $route->post('/price_itinerary', function (Request $request) {
        //     validateAndSave($request->headers(), $request->getUrl());

        //     $arCall = new \Api\AerialCall(array($request->all()['ws_op']));
        //     $arCall->priceItinerary($request->all());
        // });

        $route->post('/clear', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->clearSession($request->all());
        });

        $route->post('/seat_assign', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->seatAssign($request->all());
        });

        $route->post('/confirm_booking', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            logAction();
            $arCall = new \Api\AerialCall();
            $arCall->confirmBooking($request->all());
        });

        $route->post('/search', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->search($request->all());
        });

        $route->post('/commit_as_hold', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            logAction();
            $arCall = new \Api\AerialCall();
            $arCall->commitAsHold($request->all());
        });

        $route->post('/seat_availability', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            logAction();
            $arCall = new \Api\AerialCall();
            $arCall->getSeatAvailability($request->all());
        });

        $route->post('/ancillary/price', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->ancillaryPrice($request->all());
        });

        $route->post('/ancillary', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->ancillary($request->all());
        });

        $route->post('/getBooking', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->getBooking($request->all());
        });

        $route->put('/divide_and_cancel_booking', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->divideAndCancelBooking($request->all());
        });

        $route->put('/divide_booking', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            logAction();
            $arCall = new \Api\AerialCall();
            $arCall->divideBooking($request->all());
        });

        $route->delete('/cancel_ancillary', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());

            logAction();
            $arCall = new \Api\AerialCall();
            $arCall->cancelAncillaries($request->all());
        });

        $route->delete('/cancel_booking', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());

            logAction();
            $arCall = new \Api\AerialCall();
            $arCall->cancelLoc($request->all());
        });

        $route->delete('/cancel_ssr', function (Request $request) {
            validateAndSave($request->headers(), $request->getUrl());
            validateRequest($request->all(), $request->getUrl());

            $arCall = new \Api\AerialCall();
            $arCall->cancelSSR($request->all());
        });
    });

    $route->on();
} catch (\Exception $e) {
    Response::sendErrorMessage($e->getMessage());
}
