<?php

namespace Drupal\escort;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\escort\Entity\EscortInterface;

/**
 * Provides a listing of Escort entities.
 */
class EscortListBuilder extends ConfigEntityListBuilder implements FormInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The region manager.
   *
   * @var \Drupal\escort\EscortRegionManagerInterface
   */
  protected $escortRegionManager;

  /**
   * Constructs a new EscortListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder, EscortRegionManagerInterface $escort_region_manager) {
    parent::__construct($entity_type, $storage);
    $this->formBuilder = $form_builder;
    $this->escortRegionManager = $escort_region_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('form_builder'),
      $container->get('escort.region_manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The escort list as a renderable array.
   */
  public function render(Request $request = NULL) {
    $this->request = $request;
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'escort_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'core/drupal.tableheader';
    $form['#attached']['library'][] = 'escort/admin';

    // Build the form tree.
    $form['escorts'] = $this->buildItemsForm();

    $form['actions'] = array(
      '#tree' => FALSE,
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Escorts'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * Builds the main "Escort escorts" portion of the form.
   *
   * @return array
   *   The form array.
   */
  protected function buildItemsForm() {
    $escorts = [];
    $regions = $this->escortRegionManager->getRegions(TRUE);
    $entities = $this->load();
    /** @var \Drupal\escort\EscortInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $definition = $entity->getPlugin()->getPluginDefinition();
      $region_id = $entity->getRegion();
      $region_id = isset($regions[$region_id]) ? $region_id : EscortInterface::ESCORT_REGION_NONE;
      $escorts[$region_id][$entity_id] = array(
        'label' => $entity->label(),
        'entity_id' => $entity_id,
        'weight' => $entity->getWeight(),
        'entity' => $entity,
        'category' => $definition['category'],
      );
    }

    $form = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Escort'),
        $this->t('Category'),
        $this->t('Type'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ),
      '#attributes' => array(
        'id' => 'escorts',
      ),
    );

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of escorts present. This makes sure all escorts in the same
    // region get an unique weight.
    $weight_delta = round(count($entities) / 2);

    $regions_with_disabled = $regions + array(EscortInterface::ESCORT_REGION_NONE => $this->t('Disabled', array(), array('context' => 'Plural')));
    foreach ($regions_with_disabled as $region => $title) {
      $form['#tabledrag'][] = array(
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'escort-region-select',
        'subgroup' => 'escort-region-' . $region,
        'hidden' => FALSE,
      );
      $form['#tabledrag'][] = array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'escort-weight',
        'subgroup' => 'escort-weight-' . $region,
      );

      $form['region-' . $region] = array(
        '#attributes' => array(
          'class' => array('region-title', 'region-title-' . $region),
          'no_striping' => TRUE,
        ),
      );
      $form['region-' . $region]['title'] = array(
        '#theme_wrappers' => array(
          'container' => array(
            '#attributes' => array('class' => 'region-title__action'),
          ),
        ),
        '#prefix' => $title,
        '#type' => 'link',
        '#title' => $this->t('Place escort <span class="visually-hidden">in the %region region</span>', ['%region' => $title]),
        '#url' => Url::fromRoute('escort.escort_library', [], ['query' => ['region' => $region]]),
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      );

      $form['region-' . $region . '-message'] = array(
        '#attributes' => array(
          'class' => array(
            'region-message',
            'region-' . $region . '-message',
            empty($escorts[$region]) ? 'region-empty' : 'region-populated',
          ),
        ),
      );
      $form['region-' . $region . '-message']['message'] = array(
        '#markup' => '<em>' . $this->t('No escorts in this region') . '</em>',
        '#wrapper_attributes' => array(
          'colspan' => 5,
        ),
      );

      if (isset($escorts[$region])) {
        foreach ($escorts[$region] as $info) {
          $entity_id = $info['entity_id'];

          $form[$entity_id] = array(
            '#attributes' => array(
              'class' => array('draggable'),
            ),
          );
          $form[$entity_id]['info'] = array(
            '#plain_text' => $info['label'],
            '#wrapper_attributes' => array(
              'class' => array('escort'),
            ),
          );
          $form[$entity_id]['category'] = array(
            '#markup' => $info['category'],
          );
          $form[$entity_id]['plugin_type'] = array(
            '#markup' => $info['entity']->getPluginId(),
          );
          $form[$entity_id]['region-theme']['region'] = array(
            '#type' => 'select',
            '#default_value' => $region,
            '#empty_value' => EscortInterface::ESCORT_REGION_NONE,
            '#title' => $this->t('Region for @item item', array('@item' => $info['label'])),
            '#title_display' => 'invisible',
            '#options' => $regions,
            '#attributes' => array(
              'class' => array('escort-region-select', 'escort-region-' . $region),
            ),
            '#parents' => array('escorts', $entity_id, 'region'),
          );
          $form[$entity_id]['weight'] = array(
            '#type' => 'weight',
            '#default_value' => $info['weight'],
            '#delta' => $weight_delta,
            '#title' => $this->t('Weight for @item item', array('@item' => $info['label'])),
            '#title_display' => 'invisible',
            '#attributes' => array(
              'class' => array('escort-weight', 'escort-weight-' . $region),
            ),
          );
          $form[$entity_id]['operations'] = $this->buildOperations($info['entity']);
        }
      }
    }

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entities = $this->storage->loadMultiple(array_keys($form_state->getValue('escorts')));
    /** @var \Drupal\escort\EscortInterface[] $entities */
    foreach ($entities as $entity_id => $entity) {
      $entity_values = $form_state->getValue(array('escorts', $entity_id));
      $entity->setWeight($entity_values['weight']);
      $entity->setRegion($entity_values['region']);
      if ($entity->getRegion() == EscortInterface::ESCORT_REGION_NONE) {
        $entity->disable();
      }
      else {
        $entity->enable();
      }
      $entity->save();
    }
    drupal_set_message(t('The escort settings have been updated.'));
  }

}
