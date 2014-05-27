<?php

namespace Imgur;

class RequestTest extends \PHPUnit_Framework_TestCase {

  public $request;

  function setUp() {
    $this->request = new Request();
  }

  function test_it_stores_route_to_model() {
    $this->request->setRoute('image');
    $this->assertEquals('image', $this->request->getRoute());
  }

  function test_it_stores_method_type() {
    $this->request->setMethod('POST');
    $this->assertEquals('POST', $this->request->getMethod());
  }

  function test_it_stores_headers() {
    $headers = array('Accept' => 'application/json');
    $this->request->setHeaders($headers);
    $this->assertEquals($headers, $this->request->getHeaders());
  }

  function test_it_stores_request_params() {
    $params = array('foo' => 'bar');
    $this->request->setParams($params);
    $this->assertEquals($params, $this->request->getParams());
  }

}
