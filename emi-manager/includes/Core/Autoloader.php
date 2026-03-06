<?php
/**
 * PSR-4 style autoloader for the EmiManager namespace.
 *
 * @package EmiManager\Core
 */

namespace EmiManager\Core;

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Autoloader
 *
 * Maps the EmiManager namespace to the includes/ directory.
 */
class Autoloader
{

    /**
     * Register the autoloader with spl_autoload_register.
     *
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    /**
     * Autoload a class file.
     *
     * @param string $class Fully-qualified class name.
     * @return void
     */
    public static function autoload(string $class): void
    {
        $prefix = 'EmiManager\\';

        // Bail if the class does not belong to our namespace.
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        // Strip the namespace prefix.
        $relative_class = substr($class, strlen($prefix));

        // Convert namespace separators to directory separators.
        // Use rtrim on EMI_MANAGER_PATH to prevent double slashes.
        $file = rtrim(EMI_MANAGER_PATH, '/\\') . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
        else {
            // Fallback for case-sensitivity issues on some environments.
            $file_lower = strtolower($file);
            if (file_exists($file_lower)) {
                require_once $file_lower;
            }
        }
    }
}
