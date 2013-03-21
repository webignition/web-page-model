<?php

namespace webignition\WebResource\WebPage;

use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use webignition\InternetMediaType\InternetMediaType;

class Parser {
    
    
    /**
     *
     * @var WebPage
     */
    private $webPage;
    
    
    /**
     * Was the detected content type provided in a valid way?
     * 
     * We can detect some invalid means of specifying the content type and it's
     * useful to know whether what was found was valid.
     *
     * @var boolean
     */
    private $isContentTypeValid = true;
    
    
    /**
     *
     * @param WebPage $webPage
     * @return \webignition\WebResource\WebPage\Parser 
     */
    public function setWebPage(WebPage $webPage) {
        $this->webPage = $webPage;
        return $this;
    }
    
    
    /**
     *
     * @param string $cssSelector
     * @param array $options
     * @return \QueryPath\DOMQuery 
     */
    public function getDomQuery($cssSelector, $options = array()) {
        $options += array(
            'ignore_parser_warnings' => TRUE,
            'convert_to_encoding' => 'ISO-8859-1',
            'convert_from_encoding' => 'auto',
            'use_parser' => 'html'
        );     
        
        return new \QueryPath\DOMQuery($this->webPage->getContent(), $cssSelector, $options);
    }
    
    /**
     *
     * @return boolean
     */
    public function getIsContentTypeValid() {
        return $this->isContentTypeValid;
    }
    
    
    /**
     * Get the character encoding from the current web page
     * 
     * 
     * @return string|null
     */
    public function getCharacterEncoding() {        
        $metaContentTypeSelectors = array(
            'meta[http-equiv=Content-Type]' => true,
            'meta[http-equiv=content-type]' => true,
            'meta[name=Content-Type]' => false // invalid but happens
        );
        
        foreach ($metaContentTypeSelectors as $metaContentTypeSelector => $isValid) {
            $contentTypeString = null;

            @$this->getDomQuery($metaContentTypeSelector)->each(function ($index, \DOMElement $domElement) use (&$contentTypeString) {
                $contentTypeString = $domElement->getAttribute('content');
            });

            if (is_string($contentTypeString)) {
                $mediaTypeParser = new InternetMediaTypeParser();

                /* @var $mediaType InternetMediaType */
                $mediaType = $mediaTypeParser->parse($contentTypeString);

                if ($mediaType->hasParameter('charset')) {
                    $this->isContentTypeValid = $isValid;
                    return (string)$mediaType->getParameter('charset')->getValue();
                }
            }            
        }
        
        $charsetString = '';
        @$this->getDomQuery('meta[charset]')->each(function ($index, \DOMElement $domElement) use (&$charsetString) {            
            $charsetString = $domElement->getAttribute('charset');
        });      
        
        if (is_string($charsetString) && $charsetString !== '') {
            return $charsetString;
        }
        
        $contentTypeString = null;
        @$this->getDomQuery('meta[name=Content-Type]')->each(function ($index, \DOMElement $domElement) use (&$contentTypeString) {
            $contentTypeString = $domElement->getAttribute('content');
        });      
        
        return null;     
    }
    
    
    
}