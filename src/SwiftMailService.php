<?php
namespace Mouf\Utils\Mailer;

use Mouf\Utils\Log\LogInterface;
use Psr\Log\LoggerInterface;

/**
 * This class sends mails using the Swift mailer.<br/>
 * <br/>
 * Note: if you are running a Windows machine, and therefore don't have an SMTP server, 
 * for testing purpose, you can use your gmail account:<br/>
 * <br/>
 * <ul>
 * <li>host => 'smtp.gmail.com'</li>
 * <li>ssl => 'tls'</li>
 * <li>port => 587</li>
 * <li>auth => 'login'</li>
 * <li>username => <em>Your gmail mail address</em></li>
 * <li>password => <em>Your password</em></li>
 * </ul>
 * Note: For secured mail that use the tls or ssl encrypting, the php_openssl extension must be installed.
 */
class SwiftMailService implements MailServiceInterface {
	
	/** 
	 * @var \Swift_Mailer
	 */
	private $swiftMailer;
	/**
	 * @var LoggerInterface
	 */
	private $log;
	
	/**
	 * The constructor takes in parameter a SwiftMailTransport.
	 * 
	 * @param \Swift_Mailer $swiftMailer
	 */
	public function __construct(\Swift_Mailer $swiftMailer) {
		$this->swiftMailer = $swiftMailer;
	}
	
	/**
	 * The logger to use (optionnal)
	 *
	 * @param LogInterface $log
	 */
	public function setLog(LogInterface $log) {
		$this->log = $log;
		return $this;
	}
	
	/**
	 * Sends the mail passed in parameter.
	 *
	 * @param MailInterface $mail The mail to send.
	 */
	public function send(MailInterface $mail) {
		
		$swiftMail = \Swift_Message::newInstance();
		
		$swiftMail->setCharset($mail->getEncoding());
                
		$swiftMail->setSubject($mail->getTitle());

		// Note: HTML version must be added as body and text as part for inline attachments to work.
		// See: https://github.com/swiftmailer/swiftmailer/issues/184
		if ($mail->getBodyHtml() != null) {
			$swiftMail->setBody($mail->getBodyHtml(), 'text/html');
			if ($mail->getBodyText()) {
				$swiftMail->addPart($mail->getBodyText(), 'text/plain');
			}
		} else {
			$swiftMail->setBody($mail->getBodyText(), 'text/plain');
		}
		
		if ($mail->getFrom()) {
			$swiftMail->setFrom(array($mail->getFrom()->getMail() => $mail->getFrom()->getDisplayAs()));
		}
                
		$toRecipients = array ();
		foreach ( $mail->getToRecipients () as $recipient ) {
			$toRecipients [$recipient->getMail ()] = $recipient->getDisplayAs ();
		}
		$swiftMail->setTo ( $toRecipients );
		
		$ccRecipients = array ();
		foreach ( $mail->getCcRecipients () as $recipient ) {
			$ccRecipients [$recipient->getMail ()] = $recipient->getDisplayAs ();
		}
		$swiftMail->setCc ( $ccRecipients );
		
		$bccRecipients = array ();
		foreach ( $mail->getBccRecipients () as $recipient ) {
			$bccRecipients [$recipient->getMail ()] = $recipient->getDisplayAs ();
		}
		$swiftMail->setBcc ( $bccRecipients );

        if (!is_null($mail->getAttachements())) {
            foreach ($mail->getAttachements() as $attachment) {
                /*$encodingStr = $attachment->getEncoding();
			switch ($encodingStr) {
				case "ENCODING_7BIT":
					$encoding = ZendMime::ENCODING_7BIT;
					break;
				case "ENCODING_8BIT":
					$encoding = ZendMime::ENCODING_8BIT;
					break;
				case "ENCODING_QUOTEDPRINTABLE":
					$encoding = ZendMime::ENCODING_QUOTEDPRINTABLE;
					break;
				case "ENCODING_BASE64":
					$encoding = ZendMime::ENCODING_BASE64;
					break;
			}*/
                $attachment_disposition = $attachment->getAttachmentDisposition();
                switch ($attachment_disposition) {
                    case "inline":
                        $file = new \Swift_EmbeddedFile($attachment->getFileContent(), $attachment->getFileName(), $attachment->getMimeType());
                        break;
                    case "attachment":
                    case "":
                    case null:
                    $file = new \Swift_Attachment($attachment->getFileContent(), $attachment->getFileName(), $attachment->getMimeType());
                    break;
                    default:
                        throw new Exception("Invalid attachment disposition for mail. Should be one of: 'inline', 'attachment'");
                }
			
                $contentId = $attachment->getContentId();
                if ($contentId) {
                    $file->setId($contentId);
                }
			
                $swiftMail->attach($file);
            }
        }
		

		//$body = new MimeMessage();
		//$body->setParts($parts);
                
                
		
		
		/*if ($mail->getBodyText() != null && $mail->getBodyHtml() != null) {
			$swiftMail->getHeaders()->get('content-type')->setType('multipart/alternative');
		}*/

		$this->swiftMailer->send($swiftMail);

		// Let's log the mail:
		if ($this->log) {
			$recipients = array_merge($mail->getToRecipients(), $mail->getCcRecipients(), $mail->getBccRecipients());
			$recipientMails = array();
			foreach ($recipients as $recipient) {
				$recipientMails[] = $recipient->getMail();
			}
			
			$this->log->debug("Sending mail to ".implode(", ", $recipientMails).". Mail subject: ".$mail->getTitle());
		}

	}
}
?>