<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 * Safe for cPanel/Apache deployment
 */

if (!function_exists('loadEnv')) {
    /**
     * Load environment variables from .env file
     * 
     * @param string|null $envFile Path to .env file (default: root/.env)
     * @return bool True if loaded successfully, false otherwise
     */
    function loadEnv($envFile = null) {
        if ($envFile === null) {
            // Try multiple possible paths
            $possiblePaths = [
                dirname(__DIR__) . '/.env',  // From config/ directory
                __DIR__ . '/../.env',        // Alternative path
                $_SERVER['DOCUMENT_ROOT'] . '/Bus_Booking/.env',  // From document root
                dirname($_SERVER['SCRIPT_FILENAME']) . '/.env',   // From script location
            ];
            
            $envFile = null;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $envFile = $path;
                    break;
                }
            }
            
            // If still not found, use default
            if ($envFile === null) {
                $envFile = dirname(__DIR__) . '/.env';
            }
        }
        
        if (!file_exists($envFile)) {
            // If .env doesn't exist, try .env.example
            $envExample = dirname($envFile) . '/.env.example';
            if (file_exists($envExample)) {
                // Copy .env.example to .env if it doesn't exist
                @copy($envExample, $envFile);
            } else {
                // Log warning but don't fail
                error_log("Warning: .env file not found at: $envFile");
                return false;
            }
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            return false;
        }
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Set environment variable if not already set
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get environment variable with default value
     * 
     * @param string $key Environment variable name
     * @param mixed $default Default value if not found
     * @return mixed Environment variable value or default
     */
    function env($key, $default = null) {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        
        // Convert numeric strings
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
}

// Auto-load .env file when this file is included
loadEnv();

