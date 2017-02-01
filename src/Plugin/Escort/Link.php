<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\escort\EscortAjaxTrait;

/**
 * Defines a link plugin.
 *
 * @Escort(
 *   id = "link",
 *   admin_label = @Translation("Link"),
 *   category = @Translation("Basic"),
 * )
 */
class Link extends Text {
  use EscortPluginLinkTrait;
  use EscortAjaxTrait;

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
      'ajax' => FALSE,
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
    $form['dialog'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use AJAX dialog'),
      '#default_value' => $this->configuration['dialog'],
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
    $this->configuration['dialog'] = $form_state->getValue('dialog');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $attributes = $this->getUriAsAttributes($this->configuration['url']);
    $attributes['title'] = $this->configuration['title'];
    $build = [
      '#tag' => 'a',
      '#attributes' => $attributes,
      '#markup' => $this->configuration['title'],
      '#attached' => ['library' => ['escort/escort.active']],
    ];

    if ($this->configuration['target']) {
      $build['#attributes']['target'] = $this->configuration['target'];
    }

    $url = $this->getUrl($this->configuration['url']);
    if ($this->configuration['dialog'] && !$url->isExternal() && $url->isRouted()) {
      // Dialog ajaxify.
      $this->ajaxLinkAttributes($build);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function escortAccess(AccountInterface $account) {
    return $this->uriAccess($this->configuration['url']);
  }

}
