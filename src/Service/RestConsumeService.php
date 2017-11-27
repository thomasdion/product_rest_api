<?php
/**
* @file
 * Contains RestConsumeService.
 */

namespace Drupal\product_rest_api\Service;
use Drupal\Core\DependencyInjection\Compiler\GuzzleMiddlewarePass;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
// use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class RestConsumeService.
 */
class RestConsumeService  {

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
   protected $currentUser;

  /**
   * For locals' api  product retrieval. Though it could be used
   *in any endpoid we want
   *
   */
   public $id=NULL;

  /**
   * The public API list.
   *
   */
   protected $apis;

  /**
   * Constructs a new RestConsumeService object.
   *   The api list provided by services.yml.
   */
  public function __construct($apis, AccountProxyInterface $current_user) {

       $this->apis = $apis;
       $this->currentUser = $current_user;
  }

  protected function getApi(string $api_name){

    switch($api_name) {
      case 'awardees': //a public endpoint
        $api['endpoint'] = $this->apis[0]['url'];
        $api['headers'] = [
              'Accept'  => $this->apis[0]['Accept'],
              'http_errors'=>false,
        ];
        break;
      case 'breeds': // onother public endpoint
        $api['endpoint'] = $this->apis[1]['url'].$this->id;
        $api['headers'] = [
              'Accept'  => $this->apis[1]['Accept'],
              'http_errors'=>false,
        ];
        break;
        case 'rest_api': //our endpoint
          $api['endpoint'] = $this->apis[2]['url'].$this->id.'?_format=hal_json';
          // $api['endpoint'] = 'http://192.168.1.175/drupal8test/testview/fields/didaskalia/search_global?_format=hal_json&tmima_id=2&teacher_afm=057339344&aithousa_id=2&dateend=1509364970&datebegin=1509364970';
          $credentials = base64_encode('test2:1981');
          $api['headers'] = [
                'Accept'  => $this->apis[2]['Accept'],
                'Content-Type' => $this->apis[2]['Content-Type'],
                'http_errors'=>false,
                'Authorization'=>'Basic '.$credentials,
                'auth'=>['test2','1981'],
          ];
          break;
    }
    return $api;
  }

  public function check_access($api_name) {

    //for each endpoint we should set and check different permissions
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
    // if (!$this->currentUser->hasPermission('restful get product_rest_resource')) {
    if (!$this->currentUser->hasPermission($permission)) {      
      throw new AccessDeniedHttpException();
    }
  }

  public function consume(string $api_name) {

    $this->check_access('rest_api');
    $api = $this->getApi($api_name);
    $client   = \Drupal::httpClient();
    $response = $client->get($api['endpoint'], $api['headers']);
    //  $request = $client->createRequest('GET', 'http://chroniclingamerica.loc.gov/newspapers.json');
    return $response;
  }

  public function setId($id) {
    $this->id = $id;
  }
}
