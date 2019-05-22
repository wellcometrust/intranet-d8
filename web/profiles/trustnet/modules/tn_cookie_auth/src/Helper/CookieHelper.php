<?php

namespace Drupal\tn_cookie_auth\Helper;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CookieHelper.
 */
class CookieHelper implements EventSubscriberInterface {

  /**
   * @var string
   */
  protected $domain;

  /**
   * @var string
   */
  protected $ssoCookieName;

  /**
   * @var string
   */
  protected $ssoCookieDomain;

  /**
   * @var object
   */
  protected $logger;

  /**
   * @var bool
   */
  protected $clearTokenTriggered = FALSE;

  /**
   * CookieHelper constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   */
  public function __construct(RequestStack $request_stack, LoggerChannelInterface $logger) {
    $this->domain = $request_stack->getCurrentRequest()->getHost();
    $this->logger = $logger;
    $this->ssoCookieName = 'TNCookie';
    $this->ssoCookieDomain = $this->getCookieDomain();
  }

  /**
   * Get the current domain.
   *
   * @return string
   *   The current domain.
   */
  public function getDomain() {
    return $this->domain;
  }

  /**
   * Get the cookie domain.
   *
   * @return mixed|string
   *   The cookie domain.
   */
  public function getCookieDomain() {
    return !empty($cookieDomain = Settings::get('tn_cookie_auth_cookie_domain'))
      ? $cookieDomain : '.' . $this->domain;
  }

  /**
   * Get the sso cookie name.
   *
   * @return mixed|string
   *   The sso cookie name.
   */
  public function getSsoCookieName() {
    return $this->ssoCookieName;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 512];
    return $events;
  }

  /**
   * Kernel.response subscriber.
   *
   * Removes the SSO cookie if clearing has been triggered at some point.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The kernel response event.
   */
  public function onResponse(FilterResponseEvent $event) {
    if ($this->clearTokenTriggered && $event->getRequest()->cookies->has($this->ssoCookieName)) {
      // TODO:
    }
  }

  /**
   * Check if the request contains a valid SSO Cookie.
   *
   * @return bool
   *   Whether there is a valid cookie or not.
   */
  public function hasValidSsoCookie($request) {

    return (NULL !== $this->getValidSsoCookie($request));
  }

  /**
   * Extracts our SSO cookie.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string|null
   *   The valid cookie or NULL.
   */
  public function getValidSsoCookie(Request $request) {
    if (!$request->cookies->has($this->ssoCookieName)) {
      return NULL;
    }

    return $request->cookies->get($this->ssoCookieName);
  }

  /**
   * Flag to clear the cookie for some reason.
   */
  public function triggerClearSsoCookie() {
    $this->clearTokenTriggered = TRUE;
  }

  /**
   * @return array|bool|object
   *   The decrypted cookie.
   */
  public function getDecryptedCookie() {

    $decryptedCookie = &drupal_static(__FUNCTION__);
    if (!isset($decryptedCookie)) {
      if (isset($_COOKIE[$this->getSsoCookieName()])) {
        $tnCookie = explode('.', $_COOKIE[$this->getSsoCookieName()]);
        if (count($tnCookie) !== 3) {
          return FALSE;
        }
        $alg = base64_decode($tnCookie[0]);
        $alg = json_decode($alg);
        if (!isset($alg->alg)) {
          return FALSE;
        }
        if ($alg->alg == 'HS256') {
          $decryptedCookie = $this->decryptCookieSecretKey($_COOKIE[$this->getSsoCookieName()]);
        }
        elseif ($alg->alg == 'RS256') {
          $decryptedCookie = $this->decryptCookiePublicKey($_COOKIE[$this->getSsoCookieName()]);
        }
        else {
          return FALSE;
        }
      }
      else {
        $decryptedCookie = FALSE;
      }
    }
    return $decryptedCookie;
  }

  /**
   * @param string $cookie
   *   The cookie.
   *
   * @return bool|object
   *   The decoded cookie.
   */
  private function decryptCookieSecretKey($cookie) {
    $decodedCookie = FALSE;
    $secretKey = Settings::get('tn_cookie_auth_jwt_secret_key');
    dpm('run here');
    // Never attempt to decode without a key.  It will still work but won't be
    // signed, so do not trust it.
    if (!empty($secretKey)) {
      try {
        $decodedCookie = JWT::decode($cookie, $secretKey, ['HS256']);
      }
      catch (\Exception $e) {
        try {
          $secretKey = Settings::get('tn_cookie_auth_jwt_secret_key_alternative');
          $decodedCookie = JWT::decode($cookie, $secretKey, ['HS256']);
        }
        catch (\Exception $e) {
          watchdog_exception('tn_cookie_auth', $e);
        }
      }
    }

    return $decodedCookie;
  }

  /**
   * @param string $cookie
   *   The cookie.
   *
   * @return bool|object
   *   The decoded cookie.
   */
  private function decryptCookiePublicKey($cookie) {

    $decodedCookie = FALSE;
    $publicKey = Settings::get('tn_cookie_auth_jwt_public_key');

    // Never attempt to decode without a key.  It will still work but won't be
    // signed, so do not trust it.
    if (!empty($publicKey)) {
      try {
        $decodedCookie = JWT::decode($cookie, $publicKey, ['RS256']);
      }
      catch (\Exception $e) {
        watchdog_exception('tn_cookie_auth', $e);
      }
    }
    return $decodedCookie;
  }

}
