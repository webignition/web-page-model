<?php

use webignition\WebResource\WebPage\WebPage;

class WebPageTest extends PHPUnit_Framework_TestCase {

    public function testSetValidContentType() {
        $contentTypeStrings = array(
            'text/html',
            'application/xhtml+xml',
            'application/xml'
        );
        
        $webPage = new WebPage();
        
        foreach ($contentTypeStrings as $contentTypeString) {
            $webPage->setContentType($contentTypeString);
            $this->assertEquals($contentTypeString, (string)$webPage->getContentType());
        }
    }   
    
    public function testSetInvalidContentType() {
        $contentTypeStrings = array(
            'image/png',
            'text/css',
            'text/javascript'
        );
        
        $webPage = new WebPage();
        
        foreach ($contentTypeStrings as $contentTypeString) {
            try {
                $webPage->setContentType($contentTypeString);
                $this->fail('Invalid content type exception not thrown for "'.$contentTypeString.'"');
            } catch (\webignition\WebResource\WebPage\Exception $exception) {
                $this->assertEquals(1, $exception->getCode());
            }
        }
    } 
    
}