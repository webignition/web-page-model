<?php

use webignition\WebResource\WebPage\WebPage;

class GetCharacterEncodingTest extends BaseTest {
    
    public function testGetCharacterEncodingFromDocumentMetaContentType() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));        
        
        $this->assertEquals('iso-8859-1', $webPage->getCharacterEncoding());
        $this->assertFalse($webPage->getIsDocumentCharacterEncodingValid());
    }    
    
    public function testGetCharacterEncodingFromDocumentMetaEquivElement() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        
        $this->assertEquals('utf-8', $webPage->getCharacterEncoding());
        $this->assertTrue($webPage->getIsDocumentCharacterEncodingValid());
    }
    
    public function testGetCharacterEncodingFromLowerCaseDocumentMetaEquivElement() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        
        $this->assertEquals('iso-8859-1', $webPage->getCharacterEncoding());
        $this->assertTrue($webPage->getIsDocumentCharacterEncodingValid());
    }    
    
    public function testGetCharcterEncodingWhenThereIsNone() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        
        $this->assertNull($webPage->getCharacterEncoding());
        $this->assertTrue($webPage->getIsDocumentCharacterEncodingValid());
    }     
    
    
    public function testGetCharacterEncodingFromDocumentMetaCharsetElement() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        
        $this->assertEquals('utf-8', $webPage->getCharacterEncoding());
        $this->assertTrue($webPage->getIsDocumentCharacterEncodingValid());
    }
    
    
    public function testGetCharacterEncodingFromContentType() {
        $webPage = new WebPage();
        $webPage->setContentType('text/html; charset=utf-8');
        
        $this->assertEquals('utf-8', $webPage->getCharacterEncoding());
    }
    
    public function testGetCharacterEncodingFromContentTypeHeaderWhenContentHasInvalidMetaEquiv() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        $webPage->setContentType('text/html; charset=utf-8');
        
        $this->assertEquals('utf-8', $webPage->getCharacterEncoding());
        $this->assertTrue($webPage->getIsDocumentCharacterEncodingValid());        
    }
}