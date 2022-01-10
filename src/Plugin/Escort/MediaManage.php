<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\micon\MiconIconize;
use Drupal\Core\Link as DrupalLink;

/**
 * Defines a plugin for managing content.
 *
 * @Escort(
 *   id = "media_manage",
 *   admin_label = @Translation("Media Manage"),
 *   category = @Translation("Media"),
 * )
 */
class MediaManage extends Aside {
  use EscortEntityTrait;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType = 'media';

  /**
   * The entity type bundle.
   *
   * @var string
   */
  protected $entityTypeBundle = 'bundle';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'text' => $this->t('Manage [TYPE]'),
      'icon' => 'fa-file-image-o',
      'bundle' => '',
      'view' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function escortAccess(AccountInterface $account) {

    if ($account->hasPermission('administer media bundles')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($bundle = $this->configuration['bundle']) {
      if ($account->hasPermission('update media') || $account->hasPermission('update any media')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $form = parent::escortForm($form, $form_state);
    $options = [];
    foreach ($this->entityStorage()->loadMultiple() as $entity) {
      $options[$entity->id()] = MiconIconize::iconize($entity->label())->addMatchPrefix('content_type');
    }
    $form['bundle'] = [
      '#type' => 'radios',
      '#title' => $this->t('Bundle'),
      '#description' => $this->t('If no bundle is selected, all bundles will be available.'),
      '#options' => $options,
      '#default_value' => $this->configuration['bundle'],
    ];
    $form['view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use view'),
      '#description' => $this->t('Use a view instead of the entity list manager.'),
      '#default_value' => $this->configuration['view'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    parent::escortSubmit($form, $form_state);
    $this->configuration['bundle'] = $form_state->getValue('bundle');
    $this->configuration['view'] = $form_state->getValue('view');

    // Create view display if it doesn't exist.
    $this->addManagementViewDisplay($this->configuration['bundle']);
  }

  /**
   * {@inheritdoc}
   */
  protected function escortBuildAsideContent() {
    $build = [];
    $build['add'] = DrupalLink::createFromRoute('Add New', 'entity.' . $this->entityType . '.add_form', [
      'media_type' => $this->configuration['bundle'],
    ], [
      'attributes' => ['class' => ['escort-create']],
    ])->toRenderable();
    if ($this->configuration['view']) {
      $build['view'] = $this->getManagementView($this->configuration['bundle']);
    }
    else {
      $build['list'] = $this->entityTypeManager->getHandler($this->entityType, 'escort_list_builder')->setTypes([$this->configuration['bundle']])->render();
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable() {
    return \Drupal::moduleHandler()->moduleExists('media');
  }

}
