<?php

namespace webignition\Tests\WebResource\WebPage;

class GetScriptValuesTest extends ResponseBasedTest {
    
    /**
     * Test script src values can be retrieved in full from GB2312 content
     * Such content needs to be pre-converted to UTF-8 as the DOM crawler
     * fails internally when trying to do so
     */
    public function testGetScriptSrcValuesFromGB2312Content() {
        $scriptSrcValues = array();
        
        $this->webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptSrcValues) {                        
            $src = trim($domElement->getAttribute('src'));
            if ($src != '') {
                $scriptSrcValues[] = $src;
            }
        });
        
        $this->assertEquals(array(
            '/min/?f=images/js/j.js,include/dedeajax2.js',
            'http://www.yixieshi.com/plus/count.php?view=yes&aid=9935&mid=1',
            'http://cpro.baidustatic.com/cpro/ui/c.js',
            'http://pagead2.googlesyndication.com/pagead/show_ads.js',
            'http://widget.wumii.com/ext/relatedItemsWidget',
            'http://pagead2.googlesyndication.com/pagead/show_ads.js',
            'http://list.qq.com/zh_CN/htmledition/js/qf/page/qfcode.js',
            'http://www.yixieshi.com/plus/ad_js.php?aid=14',
            'http://cpro.baidustatic.com/cpro/ui/c.js',
            'http://s93.cnzz.com/stat.php?id=2002547&web_id=2002547'
        ), $scriptSrcValues);
    }    
    
    public function testGetScriptSrcValues() {        
        $scriptSrcValues = array();
        
        $this->webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptSrcValues) {                        
            $src = trim($domElement->getAttribute('src'));
            if ($src != '') {
                $scriptSrcValues[] = $src;
            }
        });      
        
        $this->assertEquals(array(
            '//example.com/foo.js',
            '/vendor/example/bar.js'
        ), $scriptSrcValues);
    }
    
    
    public function testGetScriptSrcValuesWhenNoDefinedCharacterEncoding() {        
        $scriptSrcValues = array();
        
        $this->webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptSrcValues) {                        
            $src = trim($domElement->getAttribute('src'));
            if ($src != '') {
                $scriptSrcValues[] = $src;
            }
        });
        
        $this->assertEquals(array(
            'css/jquery00.js',
            'css/jquery-1.4.2.min.js',
            'http://tw.js.webmaster.yahoo.com/470551/ystat.js'
        ), $scriptSrcValues);
    }    
    
    
    public function testGetScriptValues() {        
        $scriptValues = array();
        
        $this->webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptValues) {
            $nodeValue = trim($domElement->nodeValue);
            if ($nodeValue != '') {
                $scriptValues[] = $nodeValue;
            }
        });
        
        $this->assertEquals(array(
            'var firstFromHead = true;',
            'var secondFromHead = true;',
            'var firstFromBody = true;'
        ), $scriptValues);   
    }
    
}