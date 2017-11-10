<?php

namespace Drupal\product_rest_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;
use Drupal\award\Entity\AwardEntity;
 /**
  * Provides a resource to get view modes by entity and bundle.
  *
  * @RestResource(
  *   id = "consume_rest_rersource",
  *   label = @Translation("Consume award rest resource"),
  *   uri_paths = {
  *     "canonical" = "/api/rest/awards"
  *   }
  * )
  */

class ConsumeRestRersource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ConsumeRestRersource object.
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
   public function get() {

     // You must to implement the logic of your REST Resource here.
     // Use current user after pass authentication to validate access.
     if (!$this->currentUser->hasPermission('access content')) {
       throw new AccessDeniedHttpException();
     }
      $entities = AwardEntity::loadMultiple();
      $awards = array();
      if(!empty($entities)) {
        foreach($entities as $entity) {
         $id = $entity->id();
         $name = $entity->getName();
         $url = $entity->getUrl();
         $awards[$id] = array(
           "name" => $name,
           "url" => $url
        );
       }
     } else {
         throw new NotFoundHttpException('No products found');
     }
     // $output = $this->serializer->serialize($products, 'json');
     // $response = new ResourceResponse($output);
     // $response->addCacheableDependency($output);
     // $response = ['message' => 'Hello, this is a rest service'];
     $response = new ResourceResponse($awards);
     $response->addCacheableDependency($awards);
     return $response;
   }

}
