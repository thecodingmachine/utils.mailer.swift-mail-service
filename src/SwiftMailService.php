<?php
namespace Mouf\Utils\Mailer;

use Mouf\Utils\Log\LogInterface;

use Zend\Mail\Message;

use Zend\Mail\Transport\SmtpOptions;

use Zend\Mail\Transport\Smtp;

use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime as ZendMime;
/**
 * This class sends mails using the Zend Framework SMTP mailer.<br/>
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
 * 
 * @Component
 */
class SwiftMailService implements MailServiceInterface {
	
	/**
	 * The SMTP host to use.
	 *
	 * @Property
	 * @Compulsory
	 * @var string
	 */
	public $host = "127.0.0.1";
	
	/**
	 * The logger to use.
	 *
	 * @Property
	 * @Compulsory
	 * @var LogInterface
	 */
	public $log;
	
	/**
	 * The authentication mode.
	 * Can be one of: "", "plain", "login", "crammd5"
	 *
	 * @Property
	 * //@OneOf("plain", "login", "crammd5")
	 * @var string
	 */
	public $auth;
	
	/**
	 * The user to authenticate.
	 *
	 * @Property
	 * @var string
	 */
	public $userName;
	
	/**
	 * The password.
	 *
	 * @Property
	 * @var string
	 */
	public $password;
	
	/**
	 * The port to use.
	 *
	 * @Property
	 * @var int
	 */
	public $port;
	
	/**
	 * The SSL mode to use, if any. ("ssl" or "tls")
	 *
	 * @Property
	 * // @OneOf("ssl", "tls")
	 * @var string
	 */
	public $ssl;
	
	/**
	 * The Zend mail transport.
	 *
	 * @var \Swift_SmtpTransport
	 */
	private $mailTransport;
	
	/**
	 * Sends the mail passed in parameter.
	 *
	 * @param MailInterface $mail The mail to send.
	 */
	public function send(MailInterface $mail) {
		$this->initMailTransport();
		
		$swiftMail = \Swift_Message::newInstance();
		
		$swiftMail->setCharset($mail->getEncoding());
                
                $swiftMail->setSubject($mail->getTitle());
		
		/*if ($mail->getBodyText() != null) {
                        $swiftMail->setBody($mail->getBodyText());
			$text = new MimePart($mail->getBodyText());
			$text->type = "text/plain";
			$text->encoding = $mail->getEncoding();
			$parts[]  = $text;
		}*/
		if ($mail->getBodyHtml() != null) {
                        $swiftMail->setBody($mail->getBodyHtml(),'text/html');
			/*$bodyHtml = new MimePart($mail->getBodyHtml());
			$bodyHtml->type = "text/html";
			$bodyHtml->encoding = $mail->getEncoding();
			$parts[]  = $bodyHtml;*/
		}
		
		if ($mail->getFrom()) {
			$swiftMail->setFrom(array($mail->getFrom()->getMail() => $mail->getFrom()->getDisplayAs()));
		}
                
                $toRecipients = array();
		foreach ($mail->getToRecipients() as $recipient) {
                        $toRecipients[$recipient->getMail()] = $recipient->getDisplayAs();
		}
                $swiftMail->setTo($toRecipients);
                
                $ccRecipients = array();
		foreach ($mail->getCcRecipients() as $recipient) {
                        $ccRecipients[$recipient->getMail()] = $recipient->getDisplayAs();
		}
                $swiftMail->setCc($ccRecipients);
                
                $bccRecipients = array();
		foreach ($mail->getBccRecipients() as $recipient) {
                        $bccRecipients[$recipient->getMail()] = $recipient->getDisplayAs();
		}
                $swiftMail->setBcc($bccRecipients);
                
                //TODO Attachment
		/*foreach ($mail->getAttachements() as $attachment) {
			$encodingStr = $attachment->getEncoding();
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
			}
			$attachment_disposition = $attachment->getAttachmentDisposition();
			switch ($attachment_disposition) {
				case "inline":
					$attachment_disposition = ZendMime::DISPOSITION_INLINE;
					break;
				case "attachment":
					$attachment_disposition = ZendMime::DISPOSITION_ATTACHMENT;
					break;
				case "":
				case null:
					$attachment_disposition = null;
					break;
				default:
					throw new Exception("Invalid attachment disposition for mail. Should be one of: 'inline', 'attachment'");
			}
			
			$mimePart = new MimePart($attachment->getFileContent());
			$mimePart->type = $attachment->getMimeType();
			$mimePart->disposition = $attachment_disposition;
			$mimePart->encoding = $encoding;
			$mimePart->filename = $attachment->getFileName();
			$mimePart->id = $attachment->getContentId();
			
			$parts[] = $mimePart;
		}*/
		

		//$body = new MimeMessage();
		//$body->setParts($parts);
                
                
		
		
		/*if ($mail->getBodyText() != null && $mail->getBodyHtml() != null) {
			$swiftMail->getHeaders()->get('content-type')->setType('multipart/alternative');
		}*/

		$this->mailTransport->send($swiftMail);


		// Let's log the mail:
		$recipients = array_merge($mail->getToRecipients(), $mail->getCcRecipients(), $mail->getBccRecipients());
		$recipientMails = array();
		foreach ($recipients as $recipient) {
			$recipientMails[] = $recipient->getMail();
		}
		$this->log->debug("Sending mail to ".implode(", ", $recipientMails).". Mail subject: ".$mail->getTitle());

	}
	
	
	private function initMailTransport() {
		if ($this->mailTransport != null) {
			return;
		}
                
                $transport = \Swift_SmtpTransport::newInstance($this->host, $this->port, $this->ssl);
                
		/*if (!empty($this->auth)) {
			$config['connection_class'] = $this->auth;
		}*/
		if (!empty($this->userName)) {
			$transport->setUsername($this->userName);
		}
		if (!empty($this->password)) {
			$transport->setPassword($this->password);
		}
		/*if (!empty($this->ssl)) {
			$config['connection_config']['ssl'] = $this->ssl;
		} */
		
		
                $mailer = \Swift_Mailer::newInstance($transport);
                
                $this->mailTransport = $mailer;
                
		/*$this->mailTransport = new Smtp();
		$options   = new SmtpOptions($config);

		$this->mailTransport->setOptions($options);*/
		
	}
}
?>