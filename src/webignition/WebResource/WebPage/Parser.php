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
     * Was the detected content type provided in a correct way?
     * 
     * We can detect some invalid means of specifying the content type and it's
     * useful to know whether what was found was formed correctly.
     *
     * @var boolean
     */
    private $isContentTypeMalformed = null;
    
    
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
        
        return new \QueryPath\DOMQuery($this->webPage->getHttpResponse()->getBody(true), $cssSelector, $options);
    }
    
    /**
     *
     * @return boolean
     */
    public function getIsContentTypeMalformed() {
        if (is_null($this->isContentTypeMalformed)) {
            $this->getCharacterSet();
        }
        
        return $this->isContentTypeMalformed;
    }
    
    
    /**
     * Get the character set from the current web page
     * 
     * 
     * @return string|null
     */
    public function getCharacterSet() {        
        $this->isContentTypeMalformed = false;
        
        $metaContentTypeSelectors = array(
            'meta[http-equiv=Content-Type]' => false,
            'meta[http-equiv=content-type]' => false,
            'meta[name=Content-Type]' => true // invalid but happens
        );
        
        foreach ($metaContentTypeSelectors as $metaContentTypeSelector => $isMalformed) {
            $contentTypeString = null;

            @$this->getDomQuery($metaContentTypeSelector)->each(function ($index, \DOMElement $domElement) use (&$contentTypeString) {
                $contentTypeString = $domElement->getAttribute('content');
            });

            if (is_string($contentTypeString)) {                
                $mediaTypeParser = new InternetMediaTypeParser();
                $mediaTypeParser->setIgnoreInvalidAttributes(true);
                $mediaTypeParser->setAttemptToRecoverFromInvalidInternalCharacter(true);                

                /* @var $mediaType InternetMediaType */
                try {
                    $mediaType = $mediaTypeParser->parse($contentTypeString);

                    if ($mediaType->hasParameter('charset')) {
                        $this->isContentTypeMalformed = $isMalformed;
                        return (string)$mediaType->getParameter('charset')->getValue();
                    }                    
                } catch (\webignition\InternetMediaType\Parser\TypeParserException $typeParserException) {
                    // Occurs when we can't parse the in-markup content type
                    // Ignore such exceptions to treat this as having no in-markup content type
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