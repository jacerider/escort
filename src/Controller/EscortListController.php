<?php

namespace Drupal\escort\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a controller to list escorts.
 */
class EscortListController extends ControllerBase {

  /**
   * Shows the escort administration page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function listing(Request $request = NULL) {
    return $this->entityTypeManager()->getListBuilder('escort')->render($request);
  }

  /**
   * Saves escort data in bulk.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json response to use via javascript.
   */
  public function updateEscorts(Request $request = NULL) {
    $response = [];
    $content = $request->getContent();
    if (!empty($content)) {
      $params = json_decode($content, TRUE);
      $escorts = $this->entityTypeManager()->getStorage('escort')->loadMultiple(array_keys($params));
      foreach ($escorts as $escort_id => $escort) {
        $update = FALSE;
        $data = $params[$escort_id];
        if (isset($data['region']) && $escort->getRegion() !== $data['region']) {
          $update = TRUE;
          $escort->setRegion($data['region']);
        }
        if (isset($data['weight']) && $escort->getWeight() !== $data['weight']) {
          $update = TRUE;
          $escort->setWeight($data['weight']);
        }
        if ($update) {
          $escort->save();
        }
      }
      $response['status'] = 'success';
    }
    else {
      $response['status'] = 'error';
      $response['message'] = $this->t('No POST information was found in request.');
    }
    return new JsonResponse($response);
  }

}
