<?php

use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebPage\DomElementLocator;

class LocateDomElementTest extends BaseTest {
    
    
    
    public function testLocateDomElement() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html'));        
        
        $locator = new DomElementLocator();        
        $locator->locate($webPage, $domElement);
        
        $webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$webPage) {            
            $locator->locate($webPage, $domElement);
        });        
    }
    
}