<?php

namespace Drupal\escort\Plugin\Escort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
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
  protected $defaultLinkIcon = 'fa-chevron-right';

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

    list($title, $icon) = $this->titleToTitleIcon($title);
    $attributes = $this->getUriAsAttributes($uri);

    $attributes['title'] = $title;

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

    // Icon support.
    if ($this->hasIconSupport()) {
      // Check if title has already been MiconIfied.
      if (!$title instanceof MiconIconize) {
        $title = MiconIconize::iconize($title);
      }
      if ($icon = $title->getIcon()) {
        $icon = $icon->getSelector();
      }
      else {
        $icon = $this->defaultLinkIcon;
      }
      $title = $title->getTitle();
    }

    return [$title, $icon];
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

  /**
   * Form element validation handler for the 'uri' element.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public static function validateUriElement($element, FormStateInterface $form_state, $form) {
    $uri = static::getUserEnteredStringAsUri($element['#value']);
    $form_state->setValueForElement($element, $uri);

    // If getUserEnteredStringAsUri() mapped the entered value to a 'internal:'
    // URI , ensure the raw value begins with '/', '?' or '#'.
    // @todo '<front>' is valid input for BC reasons, may be removed by
    //   https://www.drupal.org/node/2421941
    if (
      parse_url($uri, PHP_URL_SCHEME) === 'internal'
      && !in_array($element['#value'][0], ['/', '?', '#'], TRUE)
      && substr($element['#value'], 0, 7) !== '<front>'
    ) {
      $form_state->setError($element, t('Manually entered paths should start with /, ? or #.'));
      return;
    }
  }

  /**
   * Gets the URI without the 'internal:' or 'entity:' scheme.
   *
   * The following two forms of URIs are transformed:
   * - 'entity:' URIs: to entity autocomplete ("label (entity id)") strings;
   * - 'internal:' URIs: the scheme is stripped.
   *
   * This method is the inverse of ::getUserEnteredStringAsUri().
   *
   * @param string $uri
   *   The URI to get the displayable string for.
   *
   * @return string
   *   The URL string.
   *
   * @see static::getUserEnteredStringAsUri()
   */
  protected static function getUriAsDisplayableString($uri) {
    $scheme = parse_url($uri, PHP_URL_SCHEME);

    // By default, the displayable string is the URI.
    $displayable_string = $uri;

    // A different displayable string may be chosen in case of the 'internal:'
    // or 'entity:' built-in schemes.
    if ($scheme === 'internal') {
      $uri_reference = explode(':', $uri, 2)[1];

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      $path = parse_url($uri, PHP_URL_PATH);
      if ($path === '/') {
        $uri_reference = '<front>' . substr($uri_reference, 1);
      }

      $displayable_string = $uri_reference;
    }
    elseif ($scheme === 'entity') {
      list($entity_type, $entity_id) = explode('/', substr($uri, 7), 2);
      // Show the 'entity:' URI as the entity autocomplete would.
      $entity_manager = \Drupal::entityManager();
      if ($entity_manager->getDefinition($entity_type, FALSE) && $entity = \Drupal::entityManager()->getStorage($entity_type)->load($entity_id)) {
        $displayable_string = EntityAutocomplete::getEntityLabels(array($entity));
      }
    }

    return $displayable_string;
  }

  /**
   * Gets the user-entered string as a URI.
   *
   * The following two forms of input are mapped to URIs:
   * - entity autocomplete ("label (entity id)") strings: to 'entity:' URIs;
   * - strings without a detectable scheme: to 'internal:' URIs.
   *
   * This method is the inverse of ::getUriAsDisplayableString().
   *
   * @param string $string
   *   The user-entered string.
   *
   * @return string
   *   The URI, if a non-empty $uri was passed.
   *
   * @see static::getUriAsDisplayableString()
   */
  protected static function getUserEnteredStringAsUri($string) {
    // By default, assume the entered string is an URI.
    $uri = $string;

    // Detect entity autocomplete string, map to 'entity:' URI.
    $entity_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($string);
    if ($entity_id !== NULL) {
      // @todo Support entity types other than 'node'. Will be fixed in
      // https://www.drupal.org/node/2423093.
      $uri = 'entity:node/' . $entity_id;
    }
    // Detect a schemeless string, map to 'internal:' URI.
    elseif (!empty($string) && parse_url($string, PHP_URL_SCHEME) === NULL) {
      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      // - '<front>' -> '/'
      // - '<front>#foo' -> '/#foo'
      if (strpos($string, '<front>') === 0) {
        $string = '/' . substr($string, strlen('<front>'));
      }
      $uri = 'internal:' . $string;
    }

    return $uri;
  }

}
