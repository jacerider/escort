<?php

namespace Drupal\escort;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\micon\MiconIconize;

/**
 * Defines a class to build a listing of node entities.
 *
 * @see \Drupal\node\Entity\Node
 */
class EscortEntityListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The entity types to use.
   *
   * @var array
   */
  protected $types = [];

  /**
   * {@inheritdoc}
   */
  public function setTypes($types = []) {
    $this->types = $types;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    if (!empty($this->types)) {
      $query->condition($this->entityType->getKey('bundle'), $this->types, 'IN');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = array(
      'title' => $this->t('Title'),
      'status' => $this->t('Status'),
    );
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = array(
        'data' => $this->t('Language'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      );
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\node\NodeInterface $entity */
    $mark = array(
      '#theme' => 'mark',
      '#mark_type' => node_mark($entity->id(), $entity->getChangedTime()),
    );
    $langcode = $entity->language()->getId();
    $uri = $entity->urlInfo();
    $options = $uri->getOptions();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? array('language' => $languages[$langcode]) : array());
    $uri->setOptions($options);
    $row['title']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->label(),
      '#suffix' => ' ' . drupal_render($mark),
      '#url' => $uri,
    );
    $row['status'] = MiconIconize::iconize($entity->isPublished() ? $this->t('published') : $this->t('not published'))->setIconOnly();
    $language_manager = \Drupal::languageManager();
    if ($language_manager->isMultilingual()) {
      $row['language_name'] = $language_manager->getLanguageName($langcode);
    }
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

}
