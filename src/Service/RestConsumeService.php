<?php

namespace Drupal\product_rest_api\Service;
use Drupal\Core\DependencyInjection\Compiler\GuzzleMiddlewarePass;
use Drupal\Core\Session\AccountInterface;
/**
 * Class RestConsumeService.
 */
class RestConsumeService  {

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  // protected $currentUser;

  /**
   * The public API list.
   *
   */
   protected $apis;

  /**
   * Constructs a new RestConsumeService object.
   *   The api list provided by services.yml.
   */
  public function __construct($apis) {

       $this->apis = $apis;
    // $this->current_user = $current_user;
  }

  protected function getApi(string $api_name){

    switch($api_name) {
      case 'awardees':
        $api['endpoint'] = $this->apis[0]['url'];
        $api['headers'] = [
              'Accept'  => $this->apis[0]['Accept'],
              'http_errors'=>false,
        ];
        break;
      case 'breeds':
        $api['endpoint'] = $this->apis[1]['url'];
        $api['headers'] = [
              'Accept'  => $this->apis[1]['Accept'],
              'http_errors'=>false,
        ];
        break;
    }
    return $api;
  }

  public function consume(string $api_name) {


    $api = $this->getApi($api_name);
    $client   = \Drupal::httpClient();
    $response = $client->get($api['endpoint'], $api['headers']);
    //  $request = $client->createRequest('GET', 'http://chroniclingamerica.loc.gov/newspapers.json');
    return $response;
  }
}
