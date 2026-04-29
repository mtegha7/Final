<?php

class Router
{

    private $routes = [];

    public function register($route, $file)
    {
        $this->routes[$route] = $file;
    }

    public function resolve($route)
    {

        if (!isset($this->routes[$route])) {
            http_response_code(404);
            echo json_encode(["error" => "Route not found"]);
            exit;
        }

        require_once $this->routes[$route];
    }
}
