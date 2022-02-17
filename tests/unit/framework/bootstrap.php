<?php namespace Tschallacka\StayLoggedIn\Tests\Unit\Framework;

use Exception;

/**
 * @return string
 * @throws Exception
 */
function getPathToMagentoRoot()
{
    $env_root = getenv('MAGENTO_ROOT');
    if($env_root !== false) {
        return $env_root;
    }
    return findRoot(__DIR__);
}

/**
 * @param $path The path to test wether it is a Magento root directory
 * @throws Exception when no Magento root was found and the root folder was encountered.
 * @return string Path to Magento root
 */
function findRoot($path): string
{
    if(validateDirectoryAsRootDir($path))
    {
        return realpath($path);
    }

    if(!isSystemRoot($path))
    {
        return findRoot($path . DIRECTORY_SEPARATOR . '..');
    }
}

/**
 * Test if the given path is the system's root directory
 * @throws Exception When a match with the systems root directory is found
 * @return bool false when it is not the system's root directory
 */
function isSystemRoot($path): bool
{
    if(realpath($path) == DIRECTORY_SEPARATOR) {
        throw new Exception("Cannot find Magento root in parent directories. Use the MAGENTO_ROOT " .
            "environment variable to set the path to the Magento root directory.");
    }
    return false;
}

/**
 * @param $path The path to validate
 * @return bool true when it's a a Magento root directory, false when not.
 */
function validateDirectoryAsRootDir($path): bool
{
    static $root_identity = [
        'app',
        'bin',
        'dev',
        'lib',
        'pub',
        'setup',
        'var',
        'vendor',
        'vendor/magento/framework',
    ];
    foreach($root_identity as $identity) {
        if(!is_dir($path . DIRECTORY_SEPARATOR . $identity)) {
            return false;
        }
    }
    return true;
}

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpIncludeInspection */
require_once(getPathToMagentoRoot() . '/dev/tests/unit/framework/bootstrap.php');
