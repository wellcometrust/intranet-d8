<?php

namespace Drupal\tn_cookie_auth\User;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class AbstractTNUserAdapter.
 */
abstract class AbstractTNUserAdapter implements AdapterInterface {

  use LoggerAwareTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\user\UserStorage
   */
  protected $userStorage;

  /**
   * AbstractTNUserAdapter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->userStorage = $this->entityTypeManager->getStorage('user');
  }

}
