<?php

namespace webignition\Tests\WebResource\WebPage;

class GetCharacterSetTest extends ResponseBasedTest {
    
    public function testGetFromDocumentWhenIsPresentAndValid() {
        $this->assertEquals('utf-8', $this->webPage->getCharacterSet());
    }
    
    
    public function testGetFromHttpResponseWhenNotPresentInDocument() {
        $this->assertEquals('utf-8', $this->webPage->getCharacterSet());
    }
    
    
    public function testGetFromHttpResponseWhenInDocumentIsInvalid() {
        $this->assertEquals('utf-8', $this->webPage->getCharacterSet());
    }
    
    
    public function testGetWhenNonePresent() {        
        $this->assertNull($this->webPage->getCharacterSet());
    }
}