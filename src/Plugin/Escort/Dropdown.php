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
   * Whether the escort provides multiple sub-escorts.
   *
   * @var bool
   */
  protected $usesIcon = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function baseConfigurationDefaults() {
    return parent::baseConfigurationDefaults() + array(
      'trigger' => '',
      'trigger_icon' => '',
      'ajax' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'dropdown' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function escortBaseForm($form, FormStateInterface $form_state) {
    $form = parent::escortBaseForm($form, $form_state);
    $form['trigger'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trigger title'),
      '#default_value' => $this->configuration['trigger'],
    ];
    if ($this->hasIconSupport()) {
      $form['trigger_icon'] = $this->escortIconForm($form, $form_state);
      $form['trigger_icon']['#title'] = $this->t('Trigger icon');
      $form['trigger_icon']['#default_value'] = $this->configuration['trigger_icon'];
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
    if ($this->hasIconSupport()) {
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
        '#value' => $this->t('Hello World'),
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
