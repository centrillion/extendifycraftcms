<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace centrillion\extendifyforcraftcms\models;

use craft\base\Model;
use craft\web\UploadedFile;

/**
 * Class Submission
 *
 * @package craft\contactform
 */
class Submission extends Model
{
  public $name;
  public $email;
  public $subject;
  public $message;
  public $hidden;
  public $fromName;
  public $fromEmail;
  public $replyToEmail;
  public $ccEmail;
  public $bccEmail;
  public $emailRecipient = false;
  public $recipientSubject;
  public $attachment;

  /**
   * @inheritdoc
   */
  public function attributeLabels()
  {
    // add the rest of the form fields here....
    return [
      'name' => \Craft::t('extendify-for-craft-cms', 'Name'),
      'email' => \Craft::t('extendify-for-craft-cms', 'Email'),
      'subject' => \Craft::t('extendify-for-craft-cms', 'Subject'),
      'message' => \Craft::t('extendify-for-craft-cms', 'Message'),
    ];
  }

  /**
   * @inheritdoc
   */
  public function rules()
  {
    return [
      [['name','email','message'], 'required'],
      [['email','fromEmail','replyToEmail','ccEmail','bccEmail'], 'email']
    ];
  }
}