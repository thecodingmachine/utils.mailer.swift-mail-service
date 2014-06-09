<?php
namespace Mouf\Utils\Mailer\Controllers;

use Mouf\Actions\InstallUtils;

use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\MoufManager;

/**
 * The controller managing the install process.
 * It will query the database details.
 *
 * @Component
 */
class SwiftMailServiceInstallController extends Controller {
	public $selfedit;
	
	/**
	 * The active MoufManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $moufManager;
	
	/**
	 * The template used by the main page for mouf.
	 *
	 * @Property
	 * @Compulsory
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 * @var HtmlBlock
	 */
	public $content;
	
	/**
	 * Displays the first install screen.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function defaultAction($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
				
		$this->content->addFile(__DIR__."/../views/installStep1.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Skips the install process.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
	 */
	public function skip($selfedit = "false") {
		InstallUtils::continueInstall($selfedit == "true");
	}

	protected $host;
	protected $port;
	protected $user;
	protected $password;
	protected $loggerInstanceName;
	
	/**
	 * Displays the second install screen.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function configure($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->host = "localhost";
		$this->port = "25";
		$this->user = "";
		$this->password = "";
		$this->loggerInstanceName = "psr.errorLogLogger";

		ob_start();
		$this->content->addFile(__DIR__."/../views/installStep2.php", $this);
		$this->template->toHtml();
		ob_end_flush();
	}
	
	
	
	/**
	 * Action to create the database connection.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only)
	 */
	public function install($host, $port, $user, $password, $auth, $ssl, $logger = null, $selfedit = "false") {
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$moufManager = $this->moufManager;
		$configManager = $moufManager->getConfigManager();
		
		$constants = $configManager->getMergedConstants();
		
		if (!isset($constants['MAIL_HOST'])) {
			$configManager->registerConstant("MAIL_HOST", "string", "localhost", "The SMTP host to use (the IP address or URL of the database server). For instance, use 'smtp.gmail.com' to connect to gmail SMTP servers.");
		}
		
		if (!isset($constants['MAIL_PORT'])) {
			$configManager->registerConstant("MAIL_PORT", "int", "25", "The SMTP server port (the port of the SMTP server, keep empty to use default port). For instance, use '587' to connect to gmail SMTP servers.");
		}
		
		if (!isset($constants['MAIL_AUTH'])) {
			$configManager->registerConstant("MAIL_AUTH", "string", "", "The authentication mechanism for the SMTP server. Can be one of '', 'plain', 'login', 'crammd5'. For instance, use 'login' to connect to gmail SMTP servers.");
		}
		
		if (!isset($constants['MAIL_USERNAME'])) {
			$configManager->registerConstant("MAIL_USERNAME", "string", "", "The username to connect to the SMTP server. For gmail, use your gmail address.");
		}
		
		if (!isset($constants['MAIL_PASSWORD'])) {
			$configManager->registerConstant("MAIL_PASSWORD", "string", "", "The password to connect to the SMTP server. For gmail, use your gmail password.");
		}
		
		if (!isset($constants['MAIL_SSL'])) {
			$configManager->registerConstant("MAIL_SSL", "string", "", "The SSL mode to use, to connect to the SMTP server, if any. Can be one of '', 'ssl', 'tls'. For gmail, use 'tls'.");
		}
		

		// Let's create the instances.
		$swiftMailTransport = InstallUtils::getOrCreateInstance('swiftMailTransport', NULL, $moufManager);
		$swiftMailTransport->setCode('$transport = \\Swift_SmtpTransport::newInstance(MAIL_HOST, MAIL_PORT, MAIL_SSL);

if (MAIL_USERNAME) {
	$transport->setUsername(MAIL_USERNAME);
}
if (MAIL_PASSWORD) {
	$transport->setPassword(MAIL_PASSWORD);
}
if (MAIL_AUTH) {
    $transport->setAuthMode(MAIL_AUTH);
}

return $transport;');
		
		$swiftMailer = InstallUtils::getOrCreateInstance('swiftMailer', NULL, $moufManager);
		$swiftMailer->setCode('return \\Swift_Mailer::newInstance($container->get(\'swiftMailTransport\'));');
				
		$swiftMailService = InstallUtils::getOrCreateInstance('swiftMailService', NULL, $moufManager);
		$swiftMailService->setCode('return new Mouf\\Utils\\Mailer\\SwiftMailService($container->get(\'swiftMailer\'));');
		
		$configPhpConstants = $configManager->getDefinedConstants();
		$configPhpConstants['MAIL_HOST'] = $host;
		$configPhpConstants['MAIL_PORT'] = $port;
		$configPhpConstants['MAIL_USERNAME'] = $user;
		$configPhpConstants['MAIL_PASSWORD'] = $password;
		$configPhpConstants['MAIL_AUTH'] = $auth;
		$configPhpConstants['MAIL_SSL'] = $ssl;
		$configManager->setDefinedConstants($configPhpConstants);
		
		$moufManager->rewriteMouf();		
		
		InstallUtils::continueInstall($selfedit == "true");
	}
}