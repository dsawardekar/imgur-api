<?php

namespace Imgur;

class CachedCredentials extends Credentials {

  static public $instance = null;
  static public function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new CachedCredentials();
      self::$instance->load();
    }

    return self::$instance;
  }

  function clear() {
    $this->clientId          = null;
    $this->clientSecret      = null;
    $this->refreshToken      = '';
    $this->accessToken       = '';
    $this->accessTokenExpiry = null;
  }

  function load() {
    $this->clientId          = getenv('IMGUR_CLIENT_ID');
    $this->clientSecret      = getenv('IMGUR_CLIENT_SECRET');
    $this->refreshToken      = getenv('IMGUR_REFRESH_TOKEN');
    $this->accessToken       = getenv('IMGUR_ACCESS_TOKEN');
    $this->accessTokenExpiry = strtotime('+60 minutes');
  }

}
