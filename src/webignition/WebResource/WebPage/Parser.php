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
     * Get the character encoding from the current web page
     * 
     * 
     * @return string|null
     */
    public function getCharacterEncoding() {        
        $metaContentTypeSelectors = array(
            'meta[http-equiv=Content-Type]',
            'meta[name=Content-Type]' // invalid but happens
        );
        
        foreach ($metaContentTypeSelectors as $metaContentTypeSelector) {
            $contentTypeString = null;

            @$this->getDomQuery($metaContentTypeSelector)->each(function ($index, \DOMElement $domElement) use (&$contentTypeString) {
                $contentTypeString = $domElement->getAttribute('content');
            });

            if (is_string($contentTypeString)) {
                $mediaTypeParser = new InternetMediaTypeParser();

                /* @var $mediaType InternetMediaType */
                $mediaType = $mediaTypeParser->parse($contentTypeString);

                if ($mediaType->hasParameter('charset')) {
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