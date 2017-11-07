<?php

namespace Drupal\product_rest_api\Plugin\rest\resource;

use Drupal\aggregator\FeedInterface;
use Drupal\Core\Queue\QueueWorkerBase;
// use Drupal\Core\Session\AccountProxyInterface;
// use Drupal\rest\Plugin\ResourceBase;
// use Drupal\rest\ResourceResponse;
// use Symfony\Component\DependencyInjection\ContainerInterface;
// use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
// use Psr\Log\LoggerInterface;
use Drupal\products\Entity\Products;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 *@QueueWorker(
 *   id = "products_consumer",
 *   title = @Translation("Rest Consume refresh"),
 *   cron = {"time" = 60}
 *)
 */

class ResConsRefresh extends QueueWorkerBase {

  /**
  * {@inheritdoc}
  */
 public function processItem($data) {
   if ($data instanceof FeedInterface) {
     $data->refreshItems();
   }
 }
