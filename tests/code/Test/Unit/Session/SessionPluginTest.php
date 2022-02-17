<?php

namespace Tschallacka\StayLoggedIn\tests\code\Test\Unit\Session;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class SessionPluginTest extends TestCase
{
    public function setUp() :void
    {

    }

    public function testProductAfterFetch()
    {
        xdebug_break();
    }
}
