<?php

use webignition\WebResource\WebPage\WebPage;

class GetCharacterEncodingTest extends BaseTest {

    public function testGetCharacterEncodingFromDocumentMetaContentType() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        
        $this->assertEquals('iso-8859-1', $webPage->getCharacterEncoding());
    }    
    
    public function testGetCharacterEncodingFromDocumentMetaEquivElement() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        
        $this->assertEquals('utf-8', $webPage->getCharacterEncoding());
    }
    
    
    public function testGetCharacterEncodingFromDocumentMetaCharsetElement() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));
        
        $this->assertEquals('utf-8', $webPage->getCharacterEncoding());
    }
    
    
    public function testGetCharacterEncodingFromContentType() {
        $webPage = new WebPage();
        $webPage->setContentType('text/html; charset=utf-8');
        
        $this->assertEquals('utf-8', $webPage->getCharacterEncoding());
    }
    
}