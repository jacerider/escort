<?php

namespace Drupal\escort;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of escort plugins.
 */
class EscortPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The escort ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $escortItemId;

  /**
   * Constructs a new EscortPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $escort_id
   *   The unique ID of the escort entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $escort_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->escortItemId = $escort_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Escort\Plugin\Escort\EscortPluginInterface
   *   The plugin.
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The escort '{$this->escortItemId}' did not specify a plugin.");
    }

    try {
      parent::initializePlugin($instance_id);
    }
    catch (PluginException $e) {
      $module = $this->configuration['provider'];
      // Ignore escorts belonging to uninstalled modules, but re-throw valid
      // exceptions when the module is installed and the plugin is
      // misconfigured.
      if (!$module || \Drupal::moduleHandler()->moduleExists($module)) {
        throw $e;
      }
    }
  }

}
