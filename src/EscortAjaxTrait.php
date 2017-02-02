<?php

namespace Drupal\escort;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\ux_dialog\Ajax\CloseUxDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;

/**
 * A trait that provides dialog utilities.
 */
trait EscortAjaxTrait {

  /**
   * Add attributes that will open a dialog window.
   *
   * @param array &$build
   *   A render array.
   * @param int $width
   *   The size of the dialog window.
   * @param int $attribute_key
   *   The attribute key.
   * @param bool $attach_library
   *   Attach ajax library.
   */
  protected function ajaxLinkAttributes(&$build, $width = NULL, $attribute_key = '#attributes', $attach_library = TRUE) {
    $build[$attribute_key] = isset($build[$attribute_key]) ? $build[$attribute_key] : [];
    $build[$attribute_key]['class'][] = escort_ajax_class();
    $build[$attribute_key]['data-dialog-type'] = escort_dialog_type();
    $build[$attribute_key]['data-dialog-options'] = Json::encode([
      'width' => !is_null($width) ? $width : 700,
    ]);
    if ($attach_library) {
      $build['#attached']['library'][] = escort_dialog_library();
    }
  }

  /**
   * Bind ajax callbacks to buttons.
   *
   * @param array &$build
   *   A render array.
   */
  protected function ajaxSubmitAttributes(&$build) {
    $build['#ajax']['callback'] = '::ajaxCallbackRefreshEscort';
    $build['#ajax']['progress']['type'] = 'fullscreen';
  }

  /**
   * Refresh escort.
   */
  public function ajaxCallbackRefreshEscort($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseUxDialogCommand());
    $response->addCommand(new ReplaceCommand('#escort', escort_render()));
    $response->addCommand(new PrependCommand('#ux-document', ['#type' => 'status_messages']));
    return $response;
  }

}
