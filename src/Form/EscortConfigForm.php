<?php

namespace Drupal\escort\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class EscortConfigForm.
 *
 * @package Drupal\escort\Form
 */
class EscortConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;
  public function __construct(
    ConfigFactoryInterface $config_factory,
      EntityTypeManager $entity_type_manager
    ) {
    parent::__construct($config_factory);
        $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
            $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'escort.escortconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'escort_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('escort.escortconfig');
    $form['awesome'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Awesome?'),
      '#description' => $this->t('Yes. It is awesome.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('awesome'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('escort.escortconfig')
      ->set('awesome', $form_state->getValue('awesome'))
      ->save();
  }

}
