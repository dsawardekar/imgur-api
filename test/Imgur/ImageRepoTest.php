<?php

namespace Imgur;

use Encase\Container;

class ImageRepoTest extends \PHPUnit_Framework_TestCase {

  public $container;
  public $cred;
  public $adapter;
  public $repo;
  public $clientId;

  function setUp() {
    $this->container = new Container();
    $this->container
      ->factory('imgurRequest', 'Imgur\Request')
      ->object('imgurCredentials', CachedCredentials::getInstance())
      ->singleton('imgurAdapter', 'Imgur\Adapter')
      ->singleton('imgurImageRepo', 'Imgur\ImageRepo');

    $this->cred    = $this->container->lookup('imgurCredentials');
    $this->adapter = $this->container->lookup('imgurAdapter');
    $this->repo    = $this->container->lookup('imgurImageRepo');

    $this->cred->clear();
    $this->cred->setClientId(getenv('IMGUR_CLIENT_ID'));
  }

  function test_it_has_image_model_name() {
    $this->assertEquals('image', $this->repo->getModel());
  }

  function test_it_can_find_details_of_image() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $image = $this->repo->find('YClNJML');
    $this->assertEquals('YClNJML', $image['id']);
  }

  function test_it_can_create_new_image_from_local_file() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $file = file_get_contents('test/images/wordpress-logo.png');
    $params = array(
      'title' => 'test_image',
      'image' => base64_encode($file),
      'type'  => 'base64'
    );

    $image = $this->repo->create($params);
    $deleteHash = $image['deletehash'];
    $this->assertNotEquals('', $image['deletehash']);

    $image = $this->repo->find($image['id']);
    $this->assertEquals('test_image', $image['title']);

    $this->repo->delete($deleteHash);
  }

  function test_it_can_create_new_image_from_remote_file() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array(
      'title' => 'test_image',
      'image' => 'http://wordpress.org/about/images/wordpress-logo-stacked-bg.png',
      'type'  => 'URL'
    );

    $image = $this->repo->create($params);
    $deleteHash = $image['deletehash'];
    $this->assertNotEquals('', $image['deletehash']);

    $image = $this->repo->find($image['id']);
    $this->assertEquals('test_image', $image['title']);

    $this->repo->delete($deleteHash);
  }

  function test_it_can_update_image() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array(
      'title' => 'test_image',
      'image' => 'http://wordpress.org/about/images/wordpress-logo-stacked-bg.png',
      'type'  => 'URL'
    );

    $image = $this->repo->create($params);
    $deleteHash = $image['deletehash'];
    $new_params = array('title' => 'new_test_image');

    $success = $this->repo->update($deleteHash, $new_params);
    $image = $this->repo->find($image['id']);

    $this->assertEquals('new_test_image', $image['title']);
    $this->repo->delete($deleteHash);
  }

  function test_it_can_delete_image() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array(
      'title' => 'test_image',
      'image' => 'http://wordpress.org/about/images/wordpress-logo-stacked-bg.png',
      'type'  => 'URL'
    );
    $image = $this->repo->create($params);
    $this->repo->delete($image['deletehash']);

    $this->setExpectedException('Imgur\Exception');
    $image = $this->repo->find($image['id']);
  }

}
