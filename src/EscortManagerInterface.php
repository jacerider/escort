<?php

namespace Drupal\escort;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of block plugins.
 */
interface EscortManagerInterface extends ContextAwarePluginManagerInterface, CategorizingPluginManagerInterface {

}
