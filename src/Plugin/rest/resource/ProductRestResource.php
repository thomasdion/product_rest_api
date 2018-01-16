<?php

namespace Drupal\product_rest_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\products\Entity\Products;
use Drupal\subproduct\Entity\SubProduct;
use Drupal\product_rest_api\Service\EntityService;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\ModifiedResourceResponse;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "product_rest_resource",
 *   label = @Translation("Product rest resource"),
 *   uri_paths = {
 *     "canonical" = "/api/products/{products}",
 *     "https://www.drupal.org/link-relations/create" = "/api/products"
 *   }
 * )
 */

class ProductRestResource extends ResourceBase {

  /**
   *  Drupal\Core\Access\CsrfRequestHeaderAccessCheck definition.
   *
   * @var \Drupal\Core\Access\CsrfRequestHeaderAccessCheck
   */
  protected $check_csrf;

  /**
   *  Drupal\product_rest_api\EntityService definition.
   *
   * @var \Drupal\product_rest_api\EntityService
   */
  protected $entityDB;

  /**
   * Constructs a new ProductRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   A current mysql Connection instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    CsrfRequestHeaderAccessCheck $access_check_header_csrf,
    EntityService $product_rest_api_entity_db) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->check_csrf = $access_check_header_csrf;
    $this->entityDB = $product_rest_api_entity_db;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('product_rest_api'),
      $container->get('access_check.header.csrf'),
      $container->get('product_rest_api.entity_db')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($id=NULL) {

    // You must to implement the logic of your REST Resource here.
    $products = [];
    try {
      $this->entityDB->setId($id);
      $entities = $this->entityDB->get_entity();
      if(empty($entities))
        throw new NotFoundHttpException('No products found');
      else {
        foreach($entities as $entity) {
          $id = $entity->id();
          $name = t($entity->getName());
          $description = t($entity->getDescription());
          $price = $entity->getPrice();
          $products[$id] = array(
            "name" => $name,
            "description" => $description,
            "price" => $price
          );
        }
      }
    }catch( InvalidPluginDefinitionException $e) {
      // \Drupal::logger('product_rest_api')->error("Error on entity load:".$e->getMessage());
      $this->logger->error("Error on entity load:".$e->getMessage());
      return new ResourceResponse(["message"=>"Problem on load entity"], 500);
    }catch( NotFoundHttpException $e) {
      // \Drupal::logger('product_rest_api')->notice("Unsuccesfull retrieval:".$e->getMessage());
      $this->logger->notice("Unsuccesfull retrieval:".$e->getMessage());
      return new ResourceResponse(["message"=>$e->getMessage()], 404);
    }catch( AccessDeniedHttpException $e) {
      $this->logger->notice($e->getMessage());
      return new ResourceResponse(["message"=>"You are not Authorized!"], 403);
    }
    $response = new ResourceResponse($products);
    $response->addCacheableDependency($products);
    return $response;
  }

  /**
   * Responds to POST requests.
   * Insert a new Product Entity.
   *
   * @param $data
   * @return \Drupal\rest\ResourceResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
    public function post($data) {

    // You must to implement the logic of your REST Resource here.
    try {
      $con_type = $_SERVER['CONTENT_TYPE'];
      if($con_type!='application/json' && $con_type!='application/hal+json')
         throw new NotAcceptableHttpException();
      // $request_token = $_SERVER['HTTP_X_CSRF_TOKEN'];
      // $request = \Drupal::request();
      // $this->check_csrf->access($request, $this->currentUser);
      $this->entityDB->setId($data["id"]);
      $entities = $this->entityDB->store_entity($data);
      // $this->store_products($data);
    }catch(NotAcceptableHttpException $ex) {
      $this->logger->notice($ex->getMessage());
      return new ResourceResponse(["message"=>"Not acceptable format"], 406);
    }catch(AccessDeniedHttpException $ex) {
      $this->logger->notice($ex->getMessage());
      return new ResourceResponse(["message"=>$ex->getMessage()], $ex->getCode());
    }catch(NotFoundHttpException $ex) {
      $this->logger->notice("Product Not Found:".$ex->getMessage());
      return new ResourceResponse(["message"=>"Product not Found"], 404);
    }catch(EntityStorageException $ex) {
      $this->logger->error($ex);
      return new ResourceResponse(["message"=>"Internal Service Error"],500);
    }catch(InvalidArgumentException $ex) {
      $this->logger->warning("Transaction Rollback:".$ex->getMessage());
      return new ResourceResponse(["message"=>"Problem on insert data"], 400);
    }catch(Exception $ex) {
      $this->logger->error($ex);
      return new ResourceResponse(["message"=>"Internal Service Error"],500);
    }
    $response = ["message"=>"New product added"];
    return new ResourceResponse($response, 201);
  }

  /**
   * Responds to DELETE requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function delete($id=NULL) {

    // You must to implement the logic of your REST Resource here.
    try {
      $this->entityDB->setId($id);
      $this->entityDB->delete_entity();
    }catch( InvalidPluginDefinitionException $ex) {
      $this->logger->error($ex);
      return new ModifiedResourceResponse(NULL, 500);
    }catch ( EntityStorageException $ex ) {
      $this->logger->error($ex);
      return new ModifiedResourceResponse(NULL, 500);
    }catch( AccessDeniedHttpException $e) {
      $this->logger->notice($e->getMessage());
      return new ModifiedResourceResponse(NULL, 403);
    }catch( NotFoundHttpException $e) {
      $this->logger->notice($e->getMessage());
      return new ModifiedResourceResponse(NULL, 404);
    }

    return new ModifiedResourceResponse(NULL, 204);
  }

  /**
   * Responds to PATCH requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function patch($id=NULL, $data=NULL) {

    // You must to implement the logic of your REST Resource here.
    try {
      $this->entityDB->check_access('update');
      $con_type = $_SERVER['CONTENT_TYPE'];
      if($con_type!='application/json' && $con_type!='application/hal+json')
        throw new NotAcceptableHttpException();
      $this->entityDB->setId($id);
      $this->entityDB->update_entity($data);
    }catch(NotAcceptableHttpException $ex) {
      $this->logger->notice($ex->getMessage());
      return new ResourceResponse(["message"=>"Not acceptable format"], 406);
    }catch(InvalidPluginDefinitionException  $ex) {
      $this->logger->error($ex);
      return new ResourceResponse(["message"=>"Internal Service Error"], 500);
    }catch(EntityStorageException $ex) {
      $this->logger->error($ex);
      return new ResourceResponse(["message"=>"Internal Service Error"], 500);
    }catch( AccessDeniedHttpException $e) {
      $this->logger->notice($ex->getMessage()."Code:".$e->getCode());
      return new ResourceResponse(["message"=>$e->getMessage()], $e->getCode());
    }catch( NotFoundHttpException $e) {
      $this->logger->notice($e->getMessage());
      return new ResourceResponse(["message"=>"Product not Found"], 404);
    }
    $response = ["message"=>"Product Updated"];
    return new ResourceResponse($response, 200);
  }

  // public function csrf_check($request_token) {
  //
  //   $this->check_csrf->access($current_user);
  //   return true;
    // if($request_token!==$csrf)
        //return false;
  // }

}
