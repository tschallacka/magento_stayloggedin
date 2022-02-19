<?php

namespace Tschallacka\StayLoggedIn\Plugin;

use Magento\Framework\Session\Storage;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Security\Model\AdminSessionInfo;

/**
 * @see AdminSessionInfo
 */
class AdminSessionInfoPlugin
{
    const STATUS_KEY = 'status';
    const UPDATED_AT_KEY = 'updated_at';
    /** No actual type hints because of php 7.3 compatiblity with older versions. */
    /** @var DateTime $storage */
    private $dateTime;
    /** No actual type hints because of php 7.3 compatiblity with older versions. */
    /** @var Storage $storage */
    private $storage;

    public function __construct(
        Storage $storage,
        DateTime $dateTime
    )
    {
        $this->storage = $storage;
        $this->dateTime = $dateTime;
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
        $user = $this->storage->getData(BackendSessionPlugin::KEY);
        if($key === self::STATUS_KEY) {
            if ($user) {
                return AdminSessionInfo::LOGGED_IN;
            }
        } elseif ($key === self::UPDATED_AT_KEY) {
            if ($user) {
                return $this->dateTime->gmtTimestamp();
            }
        }
        if(is_null($key)) {
            if($user) {
                $data = $call();
                $data[self::STATUS_KEY] = AdminSessionInfo::LOGGED_IN;
                $data[self::UPDATED_AT_KEY] = $this->dateTime->gmtTimestamp();
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
