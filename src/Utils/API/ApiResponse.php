<?php

namespace App\Utils\API;

/**
 * Custom API response for system apis return resepone
 *
 */
class ApiResponse {

    protected $status;
    protected $data;
    protected $error;

    public function __construct($status, $data = null, $error = null) {

        $this->setStatus($status)
                ->setData($data)
                ->setError($error);
    }

    function getStatus() {
        return $this->status;
    }

    function getData() {
        return $this->data;
    }

    function getError() {
        return $this->error;
    }

    function setStatus($status) {
        $this->status = preg_match('/^2..$/', $status) ? TRUE : FALSE;
        return $this;
    }

    function setData($data) {
        $this->data = $data;
        return $this;
    }

    function setError($error) {

        if (empty($error)) {
            $this->error = null;
        } elseif (is_string($error)) {
            $this->error = ['default' => $error];
        } else {
            $this->error = $error;
        }
        return $this;
    }

}
