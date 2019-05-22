<?php

namespace Drupal\tn_cookie_auth\User;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserDataInterface;
use Drupal\tn_cookie_auth\User\AdapterInterface as TNUserAdapter;
use Drupal\user\UserInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class Manager.
 */
class Manager {

  use LoggerAwareTrait;

  /**
   * @var \Drupal\user\UserDataInterface
   *   The user data service.
   */
  protected $userDataService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\tn_cookie_auth\User\AdapterInterface
   *   The user adapter.
   */
  protected $userAdapter;

  /**
   * Manager constructor.
   *
   * @param \Drupal\user\UserDataInterface $userDataService
   *   The user data service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\tn_cookie_auth\User\AdapterInterface $userAdapter
   *   The user adapter.
   */
  public function __construct(UserDataInterface $userDataService, EntityTypeManagerInterface $entityTypeManager, TNUserAdapter $userAdapter) {
    $this->userDataService = $userDataService;
    $this->entityTypeManager = $entityTypeManager;
    $this->userAdapter = $userAdapter;
  }

  /**
   * Creates a Drupal user from Trustnet data struct.
   *
   * @param object $tnSessionData
   *   The trustnet user session object.
   *
   * @return \Drupal\user\UserInterface
   *   The new Drupal user object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createAndAssociateUser($tnSessionData) {
    $user = $this->userAdapter->createUser($tnSessionData);
    $this->userDataService->set('tn_cookie_auth', $user->id(), 'userKey', $tnSessionData->userId);
    return $user;
  }

  /**
   * Updates a Drupal user from Trustnet data struct.
   *
   * @param object $tnSessionData
   *   The trustnet user session object.
   * @param \Drupal\user\UserInterface $user
   *   The Drupal user object.
   *
   * @return bool
   *   Whether the tn session user updated the Drupal user.
   */
  public function updateAssociatedUser($tnSessionData, UserInterface $user) {
    return $this->userAdapter->updateUser($tnSessionData, $user);
  }

  /**
   * Fetches a Drupal uid for a given Trustnet userKey.
   *
   * @param string $userKey
   *   Trustnet userKey.
   *
   * @return int|null
   *   The Drupal user id or NULL.
   */
  public function findUidForTNUserKey($userKey) {
    // Return an array of the form $uid => $userKey.
    $userKeysByUid = $this->userDataService->get('tn_cookie_auth', NULL, 'userKey');
    $uidsByUserKey = array_flip($userKeysByUid);
    return isset($uidsByUserKey[$userKey]) ? $uidsByUserKey[$userKey] : NULL;
  }

  /**
   * Fetches the Trustnet user key for a given Drupal UID.
   *
   * @param int $uid
   *   The Drupal User UID.
   *
   * @return string|null
   *   The Trustnet user key or NULL.
   */
  public function findTNUserKeyForUid($uid) {
    return $this->userDataService->get('tn_cookie_auth', $uid, 'userKey');
  }

  /**
   * @param int $userKey
   *   The trustnet user key.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The Drupal User object.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function loadUserByTNUserKey($userKey) {
    $uid = $this->findUidForTNUserKey($userKey);
    return ($uid === NULL ? NULL : $this->entityTypeManager->getStorage('user')
      ->load($uid));
  }

}
