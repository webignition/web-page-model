<?php
namespace webignition\WebResource\WebPage;

use webignition\WebResource\WebResource;
use webignition\WebResource\WebPage\Parser as WebPageParser;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
//use webignition\InternetMediaType\InternetMediaType;

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
     * Character encoding as specified in the document body. This supercedes
     * any character encoding specified in the content type header.
     * 
     * @var string
     */
    private $documentCharacterEncoding;
    
    
    /**
     * The character encoding can be specified in unabiguous but invalid ways. The parser
     * can understand some invalidity.
     * 
     * It is sometimes necessary to know if the detected character coding was
     * invalidly specified.
     * 
     * @var boolean
     */
    private $isDocumentCharacterEncodingValid = true;
    
    
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
     *
     * @return boolean
     */
    public function getIsDocumentCharacterEncodingValid() {
        return $this->isDocumentCharacterEncodingValid;
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
            $this->isDocumentCharacterEncodingValid = $this->getWebPageParser()->getIsContentTypeValid();
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
    
    
    /**
     *
     * @param string $cssSelector
     * @param array $options
     * @return \QueryPath\DOMQuery 
     */
    public function find($cssSelector, $options = array()) {
        $content = $this->getContent();
        
        if ($this->getCharacterEncoding() == 'gb2312') {
            $content = iconv('GB2312', 'UTF-8', $this->getContent());
        }
        
        $options += array(
            'ignore_parser_warnings' => TRUE,
            'convert_to_encoding' => $this->getCharacterEncoding(),
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