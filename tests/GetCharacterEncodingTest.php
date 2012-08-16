<?php

use webignition\WebResource\WebPage\WebPage;

class GetCharacterEncodingTest extends BaseTest {

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
    
}