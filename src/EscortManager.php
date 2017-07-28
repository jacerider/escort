<?php

namespace Drupal\escort;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of block plugins.
 *
 * @todo Add documentation to this class.
 *
 * @see \Drupal\escort\Plugin\Escort\EscortPluginInterface
 */
class EscortManager extends DefaultPluginManager implements EscortManagerInterface, FallbackPluginManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
    getGroupedDefinitions as traitGetGroupedDefinitions;
  }
  use ContextAwarePluginManagerTrait;

  /**
   * Constructs a new \Drupal\escort\EscortManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Escort', $namespaces, $module_handler, 'Drupal\escort\Plugin\Escort\EscortPluginInterface', 'Drupal\escort\Annotation\Escort');

    $this->alterInfo('escort');
    $this->setCacheBackend($cache_backend, 'escort_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = $this->getCachedDefinitions();
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions();
      foreach ($definitions as $key => $definition) {
        if (!$definition['class']::isApplicable()) {
          unset($definitions[$key]);
        }
      }
      $this->setCachedDefinitions($definitions);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);
  }

  /**
   * Returns an array of filter options for a field type.
   *
   * @param string|null $field_type
   *   (optional) The name of a field type, or NULL to retrieve all filters.
   *
   * @return array
   *   If no field type is provided, returns a nested array of all filters,
   *   keyed by field type.
   */
  public function getOptions($field_type = NULL) {
    $options = [];
    $definitions = $this->getDefinitions();
    foreach ($this->getSortedDefinitions($definitions) as $plugin) {
      $options[(string) $plugin['category']][$plugin['id']] = $plugin['admin_label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL, $show_hidden = FALSE) {
    // Sort the plugins first by category, then by label.
    $definitions = $this->traitGetSortedDefinitions($definitions, 'admin_label');
    // Do not display the 'broken' plugin in the UI.
    unset($definitions['broken']);

    if (!$show_hidden) {
      // Filter out definitions that can not be configured in Field UI.
      $definitions = array_filter($definitions, function ($definition) {
        return empty($definition['no_ui']);
      });
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedDefinitions(array $definitions = NULL, $show_hidden = FALSE) {
    $definitions = $this->traitGetGroupedDefinitions($definitions, 'admin_label');

    if (!$show_hidden) {
      // Filter out definitions that can not be configured in Field UI.
      foreach ($definitions as $label => &$items) {
        // Filter out definitions that can not be configured in Field UI.
        $items = array_filter($items, function ($definition) {
          return empty($definition['no_ui']);
        });
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = array()) {
    return 'broken';
  }

}
