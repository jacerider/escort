<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a fallback plugin for missing block plugins.
 *
 * @Escort(
 *   id = "link",
 *   admin_label = @Translation("Link"),
 *   category = @Translation("Basic"),
 * )
 */
class Link extends Text {
  use EscortPluginLinkTrait;

  /**
   * {@inheritdoc}
   */
  protected $tag = 'a';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will contain 10 feed items.
    return array(
      'url' => '',
      'target' => '',
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    $form = parent::escortForm($form, $form_state);
    $form['url'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Url'),
      '#description' => $this->t('Start typing the title of a piece of content to select it. You can also enter an internal path such as %add-node or an external URL such as %url. Enter %front to link to the front page.',
        [
          '%front' => '<front>',
          '%add-node' => '/node/add',
          '%url' => 'http://example.com',
        ]
      ),
      '#default_value' => $this->configuration['url'] ? static::getUriAsDisplayableString($this->configuration['url']) : NULL,
      '#maxlength' => 2048,
      '#required' => TRUE,
      '#target_type' => 'node',
      // Disable autocompletion when the first character is '/', '#' or '?'.
      '#data-autocomplete-first-character-blacklist' => '/#?',
      '#process_default_value' => FALSE,
      '#element_validate' => [[get_class($this), 'validateUriElement']],
    ];
    $form['target'] = array(
      '#type' => 'checkbox',
      '#title' => t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->configuration['target'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {
    parent::escortSubmit($form, $form_state);
    $this->configuration['url'] = $form_state->getValue('url');
    $this->configuration['target'] = $form_state->getValue('target');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $attributes = $this->getUriAsAttributes($this->configuration['url']);
    $attributes['title'] = $this->configuration['title'];
    return [
      '#tag' => 'a',
      '#attributes' => $attributes,
      '#markup' => $this->configuration['title'],
      '#attached' => ['library' => ['escort/escort.active']],
    ];
  }

}
