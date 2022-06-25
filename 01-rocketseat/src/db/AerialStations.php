<?php

namespace DataBase;

class AerialStations extends DataBase {
    public function fetch() {
        $query = "SELECT * FROM aerial_stations";

        return $this->select($query, 'mysql');
    }

    public function fetchLike(String $code) {
        $query = "SELECT * FROM aerial_stations WHERE iata = \"$code\" OR mac = \"$code\" OR remove_accent(name) LIKE \"%$code%\"";

        return $this->select($query, 'mysql');
    }
}