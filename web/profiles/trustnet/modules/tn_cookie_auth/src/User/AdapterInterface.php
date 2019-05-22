<?php

namespace Drupal\tn_cookie_auth\User;

use Drupal\user\UserInterface;

/**
 * Interface AdapterInterface.
 */
interface AdapterInterface {

  /**
   * @param object $tnSessionData
   *   The trustnet user session object.
   *
   * @return \Drupal\user\UserInterface
   *   The new Drupal user object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createUser($tnSessionData);

  /**
   * @param object $tnSessionData
   *   The trustnet user session object.
   * @param \Drupal\user\UserInterface $user
   *   The existing Drupal user object.
   *
   * @return bool
   *   Whether the Drupal user was changed.
   */
  public function updateUser($tnSessionData, UserInterface &$user);

}
