<?php

namespace centrillion\extendifyforcraftcms\services;

use yii\base\Component as baseComponent;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Markdown;

use Craft;
use craft\elements\User;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\mail\Message;

use centrillion\extendifyforcraftcms\ExtendifyForCraftCms as Extendify;
use centrillion\extendifyforcraftcms\events\SendEvent;
use centrillion\extendifyforcraftcms\models\Submission;

class Mailer extends baseComponent
{
  // Constants
  // =========================================================================
  const FROM_NAME = 'Website';
  const SUBJECT = 'New submission from website';
  const RECIPIENT_SUBJECT = 'Thank you for your recent submission';
  const SUCCESS_MESSAGE = 'Email was sent successfully';
  const HTML_EMAIL_TEMPLATE = '_emails/default';

  /**
  * @event SubmissionEvent The event that is triggered before a message is sent
  */
  const EVENT_BEFORE_SEND = 'beforeSend';

  /**
  * @event SubmissionEvent The event that is triggered after a message is sent
  */
  const EVENT_AFTER_SEND = 'afterSend';

  // Public Methods
  // =========================================================================

  /**
  * Sends an email submitted through a contact form.
  *
  * @param Submission $submission
  * @param bool $runValidation Whether the section should be validated
  * @throws InvalidConfigException if the plugin settings don't validate
  * @return bool
  */
  public function send(Submission $submission, string $template, bool $runValidation = true): bool
  {
    // Get the plugin settings and make sure they validate before doing anything
    $settings = Extendify::getInstance()->getSettings();
    
    // Validate the values from the plugin settings
//     if (!$settings->validate()) {
//       throw new InvalidConfigException('The Extendify App Settings don’t validate.');
//     }

    if ($runValidation && !$submission->validate()) {
      Craft::info('Extendify App submission not saved due to validation error.', __METHOD__);
      return false;
    }
    
    $mailer = Craft::$app->getMailer();
    
    if(!empty($submission->fromEmail)) {
      $fromEmail = $submission->fromEmail;
    } elseif (!empty($settings->fromEmail)) {
      $fromEmail = is_string($settings->fromEmail) ? StringHelper::split($settings->fromEmail) : $settings->fromEmail;
    } elseif (!empty($mailer->from)) {
      $fromEmail = $this->getEmail($mailer->from);
    } else {
      Craft::info('Email App not configured correctly.', __METHOD__);
      return false;
    }
    
    if(!empty($submission->fromName)) {
      $fromName = $submission->fromName;
    } elseif (!empty($settings->fromName)) {
      $fromName = is_string($settings->fromName) ? $settings->fromName : self::FROM_NAME;
    } else {
      $fromName = self::FROM_NAME;
    }

    if(!empty($submission->replyToEmail)) {
      $replyToEmail = $submission->replyToEmail;
    } elseif (!empty($settings->replyToEmail)) {
      $replyToEmail = is_string($settings->replyToEmail) ? StringHelper::split($settings->replyToEmail) : $settings->replyToEmail;
    } elseif (!empty($mailer->from)) {
      $replyToEmail = $this->getEmail($mailer->from);
    } else {
      Craft::info('Email App not configured correctly.', __METHOD__);
      return false;
    }

    if(!empty($submission->ccEmail)) {
      $ccEmail = $submission->ccEmail;
    } elseif (!empty($settings->ccEmail)) {
      $ccEmail = is_string($settings->ccEmail) ? $settings->ccEmail : null;
    } else {
      $ccEmail = null;
    }

    if(!empty($submission->bccEmail)) {
      $bccEmail = $submission->bccEmail;
    } elseif (!empty($settings->bccEmail)) {
      $bccEmail = is_string($settings->bccEmail) ? $settings->bccEmail : null;
    } else {
      $bccEmail = null;
    }

    if(!empty($submission->subject)) {
      $subject = $submission->subject;
    } elseif (!empty($settings->subject)) {
      $subject = is_string($settings->subject) ? $settings->subject : self::SUBJECT;
    } else {
      $subject = self::SUBJECT;
    }
    
    if(!empty($submission->emailRecipient)) {
      $emailRecipient = $submission->emailRecipient;
    } elseif (!empty($settings->emailRecipient)) {
      $emailRecipient = is_bool($settings->recipientEmail) ? $settings->recipientEmail : false;
    } else {
      $emailRecipient = false;
    }
    
    if(!empty($submission->recipientSubject)) {
      $recipientSubject = $submission->recipientSubject;
    } elseif (!empty($settings->recipientSubject)) {
      $recipientSubject = is_string($settings->recipientSubject) ? $settings->recipientSubject : self::RECIPIENT_SUBJECT;
    } else {
      $recipientSubject = self::RECIPIENT_SUBJECT;
    }

    if(!empty($submission->mailchimpListId)) {
      $mailchimpListId = $submission->mailchimpListId;
    } elseif (!empty($settings->mailchimpListId)) {
      $mailchimpListId = is_string($settings->mailchimpListId) ? $settings->mailchimpListId : null;
    } else {
      $mailchimpListId = null;
    }

    $mailchimpApiKey       = is_string($settings->mailchimpApiKey)     ? $settings->mailchimpApiKey      : null;
    $mailchimpDoubleOptin  = is_bool($settings->mailchimpDoubleOptin)  ? $settings->mailchimpDoubleOptin : false;
    $successFlashMessage   = is_string($settings->successFlashMessage) ? $settings->successFlashMessage  : self::SUCCESS_MESSAGE;
    $template              = !empty($template) ? $template  : self::HTML_EMAIL_TEMPLATE;
    
    $style_vars = (object) array();
    $style_vars->backgroundColour = is_string($settings->emailBackgroundColour) ? $settings->emailBackgroundColour : null;
    $style_vars->headerColour     = is_string($settings->emailHeaderColour)     ? $settings->emailHeaderColour     : null;
    $style_vars->logoPath         = is_string($settings->emailLogo)             ? $settings->emailLogo             : null;
    $style_vars->signature        = is_string($settings->emailSignatureSection) ? $settings->emailSignatureSection : null;
    
    Craft::warning('Check Test 2', 'extendify-plugin');
    
    // Prep the message    
    $textBody = $this->compileTextBody($submission);
    $htmlBody = $this->compileHtmlBody($template, $style_vars, $submission);

    $message = (new Message())
    ->setFrom([$fromEmail => $fromName])
    ->setReplyTo($replyToEmail)
    ->setCc($ccEmail)
    ->setBcc($bccEmail)
    ->setSubject($subject)
    ->setTextBody($textBody)
    ->setHtmlBody($htmlBody);

    if ($submission->attachment !== null) {
      foreach ($submission->attachment as $attachment) {
        if (!$attachment) {
          continue;
        }
        $message->attach($attachment->tempName, [
          'fileName' => $attachment->name,
          'contentType' => FileHelper::getMimeType($attachment->tempName),
        ]);
      }
    }
    
    //$fromEmail = is_string($settings->fromEmail) ? StringHelper::split($settings->fromEmail) : $settings->fromEmail;

    // Fire a 'beforeSend' event
    $event = new SendEvent([
      'submission' => $submission,
      'message' => $message,
      'fromEmail' => $fromEmail,
    ]);
    
    $this->trigger(self::EVENT_BEFORE_SEND, $event);

    if ($event->isSpam) {
      Craft::info('Contact form submission suspected to be spam.', __METHOD__);
      return false;
    }
    
//     throw new InvalidConfigException($event->fromEmail);
    
    if (is_array($event->fromEmail)) {
      foreach ($event->fromEmail as $from) {
        $message->setTo($from);
        $mailer->send($message);
      }
    } else {
      $message->setTo($event->fromEmail);
      $mailer->send($message);
    }
    
    
    if ($emailRecipient && !empty($submission->email) && !empty($recipientSubject)) {
      $message->setTo($submission->email);
      $message->setSubject($recipientSubject);
      // Unsetting cc and bcc values
      $message->setCc(null);
      $message->setBcc(null);
      $mailer->send($message);
    }

    // Fire an 'afterSend' event
    if ($this->hasEventHandlers(self::EVENT_AFTER_SEND)) {
      $this->trigger(self::EVENT_AFTER_SEND, new SendEvent([
        'submission' => $submission,
        'message' => $message,
        'fromEmail' => $event->fromEmail,
      ]));
    }

    Craft::info('Email sucessfully sent.', __METHOD__);
    return true;
  }

