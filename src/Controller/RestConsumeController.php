<?php

namespace Drupal\product_rest_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
/**
 * Class RestConsumeController.
 */
class RestConsumeController extends ControllerBase {

  /**
   * Hello.
   *
   * @return string
   *   Return data from the endpoind
   *   on /test2/product_rest_api/rest/test
   *
   */
  public function get($id=NULL) {

    drupal_set_message("Internaly Consuming Local Drupals RestResource");
    try {
      $rest = \Drupal::service('product_rest_api.rest_consume');
      $rest->setId($id);
      //should send conditionally api names for different endpoinds
      //must import currentUser interface to work!
      // $this->check_access('rest_api'); 
      $response = $rest->consume('rest_api');
      $con_type = $response->getHeader('Content-Type');
      if($con_type[0]!='application/json' && $con_type[0]!='application/hal+json')
         throw new AccessDenniedException("406 Not Acceptable");
      $body_response = json_decode($response->getBody()->getContents(), TRUE);
      // drupal_set_message($body_response);
    }catch(AccessDeniedHttpException $ex) {
      \Drupal::logger('product_rest_api')->warning($ex);
    }catch(RequestException $ex) {
      \Drupal::logger('product_rest_api')->error($ex);
    }catch(InvalidArgumentException $ex) {
      \Drupal::logger('product_rest_api')->error($ex);
    }
    return [  //should conditionally return themes
      '#theme' => 'product_rest_api',
      '#title' => $this->t('Internaly Consuming Local Drupals RestResource'),
      '#products' => $body_response,
    ];
  }

  public function check_access($api_name) {

    $permission='access content';
    switch($api_name) {
      case 'awardees':
      $permission='access content';
      break;
      case 'breeds':
      $permission='access content';
      break;
      case 'rest_api':
      $permission='restful get product_rest_resource';
      break;
    }
    if (!$this->currentUser->hasPermission('restful get product_rest_resource')) {
      throw new AccessDeniedHttpException();
    }
  }

}
