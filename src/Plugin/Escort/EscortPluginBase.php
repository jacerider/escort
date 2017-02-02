<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\escort\Entity\EscortInterface;
use Drupal\Component\Transliteration\TransliterationInterface;

/**
 * Defines a base escort implementation that most escorts plugins will extend.
 *
 * This abstract class provides the generic escort configuration form, default
 * escort settings, and handling for general user-defined escort visibility
 * settings.
 *
 * @ingroup escort_api
 */
abstract class EscortPluginBase extends PluginBase implements EscortPluginInterface, PluginWithFormsInterface {

  use RefinableCacheableDependencyTrait;
  use PluginWithFormsTrait;

  /**
   * The escort entity this plugin belongs to.
   *
   * @var \Drupal\escort\Entity\EscortInterface
   */
  protected $escort;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * Whether the escort provides multiple sub-escorts.
   *
   * @var bool
   */
  protected $provideMultiple = FALSE;

  /**
   * Whether the escort provides multiple sub-escorts.
   *
   * @var bool
   */
  protected $usesIcon = TRUE;

  /**
   * {@inheritdoc}
   */
  public function label($force_admin = FALSE) {
    if (!$force_admin && !empty($this->configuration['label'])) {
      return $this->configuration['label'];
    }

    $definition = $this->getPluginDefinition();
    // Cast the admin label to a string since it is an object.
    // @see \Drupal\Core\StringTranslation\TranslatableMarkup
    return (string) $definition['admin_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * Returns generic default configuration for escort plugins.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function baseConfigurationDefaults() {
    return array(
      'id' => $this->getPluginId(),
      'label' => '',
      'icon' => '',
      'provider' => $this->pluginDefinition['provider'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValue($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = $this->escortAccess($account);
    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Indicates whether the escort should be shown.
   *
   * Escorts with specific access checking should override this method rather
   * than access(), in order to avoid repeating the handling of the
   * $return_as_object argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see self::access()
   */
  protected function escortAccess(AccountInterface $account) {
    // By default, the escort is visible.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all escort types. Individual
   * escort plugins can add elements to this form by overriding
   * EscortPluginBase::escortForm(). Most escort plugins should not override
   * this method unless they need to alter the generic form elements.
   *
   * @see \Drupal\escort\EscortPluginBase::escortForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Add plugin-specific settings for this escort type.
    $form += $this->escortBaseForm($form, $form_state);

    // Add plugin-specific settings for this escort type.
    $form += $this->escortForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortBaseForm($form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $form['provider'] = [
      '#type' => 'value',
      '#value' => $definition['provider'],
    ];

    $form['admin_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Escort description'),
      '#plain_text' => $definition['admin_label'],
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Admin Label'),
      '#maxlength' => 255,
      '#default_value' => $this->label(),
      '#required' => TRUE,
    ];
    if ($this->usesIcon && $this->hasIconSupport()) {
      $form['icon'] = $this->escortIconForm($form, $form_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function escortIconForm($form, FormStateInterface $form_state) {
    return [
      '#type' => 'micon',
      '#title' => $this->t('Icon'),
      '#default_value' => $this->configuration['icon'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function escortForm($form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   *
   * Most escort plugins should not override this method. To add validation
   * for a specific escort type, override EscortPluginBase::escortValidate().
   *
   * @see \Drupal\escort\EscortPluginBase::escortValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->escortBaseValidate($form, $form_state);
    $this->escortValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function escortBaseValidate($form, FormStateInterface $form_state) {
    // Remove the admin_label form item element value so it will not persist.
    $form_state->unsetValue('admin_label');
  }

  /**
   * {@inheritdoc}
   */
  public function escortValidate($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   *
   * Most escort plugins should not override this method. To add submission
   * handling for a specific escort type, override
   * EscortPluginBase::escortSubmit().
   *
   * @see \Drupal\escort\EscortPluginBase::escortSubmit()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the escort's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      $this->escortBaseSubmit($form, $form_state);
      $this->escortSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function escortBaseSubmit($form, FormStateInterface $form_state) {
    $this->configuration['label'] = $form_state->getValue('label');
    $this->configuration['icon'] = $form_state->getValue('icon');
    $this->configuration['provider'] = $form_state->getValue('provider');
  }

  /**
   * {@inheritdoc}
   */
  public function escortSubmit($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $definition = $this->getPluginDefinition();
    $admin_label = $definition['admin_label'];

    // @todo This is basically the same as what is done in
    //   \Drupal\system\MachineNameController::transliterate(), so it might make
    //   sense to provide a common service for the two.
    $transliterated = $this->transliteration()->transliterate($admin_label, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = Unicode::strtolower($transliterated);

    $transliterated = preg_replace('@[^a-z0-9_.]+@', '', $transliterated);

    return $transliterated;
  }

  /**
   * Wraps the transliteration service.
   *
   * @return \Drupal\Component\Transliteration\TransliterationInterface
   *   The transliteration service.
   */
  protected function transliteration() {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

  /**
   * Sets the transliteration service.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function setTransliteration(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

  /**
   * Sets the escort entity this plugin belongs to.
   */
  public function setEscort(EscortInterface $escort) {
    $this->escort = $escort;
    return $this;
  }

  /**
   * Sets the escort entity this plugin belongs to.
   */
  public function getEscort() {
    return $this->escort;
  }

  /**
   * Checks if we are in admin mode.
   *
   * @return bool
   *   True if in admin mode.
   */
  public function isAdmin() {
    return \Drupal::service('escort.path.matcher')->isAdmin();
  }

  /**
   * Checks if Micon module is installed.
   *
   * @return bool
   *   True if Micon module is installed.
   */
  public function hasIconSupport() {
    return \Drupal::moduleHandler()->moduleExists('micon');
  }

  /**
   * {@inheritdoc}
   */
  public function usesMultiple() {
    return $this->provideMultiple;
  }

  /**
   * Returns attributes that will be added to the HTML doc body.
   *
   * @var bool $is_admin
   *   TRUE if page is an admin page.
   *
   * @return array
   *   An associative attributes array.
   */
  public function getBodyAttributes($is_admin) {
    return array();
  }

  /**
   * Allows the plugin to alter the escort content.
   */
  public function viewAlter(&$build) {}

}
