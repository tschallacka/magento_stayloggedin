<?php
declare(strict_types=1);
namespace Tschallacka\StayLoggedIn\Plugin;

use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Session\Storage;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Security\Model\AdminSessionInfo;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;

/**
 * @see Storage
 */
class BackendSessionPlugin
{
    const KEY = 'user';
    const IS_FIRST_VISIT = 'is_first_visit';
    const COOKIE = 'tsch_stayloggedin';
    const STATUS = 'status';
    private State $state;
    private CookieManagerInterface $cookieManager;
    private CookieMetadataFactory $cookieMetadataFactory;
    private LoggerInterface $logger;
    private EncryptorInterface $encryptor;
    private UserResource $user_resource;
    private UserFactory $user_factory;
    private Session $backend_session;
    private RequestInterface $request;

    public function __construct(
        State $state,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        Session $backend_session,
        LoggerInterface $logger,
        EncryptorInterface $encryptor,
        UserResource $user_resource,
        UserFactory $user_factory,
        RequestInterface $request
    )
    {

        $this->state = $state;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->user_resource = $user_resource;
        $this->user_factory = $user_factory;
        $this->backend_session = $backend_session;
        $this->request = $request;
    }

    public function aroundGetData(Storage $session_storage, $orig, $key)
    {
        /** @var bool $pass Skip the entire interceptor skedazzle after the first time.
         * We only need this once per request as this should not
         * change per request. #phpismeanttodie
         */
        static $pass;
        if(is_null($pass)) {
            $pass = $this->state->getAreaCode() === Area::AREA_ADMINHTML
                    && State::MODE_DEVELOPER === $this->state->getMode();
        }
        $data = $orig($key);
        if ($pass && $key === self::KEY) {
            if(is_null($data)) {
                $cookie = $this->getCustomCookie();
                if(is_numeric($cookie)) {
                    $this->logger->alert('Renewing backend session for user id '.$cookie);
                    $this->backend_session->regenerateId();
                    $user = $this->user_factory->create();
                    $this->user_resource->load($user, $cookie, $user->getIdFieldName());
                    $data = $user;
                    $session_storage->setData(FormKey::FORM_KEY, $this->request->getParam(UrlInterface::SECRET_KEY_PARAM_NAME));
                    $session_storage->setData(self::IS_FIRST_VISIT, false);
                    $session_storage->setData(self::KEY, $user);
                    $session_storage->setData(self::STATUS, AdminSessionInfo::LOGGED_IN);
                }
            }
            /** refresh the cookie, keep it fresh */
            if($data) {
                $this->setCustomCookie($data->getId());
            }
        }

        return $data;
    }

    public function setCustomCookie(string $content)
    {
        static $hasrun;
        if($hasrun) return;
        $hasrun = true;
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDurationOneYear();
        $publicCookieMetadata->setPath('/');
        // No javascript access, thank you.
        $publicCookieMetadata->setHttpOnly(true);

        try {
            $this->cookieManager->setPublicCookie(
                self::COOKIE,
                $this->encryptor->encrypt($content),
                $publicCookieMetadata
            );
        } catch (InputException $e) {
        } catch (CookieSizeLimitReachedException $e) {
        } catch (FailureToSendException $e) {
        }
    }

    public function getCustomCookie()
    {
        $content = $this->cookieManager->getCookie(
            self::COOKIE
        );
        if(!is_null($content)) {
            return $this->encryptor->decrypt($content);
        }
        return null;
    }
}
