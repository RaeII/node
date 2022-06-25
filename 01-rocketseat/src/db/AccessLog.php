<?php

namespace DataBase;

class AccessLog extends DataBase {
    public function create(Array $data) {
        $query = "INSERT INTO access_log (ip_address, uri, payload, xff, request_method, request_date) 
                                  values ('{$data['ip']}', '{$data['uri']}', :payload, '{$data['xff']}', '{$data['method']}', now())";

        $payload = json_encode($data['payload']);

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':payload', $payload);
        return $this->insertPreparedQuery('mysql');
    }
}