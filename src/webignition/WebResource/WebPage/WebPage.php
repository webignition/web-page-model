<?php
namespace webignition\WebResource\WebPage;

use webignition\WebResource\WebResource;
use webignition\WebResource\WebPage\Parser as WebPageParser;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;

/**
 * 
 */
class WebPage extends WebResource
{
    /**
     *
     * @var WebPageParser
     */
    private $parser;
    
    
    public function __construct() {
        $validContentTypes = array(
            'text/html',
            'application/xhtml+xml',
            'application/xml'
        );
        
        foreach ($validContentTypes as $validContentTypeString) {
            $mediaTypeParser = new InternetMediaTypeParser();
            $this->addValidContentType($mediaTypeParser->parse($validContentTypeString));
        }
    }
    
    
    /**
     * Returns, in order of preference, first found to be valid of:
     *  - document character set
     *  - http response character set
     * 
     * @return string|null
     */
    public function getCharacterSet() {
        if ($this->hasDocumentCharacterSet() && $this->isValidCharacterSet($this->getDocumentCharacterSet())) {
            return $this->getDocumentCharacterSet();
        }
        
        if ($this->hasHttpResponseCharacterSet() && $this->isValidCharacterSet($this->getHttpResponseCharacterSet())) {
            return $this->getHttpResponseCharacterSet();
        }
        
        return null;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getDocumentCharacterSet() {
        return $this->getWebPageParser()->getCharacterSet();
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasDocumentCharacterSet() {
        return !is_null($this->getDocumentCharacterSet());
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasHttpResponseCharacterSet() {
        return !is_null($this->getHttpResponseCharacterSet());
    }
    
    
    /**
     * 
     * @param string $characterSet
     * @return boolean
     */
    private function isValidCharacterSet($characterSet) {
        $characterSetList = new \webignition\CharacterSetList\CharacterSetList();
        return $characterSetList->contains($characterSet);
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function getDocumentCharacterSetDefinitionIsMalformed() {
        return $this->getWebPageParser()->getIsContentTypeMalformed();
    }
    
    
    
    /**
     * 
     * @return string|null
     */
    public function getHttpResponseCharacterSet() {
        if (!$this->getContentType()->hasParameter('charset')) {
            return null;
        }
        
        return $this->getContentType()->getParameter('charset')->getValue(); 
    }
    
    
    /**
     *
     * @return WebPageParser
     */
    private function getWebPageParser() {
        if (is_null($this->parser)) {
            $this->parser = new WebPageParser();
            $this->parser->setWebPage($this);
        }
        
        return $this->parser;
    } 
    
    
    /**
     *
     * @param string $cssSelector
     * @param array $options
     * @return \QueryPath\DOMQuery 
     */
    public function find($cssSelector, $options = array()) {
        $content = $this->getContent();
        
        if ($this->getCharacterSet() == 'gb2312') {
            $content = iconv('GB2312', 'UTF-8', $content);
        }
        
        $options += array(
            'ignore_parser_warnings' => TRUE,
            'convert_to_encoding' => is_null($this->getCharacterSet()) ? 'utf-8' : $this->getCharacterSet(),
            'convert_from_encoding' => 'auto',
            'use_parser' => 'html'
        );
        
        $currentLibxmlUseInternalErrorsValue = libxml_use_internal_errors();        
        libxml_use_internal_errors(true);
        
        $result = new \QueryPath\DOMQuery($content, $cssSelector, $options);
        
        libxml_use_internal_errors($currentLibxmlUseInternalErrorsValue);
        
        return $result;
    }
    
}