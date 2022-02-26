<?php
/**
 * Extendify for Craft CMS plugin for Craft CMS 3.x
 *
 * Extendify plugin used to extend Craft CMS Functionality 
 *
 * @link      www.centrillion.com.au
 * @copyright Copyright (c) 2018 Albert Nassif
 */

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */

namespace centrillion\extendifyforcraftcms;

use centrillion\extendifyforcraftcms\models\Settings;
use centrillion\extendifyforcraftcms\services\Mailer as Mailer;
use centrillion\extendifyforcraftcms\services\FeedService as Feed;
use centrillion\extendifyforcraftcms\web\twig\Extension;
use centrillion\extendifyforcraftcms\web\twig\variables\ExtendifyVariable;
use centrillion\extendifyforcraftcms\fields\Dropdown as DropdownField;

use Craft;
use craft\base\Plugin;
use craft\events\TemplateEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\helpers\UrlHelper;
//use craft\services\Plugins;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use craft\mail\Message;

use yii\base\Event;
use yii\base\Module;

class ExtendifyForCraftCms extends Plugin
{

    public static $plugin;
    public $schemaVersion = '1.1.2';
    public $hasCpSettings = true;

    // https://github.com/putyourlightson/craft-sherlock/tree/454af038d234e9cebcfced599dabf6ca663b3a9d/src
    // https://craftcms.com/docs/3.x/extend/cp-templates.html#form-pages
    // https://craftcms.com/docs/3.x/extend/plugin-settings.html#advanced-settings-pages

    public function init() {

      parent::init();
      self::$plugin = $this;
      
      $this->_registerCpRoutes();
      $this->_registerSiteRoutes();
      $this->_registerComponents();
      $this->_registerTwigExtensions();
      $this->_registerVariables();
      $this->_registerComponentFieldTypes();
    }

    public function getPluginName() {
      return Craft::t('extendify-for-craft-cms', 'Extendify');
    }
  
    public function afterInstall() {
      Craft::$app->controller->redirect(UrlHelper::cpUrl('extendify-for-craft-cms/welcome'))->send();
    }

    public function getSettingsResponse() {
      Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('extendify-for-craft-cms/settings'));
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings {
      return new Settings();
    }
  
    // Private Methods
    // =========================================================================

    private function _registerCpRoutes(){
      Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
          $event->rules = array_merge($event->rules, [
              'extendify-for-craft-cms/settings' => 'extendify-for-craft-cms/settings/index',
          ]);
      });
    }

    private function _registerSiteRoutes(){
      Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
          $event->rules = array_merge($event->rules, [
              'extendify/start' => 'extendify-for-craft-cms/feed-download/start',
          ]);
      });
    }

    private function _registerComponents() {
        // Register services as components
        $this->setComponents([
            'mailer' => Mailer::class,
            'feed' => Feed::class,
        ]);
    }

    private function _registerTwigExtensions() {
      Craft::$app->view->registerTwigExtension(new Extension);
    }
  
    private function _registerVariables() {
      Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
          $event->sender->set('extendifyforcraftcms', ExtendifyVariable::class);
      });
    }
 
    private function _registerComponentFieldTypes() {
      Event::on(Fields::class,Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
        $event->types[] = DropdownField::class;
      });
    }
}
