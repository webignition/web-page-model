<?php

namespace webignition\Tests\WebResource\WebPage;

abstract class ResponseBasedTest extends BaseTest {
    
    public function setUp() {
        parent::setUp();
        $this->webPage->setHttpResponse($this->getHttpResponseFixture($this->getName(), 'content.html.httpresponse'));
    }
}