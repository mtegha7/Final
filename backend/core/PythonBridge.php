<?php

class PythonBridge
{
    private static $pythonPath = 'python';

    public static function run($scriptName, $args = [])
    {
        $scriptPath = dirname(__DIR__, 2) . '/python/' . $scriptName;

        $escapedArgs = array_map('escapeshellarg', $args);

        $command = self::$pythonPath . " " .
            escapeshellarg($scriptPath) . " " .
            implode(" ", $escapedArgs) . " 2>&1";

        $output = shell_exec($command);

        //remove python warning and log noise
        $lines = explode("\n", trim($output));
        $jsonLine = trim(end($lines));

        return json_decode($jsonLine, true);
    }
}
