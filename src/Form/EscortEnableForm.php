<?php

namespace Drupal\escort\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\escort\Entity\EscortInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class EscortEnableForm extends FormBase {

  /**
   * The current Request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(Request $request, EntityTypeManagerInterface $entity_type_manager) {
    $this->request = $request;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'escort_enable_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Load disabled escorts.
    $escorts = $this->entityTypeManager->getStorage('escort')->loadByProperties(['region' => EscortInterface::ESCORT_REGION_NONE]);

    $headers = [
      ['data' => $this->t('Escort')],
      ['data' => $this->t('Category')],
      ['data' => $this->t('Operations')],
    ];

    $form['escorts'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#empty' => $this->t('No disabled escorts available.'),
      '#attributes' => [
        'class' => ['escort-add-table'],
      ],
    ];

    foreach ($escorts as $escort_id => $escort) {
      $plugin = $escort->getPlugin();
      $plugin_definition = $plugin->getPluginDefinition();
      $form['escorts'][$escort_id]['title']['#plain_text'] = $escort->label();
      $form['escorts'][$escort_id]['category']['#plain_text'] = $plugin_definition['category'];
      $form['escorts'][$escort_id]['operations'] = [
        '#type' => 'submit',
        '#value' => $this->t('Place escort'),
        '#name' => $escort->id(),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $escort_id = $trigger['#name'];
    $escort = $this->entityTypeManager->getStorage('escort')->load($escort_id);
    if ($escort) {
      $region = $this->request->query->get('region');
      $weight = $this->request->query->get('weight');
      $escort->setRegion($region);
      $escort->setWeight($weight);
      $escort->save();
      drupal_set_message($this->t('Enabled the %label Escort.', [
        '%label' => $escort->label(),
      ]));
    }
    $form_state->setRedirect('escort.escort_list');
  }

}
