<?php
namespace Mouf\Utils\Mailer;

use Mouf\Utils\Log\LogInterface;

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
	 * The constructor takes in parameter a SwiftMailTransport.
	 * 
	 * @param \Swift_Transport_MailTransport $swiftMailTransport
	 */
	public function __construct(\Swift_Transport_MailTransport $swiftMailTransport) {
		$this->mailTransport = $swiftMailTransport;
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
		if ($this->log) {
			$this->log->debug("Sending mail to ".implode(", ", $recipientMails).". Mail subject: ".$mail->getTitle());
		}

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