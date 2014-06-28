<?php

namespace Imgur;

use \Moment\Moment;

class CredentialsTest extends \PHPUnit_Framework_TestCase {

  public $cred;

  function setUp() {
    $this->cred = new Credentials();
  }

  function test_it_stores_client_id() {
    $this->cred->setClientId('foo');
    $this->assertEquals('foo', $this->cred->getClientId());
  }

  function test_it_stores_client_secret() {
    $this->cred->setClientSecret('foo');
    $this->assertEquals('foo', $this->cred->getClientSecret());
  }

  function test_it_stores_access_token() {
    $this->cred->setAccessToken('foo');
    $this->assertEquals('foo', $this->cred->getAccessToken());
  }

  function test_it_stores_access_token_expiry() {
    $this->cred->setAccessTokenExpiry(60);
    $actual = $this->cred->getAccessTokenExpiry();
    $now    = strtotime('now');
    $diff   = $actual - $now;

    $this->assertEquals(60, $diff);
  }

  function test_it_store_access_token_duration() {
    $this->cred->setAccessTokenDuration(100);
    $this->assertEquals(100, $this->cred->getAccessTokenDuration());
  }

  function test_it_knows_if_raw_access_token_has_not_expired() {
    $timestamp = strtotime('+ 1minute');
    $this->cred->accessTokenExpiry = $timestamp;
    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  function test_it_knows_if_raw_access_token_has_expired() {
    $timestamp = strtotime('- 1minute');
    $this->cred->accessTokenExpiry = $timestamp;

    $this->assertTrue($this->cred->hasAccessTokenExpired());
  }

  function test_it_knows_if_access_token_has_not_expired() {
    $this->cred->setAccessTokenExpiry(60);
    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  function test_it_knows_if_access_token_has_expired() {
    $this->cred->setAccessTokenExpiry(-60);
    $this->assertTrue($this->cred->hasAccessTokenExpired());
  }

  function test_it_knows_if_access_has_expired_after_time_elapsed() {
    $this->cred->setAccessTokenExpiry(60);
    $this->cred->now = strtotime('+1 week');

    $this->assertTrue($this->cred->hasAccessTokenExpired());
  }

  function test_it_knows_if_access_has_not_expired_after_small_time_interval() {
    $this->cred->setAccessTokenExpiry(120);
    $this->cred->now = strtotime('+49 seconds');

    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  /*
   * access token 2 minutes in future
   * time travel to 61 seconds in future
   * thats 1 second more than the expiry buffer
   * Hence, accessToken has expired.
   */
  function test_it_knows_if_access_has_expired_if_valid_but_within_expiry_buffer() {
    $this->cred->setAccessTokenExpiry(120);
    $this->cred->now = strtotime('+61 seconds');

    $this->assertTrue($this->cred->hasAccessTokenExpired());
  }

  /* tiny variant on above, last call to get inside the expiry */
  function test_it_knows_if_access_has_expired_if_valid_but_outside_expiry_buffer() {
    $this->cred->setAccessTokenExpiry(120);
    $this->cred->now = strtotime('+59 seconds');

    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  function test_it_stores_refresh_token() {
    $this->cred->setRefreshToken('foo');
    $this->assertEquals('foo', $this->cred->getRefreshToken());
  }

  function test_it_knows_if_stored_expiry_has_not_expired() {
    $this->cred->accessTokenExpiry = strtotime('+60 seconds');
    $this->assertFalse($this->cred->hasAccessTokenExpired());
  }

  function test_it_knows_if_stored_expiry_has_expired() {
    $this->cred->accessTokenExpiry = strtotime('now');
    $this->cred->now = strtotime('+60 minutes');
    $this->assertTrue($this->cred->hasAccessTokenExpired());
  }

}
