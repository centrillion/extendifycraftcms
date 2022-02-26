<?php

namespace centrillion\extendifyforcraftcms\controllers;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms as Extendify;
use centrillion\extendifyforcraftcms\helper\FeedHelper;
use centrillion\extendifyforcraftcms\queue\jobs\FeedDownload;

use Craft;
use craft\web\Controller;

use yii\web\Response;


class SettingsController extends Controller {

    public function actionIndex() {
        ray("Loading Extendify For CraftCMS Plugin > Settings View");
        $settings = Extendify::$plugin->getSettings();

        ray("Settings Saved Values");
        ray($settings)->red();

        return $this->renderTemplate('extendify-for-craft-cms/settings', [
            'settings' => $settings,
        ]);
    }

    public function actionSaveSettings() {

        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $settings = Craft::$app->getRequest()->getBodyParam('settings', []);
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('app', "Couldn't save plugin settings."));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);

            return null;
        }
        
        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDeleteAllProducts() {

    }

    public function actionDownloadFeeds() {

        // encap this into a service
        $plugin   = Extendify::getInstance();

        $variables = [];
        $variables['plugin'] = $plugin;

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        $settings = $plugin->getSettings();

        if ($settings === null) {
            // throw an error to say that no settings have been set
        }

        $productFeeds = $settings->productFeeds ? $settings->productFeeds : null; 

        if ($productFeeds === null) {
            // throw an error to say that no productFeeds have been set
        }

        //$path = Craft::$app->getPath();
        //$dir = $path->getTempPath() . '/product-feeds/';

        $dir = Craft::getAlias('@webroot') . '/product-feeds/';

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        foreach($productFeeds as $feed) {

            //Craft::info('Pushing ' . $feed[0] . ' to Queue', __METHOD__);

            $result = FeedHelper::prepareFeed($feed);
            $result = FeedHelper::downloadFeed($contactId);
            $result = FeedHelper::splitFeed($contactId);
            $result = FeedHelper::scheduleFeedMeAPI($contactId);

            Craft::$app->getQueue()->delay(0)->push(new FeedDownload([
                'feedName' => $feed[0],
                'feedUrl' => $feed[1],
                'feedType' => $feed[2],
                'dir' => $dir,
            ]));
        }
        
        $variables['message'] = "Feeds have been sent to the queue to be process.";
        
        return $this->renderTemplate('extendify-for-craft-cms/settings/index', $variables);
    }

    public function actionScheduleFeeds() {
        // https://craftcms.stackexchange.com/questions/29603/craft-3-feed-me-auto-run-all-feed
        // https://craftcms.stackexchange.com/questions/32933/trigger-feedme-import-from-plugin/32955#32955
        // https://nystudio107.com/blog/robust-queue-job-handling-in-craft-cms
        // https://docs.craftcms.com/api/v2/craft-tasksservice.html
        // https://docs.craftcms.com/feed-me/v4/feature-tour/trigger-import-via-cron.html
    }
}