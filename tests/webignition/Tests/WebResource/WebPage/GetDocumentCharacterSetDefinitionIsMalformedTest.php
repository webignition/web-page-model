<?php

namespace webignition\Tests\WebResource\WebPage;

class GetDocumentCharacterSetDefinitionIsMalformedTest extends ResponseBasedTest {
    
    public function testGetFromMetaCharsetElement() {
        $this->assertFalse($this->webPage->getDocumentCharacterSetDefinitionIsMalformed());
    }
    
    
    public function testGetFromHttpEquivContentTypeElement() {
        $this->assertFalse($this->webPage->getDocumentCharacterSetDefinitionIsMalformed());
    }
    
    
    public function testGetFromMalformedHttpEquivContentTypeElement() {        
        $this->assertTrue($this->webPage->getDocumentCharacterSetDefinitionIsMalformed());
    }
    
    public function testGetFromLowercaseHttpEquivContentTypeElement() {        
        $this->assertFalse($this->webPage->getDocumentCharacterSetDefinitionIsMalformed());       
    }   
    
    public function testGetWhenNoneIsPresentInDocument() {
        $this->assertNull($this->webPage->getDocumentCharacterSet());            
    }    
}