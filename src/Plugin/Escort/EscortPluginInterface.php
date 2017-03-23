<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\escort\Entity\EscortInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the required interface for all escort plugins.
 *
 * @ingroup escort_api
 */
interface EscortPluginInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface, CacheableDependencyInterface, DerivativeInspectionInterface {

  /**
   * Returns the admin-facing escort label.
   *
   * @return string
   *   The escort admin label.
   */
  public function label();

  /**
   * Indicates whether the escort should be shown.
   *
   * This method allows base implementations to add general access restrictions
   * that should apply to all extending escort plugins.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   *
   * @see \Drupal\escort\EscortAccessControlHandler
   */
  public function access(AccountInterface $account, $return_as_object = FALSE);

  /**
   * Builds and returns the renderable array for this escort plugin.
   *
   * If a escort should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the escort.
   *
   * @see \Drupal\escort\EscortViewBuilder
   */
  public function build();

  /**
   * Sets a particular value in the escort settings.
   *
   * @param string $key
   *   The key of PluginBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function setConfigurationValue($key, $value);

  /**
   * Returns the configuration form elements specific to this escort plugin.
   *
   * Escorts that need to add form elements to the normal escort configuration
   * form should implement this method.
   *
   * @param array $form
   *   The form definition array for the escort configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   */
  public function escortForm($form, FormStateInterface $form_state);

  /**
   * Adds escort type-specific validation for the escort form.
   *
   * Note that this method takes the form structure and form state for the full
   * escort configuration form as arguments, not just the elements defined in
   * EscortPluginInterface::escortForm().
   *
   * @param array $form
   *   The form definition array for the full escort configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Escort\EscortPluginInterface::escortForm()
   * @see \Drupal\Core\Escort\EscortPluginInterface::escortSubmit()
   */
  public function escortValidate($form, FormStateInterface $form_state);

  /**
   * Adds escort type-specific submission handling for the escort form.
   *
   * Note that this method takes the form structure and form state for the full
   * escort configuration form as arguments, not just the elements defined in
   * EscortPluginInterface::escortForm().
   *
   * @param array $form
   *   The form definition array for the full escort configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Escort\EscortPluginInterface::escortForm()
   * @see \Drupal\Core\Escort\EscortPluginInterface::escortValidate()
   */
  public function escortSubmit($form, FormStateInterface $form_state);

  /**
   * Returns attributes that will be added to the HTML doc body.
   *
   * @var bool $is_admin
   *   TRUE if page is an admin page.
   *
   * @return array
   *   An associative attributes array.
   */
  public function getBodyAttributes($is_admin);

  /**
   * Sets the escort entity this plugin belongs to.
   */
  public function setEscort(EscortInterface $escort);

  /**
   * Sets the escort entity this plugin belongs to.
   */
  public function getEscort();

  /**
   * Checks if we are in admin mode.
   *
   * @return bool
   *   True if in admin mode.
   */
  public function isAdmin();

  /**
   * Checks if this is a temporary plugin.
   *
   * @return bool
   *   True if temporary.
   */
  public function isTemporary();

  /**
   * Sets plugin as temporary.
   */
  public function enforceIsTemporary();

  /**
   * Suggests a machine name to identify an instance of this escort.
   *
   * The escort plugin need not verify that the machine name is at all unique.
   * It is only responsible for providing a baseline suggestion; calling code is
   * responsible for ensuring whatever uniqueness is required for the use case.
   *
   * @return string
   *   The suggested machine name.
   */
  public function getMachineNameSuggestion();

}
