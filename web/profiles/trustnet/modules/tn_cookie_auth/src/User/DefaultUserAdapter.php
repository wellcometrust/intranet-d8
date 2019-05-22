<?php

namespace Drupal\tn_cookie_auth\User;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Utility\Random;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\user\UserInterface;

/**
 * Class DefaultUserAdapter.
 */
class DefaultUserAdapter extends AbstractTNUserAdapter {

  protected $logger;

  /**
   * DefaultUserAdapter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelInterface $logger
  ) {
    $this->logger = $logger;
    parent::__construct($entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  public function createUser($tnSessionData) {

    $r = new Random();

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->create();

    $user = $this->setUserData($tnSessionData, $user);

    // Random password so a user can never log in via Drupal.
    $user->setPassword($r->string(32));
    $user->enforceIsNew();
    $user->activate();
    $user->save();

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function updateUser($tnSessionData, UserInterface &$user) {
    // TODO: implement user updates.
    return FALSE;
  }

  /**
   * @param string $desiredName
   *   The prefered username to assign to the user.
   *
   * @return string
   *   The username after all collision checks.
   */
  protected function usernameRemoveCollisions($desiredName) {
    $name = $desiredName;
    $i = 1;
    while (!empty($this->userStorage->loadByProperties(['name' => $name]))) {
      $name = $desiredName . '_tn_' . $i++;
    }
    if ($name != $desiredName) {
      $this->logger->warning("Creating user @old_name from Usermanager but changing their username to @name, as there already is a user named @old_name.", ['@name' => $name, '@old_name' => $desiredName]);
    }
    return $name;
  }

  /**
   * @param object $tnSessionData
   *   The trustnet user session object.
   * @param \Drupal\user\UserInterface $user
   *   The Drupal user object.
   *
   * @return \Drupal\user\UserInterface
   *   Return the user we've updated/created/found.
   */
  protected function setUserData($tnSessionData, UserInterface $user) {

    // Update email.
    $users = $this->userStorage->loadByProperties(['mail' => $tnSessionData->email]);
    if (($user->isNew() && empty($users)) || (!$user->isNew() && (empty($users) || isset($users[$user->getOriginalId()])))) {
      $user->setEmail($tnSessionData->email);
    }
    else {
      $this->logger->warning("Creating or updating user @name from Usermanager without saving their email address @email, as there already is a user with the email address @email.", ['@name' => $tnSessionData->userId, '@email' => $tnSessionData->email]);
    }

    // Update username only when new account is being created.
    if ($user->isNew()) {
      $user->setUsername($this->usernameRemoveCollisions($tnSessionData->username));
    }

    return $user;
  }

}
