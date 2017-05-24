<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\escort\EscortRegionManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "toggle",
 *   admin_label = @Translation("Toggle"),
 *   category = @Translation("Basic"),
 * )
 */
class Toggle extends EscortPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Creates a Toggle instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\escort\EscortRegionManagerInterface $escort_region_manager
   *   The escort region manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EscortRegionManagerInterface $escort_region_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->escortRegionManager = $escort_region_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('escort.region_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'region' => NULL,
      'event' => 'hover',
      'icon' => 'fa-bars',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function mockConfiguration() {
    return [
      'region' => 'top',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requireRegion() {
    return $this->configuration['region'];
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $region = $form_state->getTemporaryValue('entity')->getRegion();
    $region = $this->escortRegionManager->getGroupId($region);

    $form['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t('The region controlled by this toggle.'),
      '#options' => $this->escortRegionManager->getGroups(TRUE, [$region]),
      '#default_value' => $this->configuration['region'],
    ];
    $form['event'] = [
      '#type' => 'select',
      '#title' => $this->t('Event'),
      '#options' => ['hover' => $this->t('Hover'), 'click' => $this->t('Click')],
      '#default_value' => $this->configuration['event'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    $this->configuration['region'] = $form_state->getValue('region');
    $this->configuration['event'] = $form_state->getValue('event');
  }

  /**
   * {@inheritdoc}
   */
  public function escortBuild() {
    $build = [];
    $build['#tag'] = 'a';
    $escort = $this->getEscort();
    $type = $this->escortRegionManager->getGroupType($escort->getRegion());
    if ($this->configuration['event'] == 'click' || $type == 'vertical') {
      $label = $this->escortRegionManager->getGroupLabel($this->configuration['region']);
      $build['#markup'] = $this->t('Toggle @label', array('@label' => $label));
    }
    $build['#attached']['library'][] = 'escort/escort.toggle';
    $build['#attributes']['class'][] = 'escort-toggle';
    $build['#attributes']['data-region'] = $this->configuration['region'];
    $build['#attributes']['data-event'] = $this->configuration['event'];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyAttributes($is_admin) {
    if (!$is_admin) {
      return ['class' => [Html::cleanCssIdentifier('hide-escort-' . $this->configuration['region'])]];
    }
  }

}
