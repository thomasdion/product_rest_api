<?php

namespace Drupal\product_rest_api\Controller;

use Drupal\Core\Controller\ControllerBase;
// use Drupal\rest\ResourceResponse;
// use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
/**
 * Class RestConsumeController.
 */
class RestConsumeController extends ControllerBase {



  // public function __construct(array $properties){}
  /**
   * Hello.
   *
   * @return string
   *   Return Hello string.
   */
  public function api(array $response) {

    // $response = new ResourseResponse($this->properties);
    // $response->addCacheableDependency($this->$properties);
    return new JsonResponse($response);
    // return [
    //   '#type' => 'markup',
    //   '#markup' => $this->t('Implement method: hello')
    // ];
  }

}
