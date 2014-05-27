<?php

namespace Imgur;

use Encase\Container;

class AlbumRepoTest extends \PHPUnit_Framework_TestCase {

  public $container;
  public $cred;
  public $adapter;
  public $repo;
  public $imageRepo;
  public $clientId;
  public $clientSecret;

  function setUp() {
    $this->container = new Container();
    $this->container
      ->factory('imgurRequest', 'Imgur\Request')
      ->singleton('imgurCredentials', 'Imgur\Credentials')
      ->singleton('imgurAdapter', 'Imgur\Adapter')
      ->singleton('imgurImageRepo', 'Imgur\ImageRepo')
      ->singleton('imgurAlbumRepo', 'Imgur\AlbumRepo');

    $this->cred    = $this->container->lookup('imgurCredentials');
    $this->adapter = $this->container->lookup('imgurAdapter');
    $this->repo    = $this->container->lookup('imgurAlbumRepo');
    $this->imageRepo    = $this->container->lookup('imgurImageRepo');

    $this->clientId = getenv('IMGUR_CLIENT_ID');
    $this->clientSecret = getenv('IMGUR_CLIENT_SECRET');
    $this->cred->setClientId($this->clientId);
  }

  function test_it_has_album_model_name() {
    $this->assertEquals('album', $this->repo->getModel());
  }

  function test_it_can_find_details_of_album() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $album = $this->repo->find('V9E5t');
    $this->assertEquals('V9E5t', $album['id']);
  }

  function test_it_can_create_new_album() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array(
      'title' => 'test_album',
    );

    $album = $this->repo->create($params);
    $deleteHash = $album['deletehash'];
    $this->assertNotEquals('', $album['deletehash']);

    $album = $this->repo->find($album['id']);
    $this->assertEquals('test_album', $album['title']);

    $this->repo->delete($deleteHash);
  }

  function test_it_can_update_album() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array(
      'title' => 'test_album',
    );

    $album = $this->repo->create($params);
    $deleteHash = $album['deletehash'];
    $new_params = array('title' => 'new_test_album');

    $success = $this->repo->update($deleteHash, $new_params);
    $album = $this->repo->find($album['id']);

    $this->assertEquals('new_test_album', $album['title']);
    $this->repo->delete($deleteHash);
  }

  function test_it_can_delete_album() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array(
      'title' => 'test_album',
    );
    $album = $this->repo->create($params);
    $this->repo->delete($album['deletehash']);

    $this->setExpectedException('Imgur\Exception');
    $album = $this->repo->find($album['id']);
  }

  function test_it_can_add_images_to_album() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $accessToken  = getenv('IMGUR_ACCESS_TOKEN');
    $refreshToken = getenv('IMGUR_REFRESH_TOKEN');
    if (!$accessToken || !$refreshToken) return;

    $this->cred->setClientId($this->clientId);
    $this->cred->setClientSecret($this->clientSecret);
    $this->cred->setAccessToken($accessToken);
    $this->cred->setRefreshToken($refreshToken);
    $this->cred->setAccessTokenExpiry(0);

    $params = array(
      'title' => 'test_image',
      'image' => 'http://wordpress.org/about/images/wordpress-logo-stacked-bg.png',
      'type'  => 'URL'
    );

    $image1 = $this->imageRepo->create($params);
    $image2 = $this->imageRepo->create($params);

    $params = array(
      'title' => 'test_album'
    );
    $album = $this->repo->create($params);
    $ids = array(
      $image1['id'], $image2['id']
    );
    $result = $this->repo->addImages($album['id'], $ids);
    $this->assertTrue($result);
  }

  function test_it_can_add_images_to_anonymous_album() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $this->cred->setClientId($this->clientId);

    $params = array(
      'title' => 'test_image',
      'image' => 'http://wordpress.org/about/images/wordpress-logo-stacked-bg.png',
      'type'  => 'URL'
    );

    $image1 = $this->imageRepo->create($params);
    $image2 = $this->imageRepo->create($params);

    $params = array(
      'title' => 'test_album',
      'ids' => array(
        $image1['id'], $image2['id']
      )
    );
    $album = $this->repo->create($params);
    $this->assertNotEquals('', $album['id']);
  }

}
