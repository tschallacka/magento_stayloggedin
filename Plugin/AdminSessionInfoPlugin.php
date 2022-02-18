<?php

namespace Tschallacka\StayLoggedIn\Plugin;

use Magento\Framework\Session\Storage;
use Magento\Security\Model\AdminSessionInfo;

/**
 * @see AdminSessionInfo
 */
class AdminSessionInfoPlugin
{
    const STATUS_KEY = 'status';
    /** No actual type hints because of php 7.3 compatiblity with older versions. */
    /** @var Storage $storage */
    private $storage;

    public function __construct(
        Storage $storage
    )
    {
        $this->storage = $storage;
    }

    public function aroundSetData(AdminSessionInfo $orig, $call, $key, $value=null)
    {
        if($key === self::STATUS_KEY && $value !== AdminSessionInfo::LOGGED_IN) {
            if($this->storage->getData(BackendSessionPlugin::KEY)) {
                return $call(AdminSessionInfo::LOGGED_IN);
            }
        }
        return $call($value);
    }

    public function aroundGetData(AdminSessionInfo $orig, $call, $key = null)
    {
        if($key === self::STATUS_KEY) {
            if($this->storage->getData(BackendSessionPlugin::KEY)) {
                return AdminSessionInfo::LOGGED_IN;
            }
        }
        if(is_null($key)) {
            if($this->storage->getData(BackendSessionPlugin::KEY)) {
                $data = $call();
                $data[self::STATUS_KEY] = AdminSessionInfo::LOGGED_IN;
                return $data;
            }
        }
        return $call($key);
    }

    /**
     * @see AdminSessionInfo::isSessionExpired
     */
    public function afterIsSessionExpired(AdminSessionInfo $orig, $result)
    {
        if($result && $this->storage->getData(BackendSessionPlugin::KEY)) {
            return false;
        }
        return $result;
    }
}
