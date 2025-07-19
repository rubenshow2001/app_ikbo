<?php
// utils/Response.php

class Response {
    public static function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit();
    }

    public static function error($message, $status = 400) {
        self::json(['status' => 'error', 'message' => $message], $status);
    }

    public static function success($message, $data = [], $status = 200) {
        self::json(['status' => 'success', 'message' => $message, 'data' => $data], $status);
    }
}