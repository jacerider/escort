<?php

namespace Drupal\escort;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the escort entity type.
 *
 * @see \Drupal\escort\Entity\Escort
 */
class EscortAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\escort\EscortInterface $entity */
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }

    // Don't grant access to disabled escorts.
    if (!$entity->status()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    else {
      // Delegate to the plugin.
      $escort_plugin = $entity->getPlugin();
      try {
        $access = $escort_plugin->access($account, TRUE);
      }
      catch (ContextException $e) {
        $access = AccessResult::forbidden()->setCacheMaxAge(0);
      }

      // Ensure that access is evaluated again when the escort changes.
      return $access->addCacheableDependency($entity);
    }
  }

}
