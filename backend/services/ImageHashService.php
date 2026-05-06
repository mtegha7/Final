<?php

class ImageHashService
{
    /**
     * Generates a perceptual hash of the given image via Python.
     *
     * Returns the hash string on success, or throws a RuntimeException
     * with a descriptive message so the caller can handle it cleanly.
     *
     * @param  string $imagePath  Absolute path to the image file.
     * @return string             Hex perceptual hash string.
     * @throws RuntimeException   If Python fails or returns an error sentinel.
     */
    public static function generateHash(string $imagePath): string
    {
        if (!file_exists($imagePath)) {
            throw new \RuntimeException("ImageHashService: image not found at {$imagePath}");
        }

        $pythonPath = self::resolvePython();

        // FIX: original path was dirname(__DIR__, 2) . '/python/image_hash.py'
        // Adjusted to match actual project layout: /python/ lives two levels above
        // the services/ directory (i.e. project root /python/).
        $scriptPath = escapeshellarg(dirname(__DIR__, 2) . '/python/image_hash.py');
        $arg        = escapeshellarg($imagePath);

        // Capture both stdout and stderr so we can surface Python errors
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $proc = proc_open("{$pythonPath} {$scriptPath} {$arg}", $descriptors, $pipes);

        if (!is_resource($proc)) {
            throw new \RuntimeException("ImageHashService: failed to start Python process");
        }

        fclose($pipes[0]);
        $stdout = trim(stream_get_contents($pipes[1]));
        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0 || $stdout === '' || $stdout === 'HASH_ERROR') {
            $detail = $stderr ?: $stdout ?: 'no output';
            throw new \RuntimeException("ImageHashService: Python error — {$detail}");
        }

        return $stdout;
    }

    /**
     * Resolves the Python 3 executable path.
     * Tries 'python3' first, falls back to 'python' for Windows environments.
     */
    private static function resolvePython(): string
    {
        // Check if python3 is available
        $test = shell_exec('python3 --version 2>&1');
        if ($test && stripos($test, 'python') !== false) {
            return 'python3';
        }

        $test = shell_exec('python --version 2>&1');
        if ($test && stripos($test, 'python 3') !== false) {
            return 'python';
        }

        throw new \RuntimeException("ImageHashService: Python 3 not found on this server. Install it or set the full path.");
    }

    /**
     * Checks if two hashes are perceptually similar (likely duplicate images).
     * Hamming distance <= 10 out of 256 bits is a standard duplicate threshold.
     *
     * @param  string $hashA  Hex hash string.
     * @param  string $hashB  Hex hash string.
     * @return bool           True if the images are likely duplicates.
     */
    public static function areDuplicates(string $hashA, string $hashB): bool
    {
        if ($hashA === '' || $hashB === '' || strlen($hashA) !== strlen($hashB)) {
            return false;
        }

        $distance = 0;
        $lenA = strlen($hashA);

        for ($i = 0; $i < $lenA; $i++) {
            $xor = hexdec($hashA[$i]) ^ hexdec($hashB[$i]);
            // Count set bits (population count) in the 4-bit nibble
            $distance += substr_count(decbin($xor), '1');
        }

        return $distance <= 10;
    }
}
