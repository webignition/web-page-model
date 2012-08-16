<?php
namespace webignition\WebResource\WebPage;

use webignition\WebResource\WebResource;

//use SimplyTestable\WorkerBundle\Services\WebPage\Parser as WebPageParser;
//
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use webignition\InternetMediaType\InternetMediaType;

/**
 * 
 */
class WebPage extends WebResource
{
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
    
}