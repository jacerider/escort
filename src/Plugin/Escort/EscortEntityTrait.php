<?php

namespace Drupal\escort\Plugin\Escort;

/**
 * A trait that provides dialog utilities.
 */
trait EscortEntityTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected function entityTypeManager() {
    if (!isset($this->entityTypeManager)) {
      $this->entityTypeManager = \Drupal::service('entity_type.manager');
    }
    return $this->entityTypeManager;
  }

  /**
   * Retrieves the entity storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function entityStorage() {
    if (!isset($this->entityStorage)) {
      $this->entityStorage = $this->entityTypeManager()->getStorage($this->entityTypeBundle);
    }
    return $this->entityStorage;
  }

  /**
   * Add views display for entity management.
   *
   * @var string $bundle
   *   The bundle of the entity type to add a management display for.
   *
   * @return \Drupal\views\Entity\ViewEntityInterface|false
   *   The view entity if success, else false.
   */
  protected function addManagementViewDisplay($bundle) {
    $bundle = $this->entityStorage()->load($bundle);
    if ($bundle) {
      $view_storage = $this->entityTypeManager->getStorage('view');
      if ($view = $view_storage->load('escort_' . $this->entityType . '_manage')) {
        $displays = $view->get('display');
        if (!isset($displays[$bundle->id()])) {
          // Setup display.
          $displays[$bundle->id()] = [
            'display_plugin' => 'embed',
            'id' => $bundle->id(),
            'display_title' => $bundle->label(),
            'position' => count($displays),
            'display_options' => [
              'arguments' => $displays['default']['display_options']['arguments'],
              'defaults' => [
                'arguments' => FALSE,
              ],
            ],
            'cache_metadata' => $displays['default']['cache_metadata'],
          ];
          // The 'bundle' argument is not labeled the same for all entity types.
          // This method assumes each base view only has one argument and it
          // is a bundle argument.
          foreach ($displays[$bundle->id()]['display_options']['arguments'] as $key => &$argument) {
            $argument['default_argument_options']['argument'] = $bundle->id();
          }
          $display['display_options']['filters']['type']['value'] = [
            $bundle->id() => $bundle->id(),
          ];
          // Save.
          $view->set('display', $displays);
          $view->save();
          return $view;
        }
      }
    }
    return FALSE;
  }

  /**
   * Return render array of management view.
   *
   * @var string $bundle
   *   The bundle of the entity type to add a management display for.
   *
   * @return array
   *   The render array of the view.
   */
  protected function getManagementView($bundle) {
    return views_embed_view('escort_' . $this->entityType . '_manage', $bundle);
  }

}
