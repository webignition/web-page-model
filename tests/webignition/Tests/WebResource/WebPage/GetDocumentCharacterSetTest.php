<?php

namespace webignition\Tests\WebResource\WebPage;

class GetDocumentCharacterSetTest extends ResponseBasedTest {
    
    public function testGetFromMetaCharsetElement() {        
        $this->assertEquals('utf-8', $this->webPage->getDocumentCharacterSet());
    }
    
    
    public function testGetFromHttpEquivContentTypeElement() {
        $this->assertEquals('utf-8', $this->webPage->getDocumentCharacterSet());
    }
    
    
    public function testGetFromMalformedHttpEquivContentTypeElement() {
        $this->assertEquals('utf-8', $this->webPage->getDocumentCharacterSet());        
    }
    
    
    public function testGetFromLowercaseHttpEquivContentTypeElement() {
        $this->assertEquals('utf-8', $this->webPage->getDocumentCharacterSet());            
    }
    
    public function testGetWhenNoneIsPresentInDocument() {
        $this->assertNull($this->webPage->getDocumentCharacterSet());            
    }
}