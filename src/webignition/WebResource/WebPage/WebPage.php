<?php
namespace webignition\WebResource\WebPage;

use webignition\WebResource\WebResource;
use webignition\WebResource\WebPage\Parser as WebPageParser;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use webignition\InternetMediaType\InternetMediaType;

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
    
    /**
     *  Map of valid web page internet media type types and subtypes
     * 
     * @var array
     */
    private $validContentTypes = array(
        'text' => array(
            'html'
        ),
        'application' => array(
            'xhtml+xml',
            'xml'
        )
    );
    
    /**
     * Character encoding as specified in the document body. This supercedes
     * any character encoding specified in the content type header.
     * 
     * @var string
     */
    private $documentCharacterEncoding;
    
    
    /**
     *
     * @param string $contentTypeString
     * @return WebPage
     */
    public function setContentType($contentTypeString) {
        $contentType = $this->getContentTypeInternetMediaType($contentTypeString);
        if (!$this->isValidContentType($contentType)) {
            throw new Exception('Invalid content type: "'.$contentTypeString.'"', 1);
        }
       
        return parent::setContentType($contentType);
    }
    
    
    /**
     *
     * @param InternetMediaType $contentType
     * @return boolean 
     */
    private function isValidContentType(InternetMediaType $contentType) {
        foreach ($this->validContentTypes as $type => $subtypes) {
            if ($contentType->getType() == $type) {
                foreach ($subtypes as $subtype) {
                    if ($contentType->getSubtype() == $subtype) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
   
    /**
     *
     * @param string $contentTypeString
     * @return InternetMediaType 
     */
    private function getContentTypeInternetMediaType($contentTypeString) {
        $internetMediaTypeParser = new InternetMediaTypeParser();
        return $internetMediaTypeParser->parse($contentTypeString);       
    }
    
    
    /**
     *
     * @return string|null
     */
    public function getCharacterEncoding() {        
        if (is_null($this->documentCharacterEncoding)) {
            $this->documentCharacterEncoding = $this->getWebPageParser()->getCharacterEncoding();
        }
        
        if (!is_null($this->documentCharacterEncoding)) {
            return strtolower($this->documentCharacterEncoding);
        } 
        
        if ($this->getContentType()->hasParameter('charset')) {
            return $this->getContentType()->getParameter('charset')->getValue();
        }
        
        return null;
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
    
}