<?php

namespace Drupal\product_rest_api\Service;
use Drupal\products\Entity\Products;
use Drupal\subproduct\Entity\SubProduct;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\Serializer\Exception\InvalidPluginDefinitionException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Class EntityService.
 */
class EntityService {

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $connection;

  /**
   * The entitys' id.
   *
   */
   public $id=NULL;

  /**
  * Constructs a new EntityService object.
  * Drupal\Core\Session\AccountProxy definition.
  * @param \Drupal\Core\Session\AccountProxyInterface $current_user
  * A current user instance.
  * @param \Drupal\product_rest_api\Service\RestConsumeService $rest_response
  *
  */
  public function __construct(AccountProxyInterface $current_user, Connection $database) {

    $this->currentUser = $current_user;
    $this->connection = $database;
  }

  public function setId($id) {
    $this->id = trim($id);
  }

  public function get_entity() {

    $this->check_access('get');
    if(!empty($this->id)) { //For single Product
      $ent_ret = \Drupal::entityQuery('products','AND')
                ->condition('id', $this->id)
                ->execute();
      try {
        $storage_handler = \Drupal::entityTypeManager()->getStorage('products');
        $entities = $storage_handler->loadMultiple($ent_ret);
      }catch( InvalidPluginDefinitionException $e) {
        throw new InvalidPluginDefinitionException($e->getMessage());
      }
    }else{ // Load all Products
      $entities = Products::loadMultiple();
    }
    return $entities;
  }

  public function delete_entity(){

    $this->check_access('delete');
    if(!empty($this->id)) {
      // $current_user = \Drupal::currentUser()->id();
      $user_id = $this->currentUser->id();
      $ent_ret = \Drupal::entityQuery('products','AND')
                ->condition('user_id', $user_id)
                ->condition('id', $this->id)
                ->execute();
      if(empty($ent_ret)) {
           throw new NotFoundHttpException("No entity found", NULL, 404);
        }
      try{
        $storage_handler = \Drupal::entityTypeManager()->getStorage('products');
        $entities = $storage_handler->loadMultiple($ent_ret);
        $storage_handler->delete($entities);
      }catch( InvalidPluginDefinitionException $e) {
        throw new InvalidPluginDefinitionException($e->getMessage());
      }
    } else {
      throw new NotFoundHttpException('No entity id given',NULL, 404);
    }
  }

  public function update_entity($data){

    // $this->check_access('update');
    if(!empty($this->id)) {
      $user_id = $this->currentUser->id();
      $ent_ret = \Drupal::entityQuery('products', 'AND')
        ->condition('user_id' , $user_id)
        ->condition('id', $this->id)
        ->execute();
      if(empty($ent_ret)) {
          throw new NotFoundHttpException("Product not found",NULL, 404);
      }
      try{
        $storage_handler = \Drupal::entityTypeManager()->getStorage('products');
        $entity = $storage_handler->loadMultiple($ent_ret);
        $product = $entity[$this->id];
        $product->setName($data["name"])
          ->setDescription($data["description"])
          ->setPrice($data["price"]);
        $product->save();
      }catch(InvalidPluginDefinitionException  $ex) {
        throw new InvalidPluginDefinitionException($e->getMessage());
      }catch(EntityStorageException $ex) {
        throw new EntityStorageException($e->getMessage());
      }
    }else {
      throw new NotFoundHttpException('No entity id given',NULL, 404);
    }
  }

  public function store_entity($data) {

    $this->check_access('post');
    $productId = $this->id;
    $subproducts = $data["subproducts"];
    // $database = \Drupal::database();
    $transaction = $this->connection->startTransaction();
    try {
      if(!empty($productId)) { //For single Product
        $ent_ret = \Drupal::entityQuery('products','AND')
                  ->condition('id', $productId)
                  ->execute();
        $storage_handler = \Drupal::entityTypeManager()->getStorage('products');
        $product = $storage_handler->loadMultiple($ent_ret);
      } else {
        throw new NotFoundHttpException('No product selected');
      }
      if(empty($product))
        throw new NotFoundHttpException('No product category found');
      foreach($subproducts as $spdata) {
        $sname = trim($spdata["name"]);
        $sdescription = trim($spdata["description"]);
        $sprice = trim($spdata["price"]);
        if($sname==NULL || $sdescription==NULL || $sprice==NULL)
          throw new InvalidArgumentException('Insufficient data');
        $subProduct = SubProduct::create();
        $subProduct->setProductId($productId);
        $subProduct->setName($sname);
        $subProduct->setDescription($sdescription);
        $subProduct->setPrice($sprice);
        $subProduct->save();
      }
    }
    catch(NotFoundHttpException $ex) {
      throw new NotFoundHttpException($ex);
    }catch(InvalidArgumentException $ex) {
      $transaction->rollBack();
      throw new InvalidArgumentException($ex);
    }catch(Exception $ex) {
      $transaction->rollBack();
      throw new InvalidArgumentException($ex->getMessage());
    }
  }

  public function check_access($method) {

    //for each endpoint we should set and check different permissions
    $permission='access content';
    switch($method) {
      case 'get':
      $permission='restful get product_rest_resource';
      break;
      case 'delete':
      $permission='restful delete product_rest_resource';
      break;
      case 'update':
      $permission='restful patch product_rest_resource';
      case 'post':
      $permission='restful post product_rest_resource';
      break;
    }
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission($permission)) {
      throw new AccessDeniedHttpException("Permission denied:EntityService:check_access",null,401);
    }
  }

}
