<?php

namespace centrillion\extendifyforcraftcms\controllers;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms as Extendify;

use Craft;
use craft\web\Controller as baseController;
use craft\helpers\UrlHelper;
use craft\helpers\StringHelper;

use yii\web\Response;

// https://github.com/enupal/backup/blob/master/src/controllers/WebhookController.php

class FeedDownloadController extends baseController
{

  public $allowAnonymous = true;

  // Disable CSRF validation for the entire controller
  public $enableCsrfValidation = false;

  public function actionStart() {
    $key = Craft::$app->request->getParam('key');
    $settings = Extendify::$plugin->getSettings();
    $response = [
        'success' => false
    ];

    if ($settings->secretKey) {
        if ($key == $settings->secretKey) {

          // create service functions to encaps the logic of downloading, spliting file sizes, and calling feedme queues.
          ray("Extendify For CraftCMS Plugin Controller called from URL");
          $response = [
              'success' => true
          ];

        } else {
            Craft::error("Wrong webhook key: ".$key, __METHOD__);
        }
    } else {
        Craft::error("Webhook is disabled", __METHOD__);
    }

    return $this->asJson($response);
  } 

}