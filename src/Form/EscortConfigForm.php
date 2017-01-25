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

    $form['regions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Regions'),
      '#options' => $this->escortRegionManager->getGroups(),
      '#default_value' => $config->get('regions'),
    ];

    $form['toggle'] = [
      '#tree' => TRUE,
    ];
    $toggle = $config->get('toggle');
    foreach ($this->escortRegionManager->getGroups(TRUE) as $group_id => $name) {
      $form['toggle'][$group_id] = [
        '#type' => 'select',
        '#title' => $this->t('Toggle the display of %name from', ['%name' => $name]),
        '#options' => ['- Do not toggle -'] + $this->escortRegionManager->getRegions(TRUE, [$group_id]),
        '#default_value' => isset($toggle[$group_id]) ? $toggle[$group_id] : NULL,
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

    $this->config('escort.config')
      ->set('regions', array_filter($form_state->getValue('regions')))
      ->set('toggle', array_filter($form_state->getValue('toggle')))
      ->save();
  }

}
