<?php

namespace Drupal\escort\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\escort\EscortRegionManagerInterface;

/**
 * Class EscortConfigForm.
 *
 * @package Drupal\escort\Form
 */
class EscortConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\escort\EscortRegionManagerInterface definition.
   *
   * @var \Drupal\escort\EscortRegionManagerInterface
   */
  protected $escortRegionManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManager $entity_type_manager, EscortRegionManagerInterface $escort_region_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->escortRegionManager = $escort_region_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('escort.region_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'escort.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'escort_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('escort.config');

    $form['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Regions'),
      '#options' => $this->escortRegionManager->getGroups(),
      '#default_value' => $config->get('enabled'),
    ];

    $form['regions'] = [
      '#tree' => TRUE,
    ];
    $region_settings = $config->get('regions');
    foreach ($this->escortRegionManager->getGroups(TRUE) as $group_id => $name) {
      $form['regions'][$group_id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('%region settings', ['%region' => $name]),
      ];
      $form['regions'][$group_id]['toggle'] = [
        '#type' => 'select',
        '#title' => $this->t('Toggle the display of %name from', ['%name' => $name]),
        '#options' => ['- Do not toggle -'] + $this->escortRegionManager->getRegions(TRUE, [$group_id]),
        '#default_value' => isset($region_settings[$group_id]['toggle']) ? $region_settings[$group_id]['toggle'] : NULL,
      ];
    }


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $regions = $form_state->getValue('regions');
    ksm($regions);

    $this->config('escort.config')
      ->set('enabled', array_filter($form_state->getValue('enabled')))
      ->set('regions', array_filter($form_state->getValue('regions')))
      ->save();
  }

}
