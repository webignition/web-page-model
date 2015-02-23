<?php

namespace webignition\Tests\WebResource\WebPage;

use webignition\WebResource\WebPage\WebPage;
use GuzzleHttp\Message\MessageFactory as HttpMessageFactory;
use GuzzleHttp\Message\ResponseInterface as HttpResponse;

abstract class BaseTest extends \PHPUnit_Framework_TestCase {
    
const FIXTURES_DATA_RELATIVE_PATH = '/../../../../Fixtures';      

    /**
     *
     * @var \webignition\WebResource\WebPage\WebPage 
     */
    protected $webPage;
    
    
    public function setUp() {
        $this->webPage = new WebPage();
    }

    
    /**
     * Get the path to store fixtures for a given test
     * 
     * @param string $testName Test method name
     * @return string
     */
    protected function getFixturesDataPath($testName) {        
        return __DIR__ . self::FIXTURES_DATA_RELATIVE_PATH . '/' . str_replace('\\', DIRECTORY_SEPARATOR, get_class($this)) . '/' . $testName;
    }  
    
    /**
     * Get the content for a given test fixture
     * 
     * @param string $testName Test method name
     * @param string $fixtureName Name of fixture, is fixture relative filename
     * @return string 
     */
    protected function getFixtureContent($testName, $fixtureName) {
        return file_get_contents($this->getFixtureContentPath($testName, $fixtureName));
    } 
    
    
    /**
     * 
     * @param string $testName
     * @param string $fixtureName
     * @return HttpResponse
     */
    protected function getHttpResponseFixture($testName, $fixtureName) {
        return $this->getHttpResponseFromMessage($this->getFixtureContent($testName, $fixtureName));
    }
    
    
    /**
     * Get the path for a given test fixture
     * 
     * @param string $testName Test method name
     * @param string $fixtureName Name of fixture, is fixture relative filename
     * @return string 
     */
    private function getFixtureContentPath($testName, $fixtureName) {
        return $this->getFixturesDataPath($testName) . '/' . $fixtureName;
    }


    /**
     * @param $message
     * @return HttpResponse
     */
    protected function getHttpResponseFromMessage($message) {
        $factory = new HttpMessageFactory();
        return $factory->fromMessage($message);
    }
    
    
   
}