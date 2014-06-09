<?php
use Mouf\MoufManager;

// Controller declaration

MoufManager::getMoufManager()->declareComponent('swiftmailserviceinstall', 'Mouf\\Utils\\Mailer\\Controllers\\SwiftMailServiceInstallController', true);
MoufManager::getMoufManager()->bindComponents('swiftmailserviceinstall', 'template', 'moufInstallTemplate');
MoufManager::getMoufManager()->bindComponents('swiftmailserviceinstall', 'content', 'block.content');

