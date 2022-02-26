<?php
/**
 * Extendify for Craft CMS plugin for Craft CMS 3.x
 *
 * Extendify plugin used to extend Craft CMS Functionality 
 *
 * @link      www.centrillion.com.au
 * @copyright Copyright (c) 2018 Albert Nassif
 */

namespace centrillion\extendifyforcraftcms\web\twig;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms;

use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;

use Craft;
use craft\elements\GlobalSet;
use craft\elements\Asset;
use craft\web\View;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use yii\helpers\Html;
use yii\helpers\Markdown;

/**
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators,
 * global variables, and functions. You can even extend the parser itself with
 * node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 *
 * @author    Albert Nassif
 * @package   ExtendifyForCraftCms
 * @since     0.0.1
 */
class Extension extends Twig_Extension
{
    // Public Methods
    // =========================================================================
  
    public function getName()
    {
        return 'ExtendifyForCraftCms';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('someFilter', [$this, 'someInternalFunction']),
            new \Twig_SimpleFilter('shuffle', [$this, 'twigShuffleFilter']),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
    * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('jsonToArray', [$this, 'jsonToArray']),
            new \Twig_SimpleFunction('checkElementExists', [$this, 'checkElementExists']),
            new \Twig_SimpleFunction('getArrayColumn', [$this, 'getArrayColumn']),
            new \Twig_SimpleFunction('checkPluginExists', [$this, 'checkPluginExists']),
            new \Twig_SimpleFunction('getSiteTemplatePath', [$this, 'getSiteTemplatePath']),
            new \Twig_SimpleFunction('jsonDecode', [$this, 'jsonDecode']),
            new \Twig_SimpleFunction('base64', [$this, 'base64']),
            new \Twig_SimpleFunction('checkFileExists', [$this, 'checkFileExists']),
            new \Twig_SimpleFunction('getSiteLogo', [$this, 'getSiteLogo']),
            new \Twig_SimpleFunction('compileEmailBody', [$this, 'compileEmailBody']),
            new \Twig_SimpleFunction('encodeAddress', [$this, 'encodeAddress']),
            // new functions
            new \Twig_SimpleFunction('getSelectedOptions', [$this, 'getSelectedOptions']),
            new \Twig_SimpleFunction('assetExists', [$this, 'assetExists']),
            new \Twig_SimpleFunction('cssBgImageProperty', [$this, 'cssBgImageProperty']),
            new \Twig_SimpleFunction('cssClasses', [$this, 'cssClasses']),
        ];
    }

    // TWIG Function Methods
    // =========================================================================
  
    public function jsonDecode($json, $assoc = false) {
        return json_decode($json, $assoc);
    }
    public function base64($type = 'encode', $string) {
      if ($type == 'encode') {
        return base64_encode($string);
      } else {
        return base64_decode($string);
      }
    }
    public function encodeAddress($street,$suburb,$state,$postcode,$country) {
      if (!empty($street) && !empty($suburb) && !empty($state) && !empty($postcode) && !empty($country)) {
        $address = $street . ' ' . $suburb . ' ' . $state . ' ' . $postcode . ' ' . $country;
        return urlencode($address);
      } 
      
      return null;
    }
    public function checkFileExists($file = null, $strip = false) { 
        $file = str_replace(UrlHelper::siteUrl(),'',$file);
      
        if (!empty($file)) {
          if (file_exists($file)) {
            return true;
          }
        }
        return false;
    }
    public function getSiteLogo() {
      // Get all of the globals sets
      $website = Craft::$app->getGlobals()->getSetByHandle('website');
      
      if ($this->checkFileExists($website->logos->primaryLogo->one())) {
        return $website->logos->primaryLogo->one()->getImg();
      } else if ($this->checkFileExists($website->logos->inverseLogo->one())) {
        return $website->logos->inverseLogo->one()->getImg();
      } else if ($this->checkFileExists($website->logos->mobileLogo->one())) {
        return $website->logos->mobileLogo->one()->getImg();
      } else {
        return $website->logos->logoText;
      }
    }
    public function compileEmailBody(Object $submission) {
    $fields = [];

    if (is_array($submission->message)) {
      $body = $submission->message['body'] ?? '';
      $fields = array_merge($fields, $submission->message);
      unset($fields['body']);
    } else {
      $body = (string) $submission->message;
    }
      
    if (is_array($submission->hidden)) {
      $fields = array_merge($fields, $submission->hidden);
    }  

    $text = '<dl>';
      
    $text .= '<dt>' . '<strong>Name</strong>' . '</dt>';
    $text .= '<dd>' . $submission->name . '</dd>'; 
      
    $text .= '<dt>' . '<strong>Email</strong>' . '</dt>';
    $text .= '<dd>' . $submission->email . '</dd>';   

    // this is where we can replace markeup and make it a table as 
    // it gets the key first and then appends the value
    foreach ($fields as $key => $value) {
      $text .= '<dt>' . '<strong>' . ucwords(str_replace("_"," ",$key)) . '</strong>' . '</dt>';
      $text .= '<dd>';
      if (is_array($value)) {
        $text .= implode(', ', $value);
      } else {
        $text .= $value;
      }
      $text .= '</dd>';
    }

    if ($body !== '') {
      $body = preg_replace('/\R/', "\n\n", $body);
      $text .= '<dt>' . '<strong>Message</strong>' . '</dt>';
      $text .= '<dd>' . $body . '</dd>';
    }
      
    $text .= '</dl>';  
      
    //$text .= '<pre>' . json_encode($submission) . '</pre>';  
    
    //$html = Html::encode($text);
    //$html = Markdown::process($html);

    return $text;
  }
  
    public function jsonToArray($file = null, $assoc = false) {
      $templateDir = Craft::$app->view->getTemplatesPath();
      
      if (!empty($file) && file_exists($templateDir . '/' . $file)) {
        $jsonRaw = file_get_contents($templateDir . '/' . $file);
        $jsonObject = json_decode($jsonRaw,$assoc);
        
        return $jsonObject;  
      }
      return null;
    }
    public function checkElementExists($key = null, $array = null) {
      if(!empty($key) && !empty($array)) {
        if (array_key_exists($key, $array)) {
          return true;
        }
      }    
      return false;
    }
    public function checkPluginExists($handle) {
      if (!empty($handle)) {
        return Craft::$app->plugins->isPluginInstalled($handle);
      }
      return false;
    }
    public function getSiteTemplatePath() {
      return Craft::$app->getSites()->currentSite->handle;
    }
    public function twigShuffleFilter($array) {
      if ($array instanceof Traversable) {
          $array = iterator_to_array($array, false);
      }
      shuffle($array);
      return $array;
    }
  
    public function getSelectedOptions($options) {
      if (!empty($options)) {
        $result = null;
        foreach ($options as $key => $option) {
          if ($option->selected && !empty($option->value)) {
            $result .= $option->value;
         
            if ($key !== array_key_last($options))
              $result .= ' ';
          }
        }
        return $result;
      }
      return null;
    }
    public function assetExists($asset) {
      
      if (!empty($asset) and $asset->exists()) {
        return true;
      } 
      return false; 
    }
    public function cssBgImageProperty($image, $focal = 'center center') {
      
      if (!empty($image) and $image->exists()) {
        return 'background-image:url("' . $image->one()->getUrl() . '");background-position:' . $focal . ';';
      } 
      return null; 
    }
    public function cssClasses($classes) {
      if (!empty($classes) && is_array($classes)) {
        $result = null;
        foreach($classes as $key => $class) {
          if (!empty($class)) {
            $result .= $class;
            
            // need to workout how to detect final item and noty add the space
            if ($key !== array_key_last($classes))
              $result .= ' ';
          }
        }
        return $result;
      }
      return null;
    }
}