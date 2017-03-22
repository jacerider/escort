<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\micon\MiconIconize;

/**
 * A trait that provides link utilities.
 */
trait EscortPluginLinkTrait {

  /**
   * The default icon to use when an icon is not set.
   *
   * @var string
   */
  protected $defaultLinkIcon = 'fa-circle-o';

  /**
   * Build a link given a title and uri or Drupal\Core\Url.
   *
   * @param string $title
   *   The title of the link.
   * @param mixed $uri
   *   The uri or Drupal\Core\Url of the link.
   *
   * @return array
   *   A render array.
   */
  public function buildLink($title, $uri) {
    $title = $this->titleAndUrlToIcon($title, $uri);
    list($title, $icon) = $this->titleToTitleIcon($title);
    $attributes = $this->getUriAsAttributes($uri);
    $options = $uri->getOptions();

    $attributes['title'] = $title;

    // Support icon set in url attributes.
    if (!empty($options['attributes']['data-icon'])) {
      $icon = $options['attributes']['data-icon'];
    }

    return [
      '#tag' => 'a',
      '#icon' => $icon,
      '#attributes' => $attributes,
      '#markup' => $title,
    ];
  }

  /**
   * Given a title, return an array containing title and icon.
   *
   * @param string $title
   *   The title of the link.
   *
   * @return array
   *   An array containing a title and icon key.
   */
  public function titleToTitleIcon($title) {
    $title = $title;
    $icon = '';

    // Check if title has already been MiconIfied.
    if (!$title instanceof MiconIconize) {
      $title = $this->titleToIcon($title);
    }
    if ($icon = $title->getIcon()) {
      $icon = $icon->getSelector();
    }
    else {
      $icon = $this->getDefaultLinkIcon();
    }
    $title = $title->getTitle();

    return [$title, $icon];
  }

  /**
   * Givent a title, return as Micon object.
   *
   * @param string $title
   *   The title of the link.
   */
  public function titleToIcon($title, $prefix = 'escort') {
    if (!$title instanceof MiconIconize) {
      $title = MiconIconize::iconize($title);
      if (!$title->getIcon()) {
        $title->setIcon($this->getDefaultLinkIcon());
      }
    }
    $title->addMatchPrefix($prefix);
    return $title;
  }

  /**
   * Givent a title, return as Micon object.
   *
   * @param string $title
   *   The title of the link.
   */
  public function titleAndUrlToIcon($title, $uri, $prefix = 'escort') {
    $title = $this->titleToIcon($title);
    if ($url = $this->getUrl($uri)) {
      $options = $url->getOptions();
      if (!empty($options['attributes']['data-icon'])) {
        $title->setIcon($options['attributes']['data-icon']);
      }
    }
    return $title;
  }

  /**
   * Convert a uri or Drupal\Core\Url into Drupal\Core\Url.
   *
   * @var mixed $uri
   *  A uri or Drupal\Core\Url.
   */
  public function getUrl($uri) {
    if ($uri instanceof Url) {
      $url = $uri;
    }
    else {
      $url = Url::fromUri($uri);
    }
    return $url;
  }

  /**
   * Convert a uri or Drupal\Core\Url into attributes.
   *
   * @var mixed $uri
   *  A uri or Drupal\Core\Url.
   */
  public function getUriAsAttributes($uri) {
    $attributes = [];
    if ($url = $this->getUrl($uri)) {
      // External URLs can not have cacheable metadata.
      if ($url->isExternal()) {
        $href = $url->toString(FALSE);
      }
      elseif ($url->isRouted() && $url->getRouteName() === '<nolink>') {
        $href = '';
      }
      else {
        $generated_url = $url->toString(TRUE);
        // The result of the URL generator is a plain-text URL to use as the
        // href attribute, and it is escaped by \Drupal\Core\Template\Attribute.
        $href = $generated_url->getGeneratedUrl();

        if ($url->isRouted()) {
          // Set data element for active link setting.
          // @TODO Drupal's active-link.js seems to not work for this. Why?
          $system_path = $url->getInternalPath();
          // Special case for the front page.
          $attributes['data-escort-path'] = $system_path == '' ? '<front>' : $system_path;
        }
      }
      $attributes['href'] = $href;
    }
    return $attributes;
  }

  /**
   * Get the default icon.
   */
  public function getDefaultLinkIcon() {
    return $this->defaultLinkIcon;
  }

  /**
   * Check if user has access to page.
   *
   * @param mixed $uri
   *   The uri or \Drupal\Core\Url object.
   *
   * @return Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function uriAccess($uri) {
    if (!empty($uri)) {
      if ($uri instanceof Url) {
        $url = $uri;
      }
      else {
        $url = Url::fromUri($uri);
      }
      return $url->access() ? AccessResult::allowed() : AccessResult::forbidden();
    }
    return AccessResult::forbidden();
  }

}
