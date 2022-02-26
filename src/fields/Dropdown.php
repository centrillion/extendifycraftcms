<?php

// https://craftcms.stackexchange.com/questions/9027/populate-select-input-via-static-values-on-plugin-settings-page

namespace centrillion\extendifyforcraftcms\fields;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms;

use yii\db\Schema;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use craft\helpers\Json;

class Dropdown extends Field
{
     
    public $dropdownOptions = '';
    public $columnType = 'text';
	
    public static function displayName(): string
    {
        return Craft::t('extendify-for-craft-cms', 'Extendify Dropdown');
    }

    public function getSettingsHtml()
    {
        $options = array();
	    
				$view = Craft::$app->getView();
				$templateMode = $view->getTemplateMode();
				$view->setTemplateMode($view::TEMPLATE_MODE_SITE);

				$siteHandle = Craft::$app->getSites()->currentSite->handle;
				$fileName = "template.json";
				$arrayList = $this-> _jsonToArray($siteHandle . '/' . $fileName, true);

				if ($this->_checkElementExists('fields', $arrayList['dynamic'])) :
					foreach ($arrayList['dynamic']['fields'] as $key => $element) :
						if (!empty($element['value'])) :
							$options[] = array (
								'value' => $element['value'],
								'label' => $element['label']						
							);
						endif;
					endforeach;
				else:
					$options[] = 'None';
				endif;
		    
			  $view->setTemplateMode($templateMode);
			
				return Craft::$app->getView()->renderTemplate('extendify-for-craft-cms/_components/fields/Dropdown_settings',
					[
							'field' => $this,
							'options' => $options,
					]
				);
    }
    
    public function getContentColumnType(): string
    {
        return $this->columnType;
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
		
		$options = array();
	    
		$view = Craft::$app->getView();
		$templateMode = $view->getTemplateMode();
		$view->setTemplateMode($view::TEMPLATE_MODE_SITE);
		
		$siteHandle = Craft::$app->getSites()->currentSite->handle;
		$fileName = "template.json";
		$arrayList = $this-> _jsonToArray($siteHandle . '/' . $fileName, true);
			
		$variables['element'] = $element;
		$variables['this'] = $this;	
		
		if ($this->_checkElementExists($this->dropdownOptions . '_options', $arrayList['dynamic']['values'])) :
			foreach ($arrayList['dynamic']['values'][$this->dropdownOptions . '_options'] as $key => $element) :
				if (!empty($element['value'])) :
					$options[] = array (
						'value' => $element['value'],
						'label' => $element['label']						
					);
				endif;
			endforeach;
		else:
				$options[] = 'None';	
		endif;
			
		$view->setTemplateMode($templateMode);	
		
		return Craft::$app->getView()->renderTemplate('extendify-for-craft-cms/_includes/forms/dropdown', [
				'name' => $this->handle,
				'value' => $value,
				'options' => $options,
			]);
    }
		
	public function _jsonToArray($file = null, $assoc = false) {
      $templateDir = Craft::$app->view->getTemplatesPath();
      
      if (!empty($file) && file_exists($templateDir . '/' . $file)) {
        $jsonRaw = file_get_contents($templateDir . '/' . $file);
        $jsonObject = json_decode($jsonRaw,$assoc);
        
        return $jsonObject;  
      }
      return null;
    }
	
	public function _checkElementExists($key = null, $array = null) {
      if(!empty($key) && !empty($array)) {
        if (array_key_exists($key, $array)) {
          return true;
        }
      }    
      return false;
    }
}