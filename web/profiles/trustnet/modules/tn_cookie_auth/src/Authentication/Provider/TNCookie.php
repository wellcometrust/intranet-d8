<?php

namespace Drupal\tn_cookie_auth\Authentication\Provider;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\tn_cookie_auth\Event\TNCookieAuthEvent;
use Drupal\tn_cookie_auth\Helper\CookieHelper;
use Drupal\tn_cookie_auth\User\Manager;
use Drupal\user\Authentication\Provider\Cookie;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Implements an authorization provider for Trustnet cookie based SSO
 * authorization.
 */
class TNCookie extends Cookie {

  use LoggerAwareTrait;

  /**
   * @var bool
   */
  protected $allowOverrideByDrupalLogin = FALSE;

  /**
   * @var \Drupal\tn_cookie_auth\User\Manager
   */
  protected $tnUserHelper;

  /**
   * @var \Drupal\Core\Session\SessionConfigurationInterface
   */
  protected $sessionConfiguration;

  /**
   * Cookie Helper.
   *
   * A kernel.response subscriber that can be triggered to clear our cookie
   * (and has some helper methods for convenience)
   *
   * @var \Drupal\tn_cookie_auth\Helper\CookieHelper
   */
  protected $cookieHelper;

  /**
   * The current session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * Constructs a new token authentication provider.
   */
  public function __construct(
    Manager $tnUserHelper,
    CookieHelper $cookieHelper,
    SessionInterface $session,
    SessionConfigurationInterface $sessionConfiguration,
    SessionManagerInterface $sessionManager,
    Connection $connection,
    EventDispatcherInterface $eventDispatcher
  ) {
    $this->session = $session;
    $this->sessionManager = $sessionManager;
    parent::__construct($sessionConfiguration, $connection);
    $this->cookieHelper = $cookieHelper;
    $this->tnUserHelper = $tnUserHelper;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return $this->cookieHelper->hasValidSsoCookie($request) || parent::applies($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    dpm('called');
    $token = $this->cookieHelper->getValidSsoCookie($request);

    $context = [
      '@uri' => $request->getRequestUri(),
      '@cookie_token' => $token,
    ];
    dpm('running');
    // Check to see that the running session is actually an anon commerce one.
    $commerceSession = ((NULL === ($request->getSession()
          ->get('uid'))) && ($request->getSession()
        ->get('commerce_cart_completed_orders')));

    // A session is already running.
    if (!$commerceSession && parent::applies($request)) {
      if (!$request->getSession()->has('sso_token')) {
        // A running session without token can be handled by Drupal's Cookie auth.
        return parent::authenticate($request);
      }
      $currentSessionUid = $request->getSession()->get('uid');
      $currentSessionSsoToken = $request->getSession()->get('sso_token');
      $context += [
        '@uid' => $currentSessionUid,
        '@session_token' => $currentSessionSsoToken,
      ];
      $tnSessionData = $this->lookupTNUser($token);
      if (!$tnSessionData) {
        // If the user is logged out via sso, logout here too.
        return $this->logout();
      }
      if (NULL === ($user = $this->tnUserHelper->loadUserByTNUserKey($tnSessionData->userId)) || $user->id() != $currentSessionUid) {
        // If there is a token on a running session, but no associated user
        // exists, something's wrong.
        $this->logger->warning('Failed authenticating request on @uri for existing session with token @cookie_token: user has no associated Trustnet user key, logging out user @uid', $context);
        return $this->logout();
      }

      $changed = $this->tnUserHelper->updateAssociatedUser($tnSessionData, $user);

      return $user;
    }

    // No session running, need to "login" user.
    dpm('got here');
    if ($tnSessionData = $this->lookupTNUser($token)) {
      $context += ['@user_key' => $tnSessionData->userId];
      // Look for a user that is associated with the Trustnet user key, create if needed.
      if (NULL === ($user = $this->tnUserHelper->loadUserByTNUserKey($tnSessionData->userId))) {
        $user = $this->tnUserHelper->createAndAssociateUser($tnSessionData);
        $context += ['@uid' => $user->id()];
      }
      else {
        $this->tnUserHelper->updateAssociatedUser($tnSessionData, $user);
      }
      $this->session->migrate();
      $this->session->set('uid', $user->id());
      $this->session->set('sso_token', $token);

      // Dispatch the user logged in event.
      $this->eventDispatcher->dispatch(TNCookieAuthEvent::USER_LOGIN, new TNCookieAuthEvent($user));

      return $user;
    }

    return NULL;
  }

  /**
   * Logout.
   */
  protected function logout() {
    $this->sessionManager->destroy();
    return NULL;
  }

  /**
   * @param string $token
   *   The token.
   *
   * @return \stdClass|null
   *   The trustnet user.
   */
  protected function lookupTNUser($token) {
    if ($tnUser = $this->cookieHelper->getDecryptedCookie()) {
      return $tnUser;
    }

    $this->cookieHelper->triggerClearSsoCookie();
    return NULL;

  }

}
