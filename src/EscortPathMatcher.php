<?php

namespace Drupal\escort;

use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxy;

/**
 * Provides a path matcher.
 */
class EscortPathMatcher implements EscortPathMatcherInterface {

  /**
   * Whether the current page is an escort admin page.
   *
   * @var bool
   */
  protected $isAdmin;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Creates a new PathMatcher.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(PathMatcherInterface $path_matcher, RequestStack $request_stack, CurrentPathStack $current_path, AccountProxy $current_user) {
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdmin() {
    if (!isset($this->isAdmin)) {
      if (!$this->currentUser->hasPermission('administer escort')) {
        $this->isAdmin = FALSE;
      }
      else {
        $pages = ['/admin/config/escort', '/admin/config/escort/*'];
        $request = $this->requestStack->getCurrentRequest();
        // Compare the lowercase path alias (if any) and internal path.
        $path = $this->currentPath->getPath($request);
        // Do not trim a trailing slash if that is the complete path.
        $path = $path === '/' ? $path : rtrim($path, '/');
        // Set static variable.
        $this->isAdmin = $this->pathMatcher->matchPath($path, implode($pages, "\n"));
      }
    }
    return $this->isAdmin;
  }

}
