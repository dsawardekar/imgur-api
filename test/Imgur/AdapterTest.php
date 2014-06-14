<?php

namespace Imgur;

use Encase\Container;

class AdapterTest extends \PHPUnit_Framework_TestCase {

  public $container;
  public $cred;
  public $adapter;

  function setUp() {
    $this->container = new Container();
    $this->container
      ->singleton('imgurCredentials', 'Imgur\Credentials')
      ->singleton('imgurAdapter', 'Imgur\Adapter');

    $this->cred    = $this->container->lookup('imgurCredentials');
    $this->adapter = $this->container->lookup('imgurAdapter');
  }

  function test_it_has_credentials() {
    $this->assertSame(
      $this->cred, $this->adapter->imgurCredentials
    );
  }

  function test_it_is_not_authorized_without_access_token() {
    $this->cred->setAccessToken('');
    $this->assertFalse($this->adapter->isAuthorized());
  }

  function test_it_is_authorized_if_access_token_is_present() {
    $this->cred->setAccessToken('foo');
    $this->assertTrue($this->adapter->isAuthorized());
  }

  function test_it_has_default_timeout() {
    $this->assertEquals(60, $this->adapter->getTimeout());
  }

  function test_it_sets_default_timeout_on_session() {
    $session = $this->adapter->getSession();
    $this->assertEquals(60, $session->options['timeout']);
  }

  function test_it_can_build_authorize_url_without_response_type() {
    $this->cred->setClientId('foo');
    $url = $this->adapter->authorizeUrl();

    $this->assertContains('client_id=foo', $url);
    $this->assertContains('response_type=pin', $url);
  }

  function test_it_can_build_authorize_url_with_response_type() {
    $this->cred->setClientId('foo');
    $url = $this->adapter->authorizeUrl('code');

    $this->assertContains('client_id=foo', $url);
    $this->assertContains('response_type=code', $url);
  }

  function test_it_can_update_credentials_from_json_response() {
    $json = array(
      'access_token' => 'foo',
      'expires_in' => 100,
      'refresh_token' => 'bar'
    );

    $this->adapter->updateCredentials($json);

    $this->assertEquals('foo', $this->cred->getAccessToken());
    $this->assertEquals('bar', $this->cred->getRefreshToken());
    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  function test_it_throws_exception_if_required_fields_are_missing() {
    $json = array();
    $this->setExpectedException('Imgur\Exception');
    $this->adapter->updateCredentials($json);
  }

  function test_it_can_parse_valid_json_response_body() {
    $body = '{"status":true, "data": {}}';
    $json = $this->adapter->parseBody($body);

    $this->assertTrue($json['status']);
  }

  function test_it_throws_exception_if_invalid_json_returned_from_server() {
    $body = '{foo}';
    $this->setExpectedException('Imgur\Exception');
    $json = $this->adapter->parseBody($body);
  }

  function test_it_knows_if_client_id_header_was_not_set_for_verify_pin() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;

    $this->setExpectedException('Imgur\Exception');
    $actual = $this->adapter->verifyPin('foo');
  }

  function test_it_knows_if_client_credentials_are_invalid_for_verify_pin() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;
    $this->setExpectedException('Imgur\Exception');
    $actual = $this->adapter->verifyPin('foo');
  }

  function test_it_knows_if_client_secret_is_missing_for_verify_pin() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;
    $this->cred->setClientId(getenv('IMGUR_CLIENT_ID'));
    $this->setExpectedException('Imgur\Exception');

    $actual = $this->adapter->verifyPin('foo');
  }

  function test_it_knows_if_pin_is_invalid() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;
    $this->cred->setClientId(getenv('IMGUR_CLIENT_ID'));
    $this->cred->setClientSecret(getenv('IMGUR_CLIENT_SECRET'));
    $this->setExpectedException('Imgur\Exception');
    $actual = $this->adapter->verifyPin('foo');
  }

  /* this test must be run with ENV variable VALID_PIN
   * containing the pin from visiting the authorize page */
  function test_it_can_verify_valid_pin() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;
    $pin = getenv('IMGUR_PIN');
    if (!$pin) return;

    $this->cred = CachedCredentials::getInstance();
    $this->adapter->imgurCredentials = $this->cred;

    $actual = $this->adapter->verifyPin($pin);

    $this->assertTrue($actual);
    $this->assertTrue($this->adapter->isAuthorized());

    echo "\nAccess Token: " . $this->cred->getAccessToken() . "\n";
    echo "\nRefresh Token: " . $this->cred->getRefreshToken() . "\n";

    $this->assertNotEquals('', $this->cred->getAccessToken());
    $this->assertNotEquals('', $this->cred->getRefreshToken());
    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  function test_it_can_refresh_access_token() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;
    $refreshToken = getenv('IMGUR_REFRESH_TOKEN');
    if (!$refreshToken) return;

    $this->cred = CachedCredentials::getInstance();
    $this->adapter->imgurCredentials = $this->cred;

    $actual = $this->adapter->refreshAccessToken();

    $this->assertTrue($actual);

    $this->assertNotEquals('', $this->cred->getAccessToken());
    $this->assertNotEquals('foo', $this->cred->getAccessToken());
    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  function test_it_can_invoke_model_request() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;
    $accessToken  = getenv('IMGUR_ACCESS_TOKEN');
    $refreshToken = getenv('IMGUR_REFRESH_TOKEN');
    if (!$accessToken || !$refreshToken) return;

    $this->cred = CachedCredentials::getInstance();
    $this->adapter->imgurCredentials = $this->cred;

    $request = new Request();
    $request->setRoute('album/V9E5t');

    $album = $this->adapter->invoke($request);
    $this->assertEquals('V9E5t', $album['id']);
  }

  function test_it_refreshes_access_token_before_model_request_if_needed() {
    if (getenv('IMGUR_SKIP_REMOTE')) return;
    $accessToken  = getenv('IMGUR_ACCESS_TOKEN');
    $refreshToken = getenv('IMGUR_REFRESH_TOKEN');
    if (!$accessToken || !$refreshToken) return;

    $this->cred = CachedCredentials::getInstance();
    $this->adapter->imgurCredentials = $this->cred;

    $this->cred->setAccessTokenExpiry(0);
    $this->assertTrue($this->cred->hasAccessTokenExpired());

    $request = new Request();
    $request->setRoute('album/V9E5t');

    $album = $this->adapter->invoke($request);
    $this->assertEquals('V9E5t', $album['id']);
    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

}
