<?php

namespace Imgur;

use Encase\Container;

class PackagerTest extends \PHPUnit_Framework_TestCase {

  public $container;
  public $packager;

  function setUp() {
    $this->container = new Container();
    $this->container
      ->packager('imgurPackager', 'Imgur\Packager');
  }

  function test_it_registers_stub_credentials() {
    $cred = $this->container->lookup('imgurCredentials');
    $this->assertInstanceOf('Imgur\Credentials', $cred);
  }

  function test_it_registers_imgur_adapter() {
    $adapter = $this->container->lookup('imgurAdapter');
    $this->assertInstanceOf('Imgur\Adapter', $adapter);
  }

  function test_it_registers_imgur_image_repo() {
    $imageRepo = $this->container->lookup('imgurImageRepo');
    $this->assertInstanceOf('Imgur\ImageRepo', $imageRepo);
  }

  function test_it_registers_imgur_album_repo() {
    $albumRepo = $this->container->lookup('imgurAlbumRepo');
    $this->assertInstanceOf('Imgur\AlbumRepo', $albumRepo);
  }

}