  /**
  * Returns the email value on the given mailer $from property object.
  *
  * @param string|array|User|User[]|null $from
  * @return string
  * @throws InvalidConfigException if it can’t be determined
  */
  public function getEmail($from): string
  {
    if (is_string($from)) {
      return $from;
    }
    if ($from instanceof User) {
      return $from->email;
    }
    if (is_array($from)) {
      $first = reset($from);
      $key = key($from);
      if (is_numeric($key)) {
        return $this->getEmail($first);
      }
      return $key;
    }
    throw new InvalidConfigException('Can\'t determine email from email config settings.');
  }

  /**
  * Compiles the real email textual body from the submitted message.
  *
  * @param Submission $submission
  * @return string
  */
  public function compileTextBody(Submission $submission): string
  {
    $fields = [];

    if ($submission->name) {
      $fields[Craft::t('extendify-for-craft-cms', 'Name')] = $submission->name;
    }

    $fields[Craft::t('extendify-for-craft-cms', 'Email')] = $submission->email;

    if (is_array($submission->message)) {
      $body = $submission->message['body'] ?? '';
      $fields = array_merge($fields, $submission->message);
      unset($fields['body']);
    } else {
      $body = (string)$submission->message;
    }
    
    Craft::warning('Check Test 3', 'extendify-plugin');
    
    if (is_array($submission->hidden)) {
      $fields = array_merge($fields, $submission->hidden);
    }  
    
    Craft::warning('Check Test 4', 'extendify-plugin');

    $text = '';

    foreach ($fields as $key => $value) {
      $text .= ($text ? "\n" : '')."- **{ucwords(str_replace('_',' ',$key))}:** ";
      if (is_array($value)) {
        $text .= implode(', ', $value);
      } else {
        $text .= $value;
      }
    }

    if ($body !== '') {
      $body = preg_replace('/\R/', "\n\n", $body);
      $text .= "\n\n".$body;
    }

    return $text;
  }

  /**
  * Compiles the real email HTML body from the compiled textual body.
  *
  * @param string $textBody
  * @return string
  */
  public function compileHtmlBody(string $template = null, Object $style = null, Submission $submission): string
  { 
    if ($template) {
      // Render the set template
      $html = Craft::$app->view->renderTemplate($template,['submission' => $submission, 'style' => $style]);
    } else {
      $textBody = $this->compileTextBody($submission);
      $html     = Html::encode($textBody);
      $html     = Markdown::process($html);
    }

    return $html;
  }
}