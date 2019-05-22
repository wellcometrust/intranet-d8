<?php

namespace Drupal\tn_cookie_auth\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class OrderFulfillmentEvent.
 */
class TNCookieAuthEvent extends Event {
  const USER_LOGIN = 'tn_cookie_auth.user.login';

  /**
   * Drupal User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * TNCookieAuthEvent constructor.
   *
   * @param \Drupal\user\UserInterface $user
   *   The Drupal user.
   */
  public function __construct(UserInterface $user) {
    $this->user = $user;
  }

  /**
   * Get the user whos logged in.
   *
   * @return \Drupal\user\UserInterface
   *   The user whos logged in.
   */
  public function getUser() {
    return $this->user;
  }

}
