<?php
namespace centrillion\extendifyforcraftcms\web\twig\variables;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms;

use Craft;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;

use yii\di\ServiceLocator;


class ExtendifyVariable extends ServiceLocator
{
	
		public $config;

    public function __construct($config = []) {
        $config['components'] = ExtendifyForCraftCms::$plugin->getComponents();

        parent::__construct($config);
    }
	
	  public function getPluginName() {
        return ExtendifyForCraftCms::$plugin->getPluginName();
    }

    public function getTabs() {
        $settings = ExtendifyForCraftCms::$plugin->getSettings();
        $enabledTabs = $settings->enabledTabs;

        $tabs = [
            'feeds' => [ 'label' => Craft::t('extendify-for-craft-cms', 'Feeds'), 'url' => UrlHelper::cpUrl('extendify-for-craft-cms/feeds') ],
            'logs' => [ 'label' => Craft::t('extendify-for-craft-cms', 'Logs'), 'url' => UrlHelper::cpUrl('extendify-for-craft-cms/logs') ],
            'help' => [ 'label' => Craft::t('extendify-for-craft-cms', 'Help'), 'url' => UrlHelper::cpUrl('extendify-for-craft-cms/help') ],
            'settings' => [ 'label' => Craft::t('extendify-for-craft-cms', 'Settings'), 'url' => UrlHelper::cpUrl('extendify-for-craft-cms/settings') ],
        ];

        if ($enabledTabs === '*' || $enabledTabs === 1 || !is_array($enabledTabs)) {
            return $tabs;
        }

        if (!$enabledTabs) {
            return [];
        }

        $selectedTabs = [];

        foreach ($enabledTabs as $enabledTab) {
            $selectedTabs[$enabledTab] = $tabs[$enabledTab];
        }

        return $selectedTabs;
    }
}