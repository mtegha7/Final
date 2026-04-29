<?php

class Response
{

    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function success($data = [], $message = "Success")
    {
        self::json([
            "status" => "success",
            "message" => $message,
            "data" => $data
        ]);
    }

    public static function error($message = "Error", $status = 400)
    {
        self::json([
            "status" => "error",
            "message" => $message
        ], $status);
    }
}
