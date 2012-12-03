<?php

use webignition\WebResource\WebPage\WebPage;

class LocateDomElementTest extends BaseTest {
    
//    public function testGetScriptSrcValues() {
//        $webPage = new WebPage();
//        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html')); 
//        
//        $scriptSrcValues = array();
//        
//        $webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptSrcValues) {            
//            $src = trim($domElement->getAttribute('src'));
//            if ($src != '') {
//                $scriptSrcValues[] = $src;
//            }
//        });
//        
//        $this->assertEquals(array(
//            '//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js',
//            '/vendor/twitter-bootstrap/bootstrap/js/bootstrap.js'
//        ), $scriptSrcValues);
//    }
    
    
    public function testGetScriptValues() {
        $webPage = new WebPage();
        $webPage->setContent($this->getFixtureContent(__FUNCTION__, 'content.html')); 
        
        $scriptValues = array();
        
        $webPage->find('script')->each(function ($index, \DOMElement $domElement) use (&$scriptValues) {
            $nodeValue = trim($domElement->nodeValue);
            if ($nodeValue != '') {
                $scriptValues[] = $nodeValue;
            }
        });
        
        $this->assertEquals(array(
            "var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-33218043-1']);
  _gaq.push(['_setDomainName', 'simplytestable.com']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();",
            'var GoSquared = {};
  GoSquared.acct = "GSN-258432-Q";
  (function(w){
    function gs(){
      w._gstc_lt = +new Date;
      var d = document, g = d.createElement("script");
      g.type = "text/javascript";
      g.src = "//d1l6p2sc9645hc.cloudfront.net/tracker.js";
      var s = d.getElementsByTagName("script")[0];
      s.parentNode.insertBefore(g, s);
    }
    w.addEventListener ?
      w.addEventListener("load", gs, false) :
      w.attachEvent("onload", gs);
  })(window);',
            "var uvOptions = {};
  (function() {
    var uv = document.createElement('script'); uv.type = 'text/javascript'; uv.async = true;
    uv.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'widget.uservoice.com/qSLp2Zb35kqqNpLTQD7u0A.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(uv, s);
  })();"
        ), $scriptValues);        
    }
    
}