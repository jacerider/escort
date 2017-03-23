<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines a fallback plugin for missing escort plugins.
 *
 * @Escort(
 *   id = "admin_escape",
 *   admin_label = @Translation("Admin Escape"),
 *   category = @Translation("Basic"),
 * )
 */
class AdminEscape extends EscortPluginBase {
  use EscortPluginLinkTrait;

  /**
   * {@inheritdoc}
   */
  protected $usesIcon = FALSE;

  /**
   * {@inheritdoc}
   */
  public function escortBuild() {
    $build = [
      '#attributes' => $this->getUriAsAttributes('internal:/'),
      // '#attached' => ['library' => ['escort/escort.escape']],
    ];

    $build['#attributes']['title'] = t('Return to site content');

    $route = \Drupal::routeMatch()->getRouteObject();
    if (\Drupal::service('router.admin_context')->isAdminRoute($route) === TRUE) {
      $build['#tag'] = 'a';
      $build['#icon'] = 'fa-arrow-circle-o-left';
      $build['#markup'] = $this->t('Back to site');
      $build['#attributes']['class'][] = 'escort-hidden';
    }
    else {
      $build['#empty'] = TRUE;
    }
    // ksm($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuildElementSuffix() {
    $build = [];
    // We add the library here so that it is always loaded even when the escort
    // is not built.
    $build['#attached']['library'][] = 'escort/escort.escape';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function escortAccess(AccountInterface $account) {
    if ($account->hasPermission('access administration pages')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    // No opinion.
    return AccessResult::neutral();
  }

}
