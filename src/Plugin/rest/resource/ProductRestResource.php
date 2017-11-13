<?php

namespace Drupal\product_rest_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Drupal\products\Entity\Products;
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
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
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
      $container->get('current_user')
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
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $id = trim($id);
    if(!empty($id)) { //For single Product
      $ent_ret = \Drupal::entityQuery('products','AND')
                ->condition('id', $id)
                ->execute();
      try {
        $storage_handler = \Drupal::entityTypeManager()->getStorage('products');
        $entities = $storage_handler->loadMultiple($ent_ret);
       } catch( InvalidPluginDefinitionException $e) {
           return new ResourceResponse(["message"=>"Problem on load entity"]);
         }
    }else{ // Load all Products
      $entities = Products::loadMultiple();
      $products = array();
      }
    if(!empty($entities)) {
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
    } else {
        throw new NotFoundHttpException('No products found');
        // return new ResourceResponse(["message"=>"No entries found!"]);
    }
    $response = new ResourceResponse($products);
    $response->addCacheableDependency($products);
    return $response;
  }

  /**
   * Responds to POST requests.
   * Insert a new Product Entity.
   *
   * @param $name
   * @param $description
   * @return \Drupal\rest\ResourceResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
    // public function post(array $data=[]) {
    //  public function post($data, $node_type) {
     public function post($data) {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
       throw new AccessDeniedHttpException();
    }
      $name = $data["name"];
      $description = $data["description"];
      $price = $data["price"];
      $entity = Products::create();
      $entity->setName($name);
      $entity->setDescription($description);
      $entity->setPrice($price);
      try {
        $entity->save();
     }catch(EntityStorageException $ex) {
      //  \Drupal::logger('product_rest_api')->error($ex);
       return new ResourceResponse(["message"=>"Problem on Entity save"],500);
    }
    $response = ["message"=>"New product added"];
    return new ResourceResponse($response, 204);
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
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $id = trim($id);
    // $response = ["message"=>"Product with doesnt exist"];
    if(!empty($id)) {
      $current_user = \Drupal::currentUser()->id();
      $ent_ret = \Drupal::entityQuery('products','AND')
                ->condition('user_id', $current_user)
                ->condition('id', $id)
                ->execute();
      if(empty($ent_ret)) {
        // \Drupal::logger('product_rest_api')->error($ex);
          //  throw new NotFoundHttpException(NULL, 404);
           return new ModifiedResourceResponse(NULL, 404);
        }
      try {
        $storage_handler = \Drupal::entityTypeManager()->getStorage('products');
        // $storage_handler = \Drupal::EntityTypeManagerInterface()->getStorage('products');
        $entities = $storage_handler->loadMultiple($ent_ret);
        $storage_handler->delete($entities);
        // $response = ["message"=>"Product with id was deleted!"];
    } catch( InvalidPluginDefinitionException $ex) {
        // \Drupal::logger('product_rest_api')->error($ex);
        return new ResourceResponse(["message"=>"Product not Found"], 500);
    } catch ( EntityStorageException $ex ) {
        // \Drupal::logger('product_rest_api')->error($ex);
        return new ResourceResponse(["message"=>"Product not Found"], 500);
    }
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
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $id = trim($id);
    $response = ["message"=>"Product not found"];
    if(!empty($id)) {
      $current_user=\Drupal::currentUser()->id();
      $ent_ret = \Drupal::entityQuery('products', 'AND')
        ->condition('user_id' , $current_user)
        ->condition('id', $id)
        ->execute();
      if(empty($ent_ret)) {
          // throw new NotFoundHttpException("Product not found");
          return new ResourceResponse(["message"=>"Product not Found"], 404);
      }
      try {
        $storage_handler = \Drupal::entityTypeManager()->getStorage('products');
        $entity = $storage_handler->loadMultiple($ent_ret);
        $product = $entity[$id];
        $product->setName($data["name"])
          ->setDescription($data["description"])
          ->setPrice($data["price"]);
        $product->save();
      }
      catch(InvalidPluginDefinitionException  $ex) {
        // \Drupal::logger('product_rest_api')->error($ex);
        return new ResourceResponse(["message"=>"Product not Found"], 500);
      }
      catch(EntityStorageException $ex) {
        // \Drupal::logger('product_rest_api')->error($ex);
        return new ResourceResponse(["message"=>"Product not Found"], 500);
      }
      $response = ["message"=>"Product Updated"];
    }
    return new ResourceResponse($response, 200);
  }

}
