<?php
namespace webignition\WebResource\WebPage;

/**
 * 
 */
class DomElementLocator
{
    /**
     *
     * @var WebPage
     */
    private $webPage;
    
    
    public function locate(WebPage $webPage, \DOMElement $domElement) {        
        $this->webPage = $webPage;
        
        $xml = $domElement->ownerDocument->saveXML($domElement); 
        
        //      <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"/>
        
        //$xml = '<script type="text/javascript" src="//a';
        
        
        var_dump($xml, strpos($this->getContent(), $xml), $this->getContent());
        
    
        exit();
    }
    
}