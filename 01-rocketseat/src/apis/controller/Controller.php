<?php

namespace Api\Controller;

/*
    Create pattern to use in methods.
    Call Service.
    Arrange Data, to send to user and use in the system.
*/
abstract class Controller {
    protected $credential = [];
    public static $JWT;

    public function getCredentialUsername():String {
        return $this->credential['loginName'];
    }

    public function getCredentialLabel():String {
        return $this->credential['credential_label'] ? $this->credential['credential_label'] : '';
    }
}