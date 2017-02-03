<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "dropdown",
 *   admin_label = @Translation("Dropdown"),
 *   category = @Translation("Basic"),
 * )
 */
class Dropdown extends EscortPluginMultipleBase {

  /**
   * {@inheritdoc}
   */
  protected $provideMultiple = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesIcon = FALSE;

  /**
   * Whether the escort allows trigger text/icon configuration.
   *
   * @var bool
   */
  protected $usesTrigger = TRUE;

  /**
   * Checks whether the escort allows trigger text/icon configuration.
   *
   * @return bool
   *   True if icon should be used.
   */
  public function usesTrigger() {
    return $this->hasIconSupport() && $this->usesTrigger;
  }

  /**
   * {@inheritdoc}
   */
  protected function baseConfigurationDefaults() {
    $defaults = parent::baseConfigurationDefaults();
    if ($this->usesTrigger()) {
      $defaults['trigger'] = '';
      $defaults['trigger_icon'] = '';
    }
    $defaults['ajax'] = FALSE;
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'dropdown' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function escortBaseForm($form, FormStateInterface $form_state) {
    $form = parent::escortBaseForm($form, $form_state);
    if ($this->usesTrigger()) {
      $form['trigger'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Trigger title'),
        '#default_value' => $this->configuration['trigger'],
      ];
      $form['trigger_icon'] = $this->escortIconForm($form, $form_state, $this->t('Trigger icon'), $this->configuration['trigger_icon']);
    }
    $form['ajax'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use AJAX to display dropdown content'),
      '#default_value' => $this->configuration['ajax'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $form['dropdown'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Dropdown content'),
      '#default_value' => $this->configuration['dropdown'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortBaseSubmit($form, FormStateInterface $form_state) {
    parent::escortBaseSubmit($form, $form_state);
    $this->configuration['trigger'] = $form_state->getValue('trigger');
    $this->configuration['ajax'] = $form_state->getValue('ajax');
    if ($this->usesTrigger()) {
      $this->configuration['trigger_icon'] = $form_state->getValue('trigger_icon');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    $this->configuration['dropdown'] = $form_state->getValue('dropdown');
  }

  /**
   * {@inheritdoc}
   */
  public function buildItems() {
    $items = [];

    // Add a wrapper class.
    $items['#attributes']['class'][] = 'escort-dropdown';
    $items['#attached']['library'][] = 'escort/escort.dropdown';

    $items['link'] = $this->buildLink();
    $items['link']['#attributes']['class'][] = 'escort-dropdown-trigger';

    if ($this->configuration['ajax']) {
      $items['link']['#attributes']['href'] = Url::fromRoute('escort.escort_ajax', ['escort' => $this->getEscort()->id()])->toString();
      $items['link']['#attributes']['data-escort-ajax'] = '';
      $items['link']['#attached']['library'][] = 'core/drupal.ajax';
      $items['dropdown']['replace'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['id' => 'escort-ajax-' . $this->getEscort()->uuid()],
      ];
    }
    else {
      $items['dropdown'] = $this->buildDropdown();
    }
    $items['dropdown']['#attributes']['class'][] = 'escort-dropdown-content';

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildLink() {
    return [
      '#tag' => 'a',
      '#markup' => $this->configuration['trigger'],
      '#icon' => $this->configuration['trigger_icon'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDropdown() {
    return [
      '#markup' => $this->configuration['dropdown'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildAjax() {
    return $this->buildDropdown();
  }

}
