<?php

namespace webignition\Tests\WebResource\WebPage;

class GetHttpResponseChararacterSet extends ResponseBasedTest {
    
    public function testGetWhenPresent() {        
        $this->assertEquals('utf-8', $this->webPage->getHttpResponseCharacterSet());
    }
    
    public function testGetWhenNotPresent() {
        $this->assertNull($this->webPage->getHttpResponseCharacterSet());            
    }
}