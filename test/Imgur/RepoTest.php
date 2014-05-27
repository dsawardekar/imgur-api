<?php

namespace Imgur;

use Encase\Container;

class RepoTest extends \PHPUnit_Framework_TestCase {

  public $container;
  public $cred;
  public $adapter;
  public $repo;
  public $clientId;

  function setUp() {
    $this->container = new Container();
    $this->container
      ->factory('imgurRequest', 'Imgur\Request')
      ->singleton('imgurCredentials', 'Imgur\Credentials')
      ->singleton('imgurAdapter', 'Imgur\Adapter')
      ->singleton('imgurRepo', 'Imgur\Repo');

    $this->cred    = $this->container->lookup('imgurCredentials');
    $this->adapter = $this->container->lookup('imgurAdapter');
    $this->repo    = $this->container->lookup('imgurRepo');

    $this->clientId = getenv('IMGUR_CLIENT_ID');
    $this->cred->setClientId($this->clientId);
    $this->repo->setModel('album');
  }

  function test_it_has_an_adapter() {
    $this->assertSame($this->adapter, $this->repo->imgurAdapter);
  }

  function test_it_stores_model_name() {
    $this->repo->setModel('image');
    $this->assertEquals('image', $this->repo->getModel());
  }

  function test_it_stores_parent_model() {
    $this->repo->setParent('album', 'foo');
    $this->assertEquals('album', $this->repo->getParentModel());
    $this->assertEquals('foo', $this->repo->getParentId());
  }

  function test_it_does_not_have_route_prefix_with_out_parent_model() {
    $this->assertEquals('', $this->repo->getRoutePrefix());
  }

  function test_it_has_route_prefix_if_parent_model_is_present() {
    $this->repo->setParent('album', 'foo');
    $this->assertEquals('album/foo', $this->repo->getRoutePrefix());
  }

  function test_it_can_build_route_without_parent_model() {
    $this->assertEquals('image/foo', $this->repo->routeFor('image', 'foo'));
  }

  function test_it_can_build_route_with_parent_model() {
    $this->repo->setParent('album', 'foo');
    $this->assertEquals('album/foo/image/bar', $this->repo->routeFor('image', 'bar'));
  }

  function test_it_can_find_details_of_model() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $album = $this->repo->find('V9E5t');
    $this->assertEquals('V9E5t', $album['id']);
  }

  function test_it_can_create_new_model() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array(
      'title' => 'test_album'
    );
    $album = $this->repo->create($params);
    $deleteHash = $album['deletehash'];
    $this->assertNotEquals('', $album['deletehash']);

    $album = $this->repo->find($album['id']);
    $this->assertEquals('test_album', $album['title']);

    $this->repo->delete($deleteHash);
  }

  function test_it_can_update_model() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $params = array('title' => 'test_album');
    $album = $this->repo->create($params);
    $deleteHash = $album['deletehash'];
    $new_params = array('title' => 'new_test_album');

    $success = $this->repo->update($deleteHash, $new_params);
    $album = $this->repo->find($album['id']);

    $this->assertEquals('new_test_album', $album['title']);
    $this->repo->delete($deleteHash);
  }

  function test_it_can_delete_model() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $album = $this->repo->create(array('title' => 'test_album'));
    $this->repo->delete($album['deletehash']);

    $this->setExpectedException('Imgur\Exception');
    $album = $this->repo->find($album['id']);
  }

}
