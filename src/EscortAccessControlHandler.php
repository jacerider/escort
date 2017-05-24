<?php

namespace Drupal\escort;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the escort entity type.
 *
 * @see \Drupal\escort\Entity\Escort
 */
class EscortAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  use ConditionAccessResolverTrait;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $manager;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The escort path matcher.
   *
   * @var \Drupal\escort\EscortPathMatcherInterface
   */
  protected $escortPathMatcher;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('plugin.manager.condition'),
      $container->get('context.handler'),
      $container->get('context.repository'),
      $container->get('escort.path.matcher')
    );
  }

  /**
   * Constructs the escort access control handler instance
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for checking visibility of escorts.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The ContextHandler for applying contexts to conditions properly.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(EntityTypeInterface $entity_type, ExecutableManagerInterface $manager, ContextHandlerInterface $context_handler, ContextRepositoryInterface $context_repository, EscortPathMatcherInterface $escort_path_matcher) {
    parent::__construct($entity_type);
    $this->manager = $manager;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
    $this->escortPathMatcher = $escort_path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\escort\EscortInterface $entity */
    if ($operation != 'view') {
      return parent::checkAccess($entity, $operation, $account);
    }

    if ($this->escortPathMatcher->isAdmin()) {
      return AccessResult::allowed();
    }

    // Don't grant access to disabled escorts.
    if (!$entity->status()) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }
    else {

      $conditions = [];
      $missing_context = FALSE;
      foreach ($entity->getVisibilityConditions() as $condition_id => $condition) {
        if ($condition instanceof ContextAwarePluginInterface) {
          try {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($condition->getContextMapping()));
            $this->contextHandler->applyContextMapping($condition, $contexts);
          }
          catch (ContextException $e) {
            $missing_context = TRUE;
          }
        }
        $conditions[$condition_id] = $condition;
      }

      if ($missing_context) {
        // If any context is missing then we might be missing cacheable
        // metadata, and don't know based on what conditions the escort is
        // accessible or not. For example, escorts that have a node type
        // condition will have a missing context on any non-node route like the
        // frontpage.
        // @todo Avoid setting max-age 0 for some or all cases, for example by
        //   treating available contexts without value differently in
        //   https://www.drupal.org/node/2521956.
        $access = AccessResult::forbidden()->setCacheMaxAge(0);
      }
      elseif ($this->resolveConditions($conditions, 'and') !== FALSE) {
        // Delegate to the plugin.
        $escort_plugin = $entity->getPlugin();
        try {
          if ($escort_plugin instanceof ContextAwarePluginInterface) {
            $contexts = $this->contextRepository->getRuntimeContexts(array_values($escort_plugin->getContextMapping()));
            $this->contextHandler->applyContextMapping($escort_plugin, $contexts);
          }
          $access = $escort_plugin->access($account, TRUE);
        }
        catch (ContextException $e) {
          // Setting access to forbidden if any context is missing for the same
          // reasons as with conditions (described in the comment above).
          // @todo Avoid setting max-age 0 for some or all cases, for example by
          //   treating available contexts without value differently in
          //   https://www.drupal.org/node/2521956.
          $access = AccessResult::forbidden()->setCacheMaxAge(0);
        }
      }
      else {
        $access = AccessResult::forbidden();
      }

      $this->mergeCacheabilityFromConditions($access, $conditions);

      // Ensure that access is evaluated again when the escort changes.
      return $access->addCacheableDependency($entity);
    }
  }

  /**
   * Merges cacheable metadata from conditions onto the access result object.
   *
   * @param \Drupal\Core\Access\AccessResult $access
   *   The access result object.
   * @param \Drupal\Core\Condition\ConditionInterface[] $conditions
   *   List of visibility conditions.
   */
  protected function mergeCacheabilityFromConditions(AccessResult $access, array $conditions) {
    foreach ($conditions as $condition) {
      if ($condition instanceof CacheableDependencyInterface) {
        $access->addCacheTags($condition->getCacheTags());
        $access->addCacheContexts($condition->getCacheContexts());
        $access->setCacheMaxAge(Cache::mergeMaxAges($access->getCacheMaxAge(), $condition->getCacheMaxAge()));
      }
    }
  }

}
