<?php
/**
 * Configuration File
 * Loads environment variables from .env file
 */

class Config {
    private static $config = [];
    
    /**
     * Load environment variables from .env file
     */
    public static function load() {
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            die('Error: .env file not found. Please copy .env.example to .env and configure it.');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Store in config array
                self::$config[$key] = $value;
                
                // Also set as environment variable
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
    
    /**
     * Get configuration value
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Get database configuration
     * @return array
     */
    public static function database() {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'name' => self::get('DB_NAME', 'ams_database'),
            'username' => self::get('DB_USERNAME', 'root'),
            'password' => self::get('DB_PASSWORD', '')
        ];
    }
    
    /**
     * Get application name
     * @return string
     */
    public static function appName() {
        return self::get('APP_NAME', 'Asset Management System');
    }
    
    /**
     * Check if debug mode is enabled
     * @return bool
     */
    public static function isDebug() {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
    
    /**
     * Get session lifetime in seconds
     * @return int
     */
    public static function sessionLifetime() {
        return (int) self::get('SESSION_LIFETIME', 7200);
    }
    
    /**
     * Get minimum password length
     * @return int
     */
    public static function passwordMinLength() {
        return (int) self::get('PASSWORD_MIN_LENGTH', 8);
    }
    
    /**
     * Get mail configuration
     * @return array
     */
    public static function mail() {
        return [
            'host' => self::get('MAIL_HOST'),
            'port' => self::get('MAIL_PORT'),
            'username' => self::get('MAIL_USERNAME'),
            'password' => self::get('MAIL_PASSWORD'),
            'from_address' => self::get('MAIL_FROM_ADDRESS'),
            'from_name' => self::get('MAIL_FROM_NAME')
        ];
    }
}

// Auto-load configuration on include
Config::load();
?>
