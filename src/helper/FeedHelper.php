<?php

namespace centrillion\extendifyforcraftcms\helper;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms as Extendify;
use centrillion\extendifyforcraftcms\queue\jobs\FeedDownload;

use Craft;

use yii\base\Component as baseComponent;
use yii\base\InvalidConfigException;

class FeedHelper extends baseComponent {

    public function prepareFeed() {
        // lets allow users to select the default path for downloading feeds
        $dir = Craft::getAlias('@webroot') . '/product-feeds/';

        // encap this in a function
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $filename = $this->sanitizeFileName($this->feedName);
        $path = $this->dir . $filename . $this->feedType;

        // remove existing file from the directory, so that youcan redownload the latest feed
        if (!empty($this->feedName) && file_exists($path)) {
            unlink($path);
            Craft::info('Deleted previous feed file ', __METHOD__);
        }
    }

    public function downloadFeed() {

        Craft::$app->getQueue()->delay(0)->push(new FeedDownload([
            'feedName' => $feed[0],
            'feedUrl' => $feed[1],
            'feedType' => $feed[2],
            'dir' => $dir,
        ]));
    }

    public function splitFeed() {
        // split the original feed file into multiple files to make it easier to
        // process it later.
        if($this->feedType == '.csv') {
            Craft::info('Started Splitting File in to Chunks', __METHOD__);
            $this->splitFileInChunks($path,'-split-');

            // Send to Queue
            Craft::$app->getQueue()->delay(0)->push(new FeedDownload([
                'feedName' => $feed[0],
                'feedUrl' => $feed[1],
                'feedType' => $feed[2],
                'dir' => $dir,
            ]));
        }
    }

    // https://craftcms.stackexchange.com/questions/29603/craft-3-feed-me-auto-run-all-feed
    public function scheduleFeedMeAPI() {
        // use Craft;
        // use verbb\feedme\FeedMe;
        // use verbb\feedme\queue\jobs\FeedImport;

        // $feeds = FeedMe::$plugin->feeds->getFeeds();

        // foreach ($feeds as $feed) {
        //     $limit = null;
        //     $offset = null;
        //     $processedElementIds = [];

        //     Craft::$app->getQueue()->delay(0)->push(new FeedImport([
        //         'feed' => $feed,
        //         'limit' => $limit,
        //         'offset' => $offset,
        //         'processedElementIds' => $processedElementIds,
        //     ]));
        // }
    }

    private function sanitizeFileName($name) {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower( $name ));
    }
}