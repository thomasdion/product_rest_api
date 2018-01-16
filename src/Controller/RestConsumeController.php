<?php
/**
 * @file
 * Contains \Drupal\product_rest_api\Controller\RestConsumeController.
 */
namespace Drupal\product_rest_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\product_rest_api\Service\RestConsumeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class RestConsumeController.
 */
class RestConsumeController extends ControllerBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Rest Service
   *
   * @var \Drupal\product_rest_api\Service\RestConsumeService
   */
  protected $rest;

  /**
   * Constructs a new RestConsumeController object.
   * Drupal\Core\Session\AccountProxy definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * A current user instance.
   * @param \Drupal\product_rest_api\Service\RestConsumeService $rest_response
   *
   */
  public function __construct(AccountInterface $current_user, RestConsumeService $product_rest_api_rest_consume) {
    $this->currentUser = $current_user;
    $this->rest = $product_rest_api_rest_consume;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      //Injecting our Custom Service
      $container->get('product_rest_api.rest_consume')
    );
  }

  /**
   *
   * @return string
   *   Return data from the endpoind
   *   on /test2/product_rest_api/rest/test
   *
   */
  public function get($id=NULL, $api=NULL) {
    //Consume an endpoint(public or local) from our service and export it as html

    drupal_set_message("Dynamically Consuming Endpoints");
    try {
      //We previously loaded our service statically
      // $rest = \Drupal::service('product_rest_api.rest_consume');
      $this->rest->setId($id);
      // $this->check_access('rest_api');
      //Here we could dynamically ask for different endpoinds
      $response = $this->rest->consume($api);
      // $response = $this->rest->consume('rest_api');
      $con_type = $response->getHeader('Content-Type');
      if($con_type[0]!='application/json' && $con_type[0]!='application/hal+json')
        throw new NotAcceptableHttpException();
        //  throw new AccessDenniedException("406 Not Acceptable");
      $body_response = json_decode($response->getBody()->getContents(), TRUE);
      // drupal_set_message($body_response);
    }
    catch(UnauthorizedHttpException $ex) {
      \Drupal::logger('product_rest_api')->warning($ex);
      drupal_set_message('Access Denied');
      $body_response = 'Access Denied';
    }catch(NotAcceptableHttpException $ex) {
      \Drupal::logger('product_rest_api')->warning($ex);
      drupal_set_message("Not Acceptable format");
      $body_response = 'Not Acceptable format';
    }catch(RequestException $ex) {
      \Drupal::logger('product_rest_api')->error($ex);
      drupal_set_message("Access Denied");
      $body_response = 'Access Denied';
    }catch(InvalidArgumentException $ex) {
      \Drupal::logger('product_rest_api')->error($ex);
      drupal_set_message("Access Denied");
      $body_response = 'Access Denied';
    }
    return [  //should conditionally return themes
      '#theme' => 'product_rest_api',
      '#title' => $this->t('Consuming Local/public endpoints'),
      '#products' => $body_response,
    ];
  }
}
