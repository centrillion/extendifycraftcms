<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace centrillion\extendifyforcraftcms\models;

use craft\base\Model;

class Settings extends Model
{
  public $fromEmail;
  public $fromName;
  public $replyToEmail;
  public $ccEmail;
  public $bccEmail;
  public $subject;
  public $emailRecipient = false;
  public $recipientSubject;
  public $allowAttachments = false;
  public $successFlashMessage;
  public $mailchimpApiKey;
  public $mailchimpListId;
  public $mailchimpDoubleOptin;
  public $googleRecaptchaSiteKey;
  public $googleRecaptchaSecretKey;

  public $emailLogo;
  public $emailBackgroundColour;
  public $emailHeaderColour;
  public $emailSignatureSection;

  public $secretKey;
  public $productFeeds = [];

  public function init()
  {
    parent::init();

    if ($this->successFlashMessage === null) {
      $this->successFlashMessage = \Craft::t('extendify-for-craft-cms', 'Your message has been sent.');
    }
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['fromEmail', 'successFlashMessage'], 'required'],
      [['fromEmail', 'fromName', 'replyToEmail', 'ccEmail', 'bccEmail', 'subject', 'recipientSubject', 'successFlashMessage', 'mailchimpApiKey', 'mailchimpListId', 'googleRecaptchaSiteKey', 'googleRecaptchaSecretKey' ], 'string'],
    ];
  }
}