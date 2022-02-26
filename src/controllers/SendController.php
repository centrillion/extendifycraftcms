<?php

namespace centrillion\extendifyforcraftcms\controllers;

use yii\web\Response;

use Craft;
use craft\web\Controller as baseController;
use craft\web\UploadedFile;
use craft\helpers\StringHelper;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms as Extendify;
use centrillion\extendifyforcraftcms\models\Submission;

class SendController extends baseController
{
  // Properties
  // =========================================================================

  /**
   * @inheritdoc
   */
  public $allowAnonymous = true;

  // Public Methods
  // =========================================================================

  /**
   * Sends a contact form submission.
   *
   * @return Response|null
   */
  public function actionIndex()
  {
    $this->requirePostRequest();
    $request  = Craft::$app->getRequest();
    $plugin   = Extendify::getInstance();
    $settings = $plugin->getSettings();

    // Get POST Data
    // Options Object encoded in base64 and Json
    $name    = $request->getBodyParam('name');
    $email   = $request->getBodyParam('email');
    $message = $request->getBodyParam('message');
    $options = $request->getBodyParam('options');
    $options = base64_decode($options);
    $options = json_decode($options);
    
    // Basic version will need to hash the response and see if we can do a key value comparsion to verify the values.
    $hidden = $request->getBodyParam('hidden');
                         
//     if ($request->getAcceptsJson()) {
//         return $this->asJson(['success' => !empty($hidden) ? json_encode($hidden) : false ]);
//     }
    
    $template = $request->getBodyParam('template');
   
    // Additional Options 
    $submission = new Submission();
    $submission->name             = is_string($name)                      ? $name                      : null;
    $submission->email            = is_string($email)                     ? $email                     : null;
    $submission->fromName         = is_string($options->fromName)         ? $options->fromName         : null;
    $submission->fromEmail        = is_string($options->fromEmail)        ? $options->fromEmail        : null;
    $submission->replyToEmail     = is_string($options->replyToEmail)     ? $options->replyToEmail     : null;
    $submission->ccEmail          = is_string($options->ccEmail)          ? $options->ccEmail          : null;
    $submission->bccEmail         = is_string($options->bccEmail)         ? $options->bccEmail         : null;
    $submission->subject          = is_string($options->subject)          ? $options->subject          : null;
    $submission->emailRecipient   = is_bool($options->emailRecipient)     ? $options->emailRecipient   : false;
    $submission->recipientSubject = is_string($options->recipientSubject) ? $options->recipientSubject : null;
    $template                     = is_string($template)                  ? $template                  : null;
    
    // validating name field and email field, which are required fields.
    // future check if its a real email address before processing the email.
    if (empty($submission->name) || empty($submission->email)) {
      if ($request->getAcceptsJson()) {
        return $this->asJson(['errors' => 'Please enter your name and a valid email address and submit your enquiry again.']);
      }
      
      Craft::$app->getSession()->setError(Craft::t('extendify-for-craft-cms', 'There was a problem with your submission, please check the form and try again!'));
      Craft::$app->getUrlManager()->setRouteParams([
          'variables' => ['message' => $submission]
      ]);

      return null;
    }
    
    // validating message field, which is required field, storing additional form field data.
    if (is_array($message)) {
      $submission->message = array_filter($message, function($value) {
        return $value !== '';
      });
    } else {
      $submission->message = $message;
    }
    
    // validating hidden field, which is required field, storing additional form field data.
    if (is_array($hidden)) {
      $submission->hidden = array_filter($hidden, function($value) {
        return $value !== '';
      });
    } else {
      $submission->hidden = $hidden;
    }
    
    // validating attachment field, not required field.
    if ($settings->allowAttachments && isset($_FILES['attachment']) && isset($_FILES['attachment']['name'])) {
      if (is_array($_FILES['attachment']['name'])) {
        $submission->attachment = UploadedFile::getInstancesByName('attachment');
      } else {
        $submission->attachment = [UploadedFile::getInstanceByName('attachment')];
      }
    }
    
    Craft::warning('Check Test 1', 'extendify-plugin');

    // process form request and sends email
    if (!Extendify::$plugin->mailer->send($submission, $template)) {
      if ($request->getAcceptsJson()) {
        return $this->asJson(['errors' => $submission->getErrors()]);
      }

      Craft::$app->getSession()->setError(Craft::t('extendify-for-craft-cms', 'There was a problem with your submission, please check the form and try again!'));
      Craft::$app->getUrlManager()->setRouteParams([
        'variables' => ['message' => $submission]
      ]);
      
      Craft::warning('Check Test 5', 'extendify-plugin');

      return null;
    }

    if ($request->getAcceptsJson()) {
        return $this->asJson(['success' => true]);
    }

    Craft::$app->getSession()->setNotice($settings->successFlashMessage);
    return $this->redirectToPostedUrl($submission);
  }
}