<?php

namespace Api\Interfaces;

interface AerialRequestMaker {
    /**
     * @param config Contains all needed sabre config data.
     */
    public function makeLogon(Array $credential, Array $config);
    /**
     * @param config Contains all needed sabre config data.
     */
    public function makeSearch(Array $segment, Array $paxs, Array $config);
}