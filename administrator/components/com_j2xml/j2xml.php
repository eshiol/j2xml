<?php
/**
 * @version		3.7.171 administrator/components/com_j2xml/j2xml.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.6.0
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2018 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

if(!defined('DS')) define('DS',DIRECTORY_SEPARATOR);

$params = JComponentHelper::getParams('com_j2xml');
if ($params->get('debug', 0)) 
{
	ini_set('display_errors', 'On');
	error_reporting(E_ALL | E_STRICT);
}

jimport('joomla.log.log');
if ($params->get('debug') || defined('JDEBUG') && JDEBUG)
{
	JLog::addLogger(array('text_file' => $params->get('log', 'eshiol.log.php'), 'extension' => 'com_j2xml_file'), JLog::DEBUG, array('lib_j2xml','com_j2xml'));
}
JLog::addLogger(array('logger' => 'messagequeue', 'extension' => 'com_j2xml'), JLOG::ALL & ~JLOG::DEBUG, array('lib_j2xml','com_j2xml'));
if ($params->get('phpconsole') && class_exists('JLogLoggerPhpconsole'))
{
	JLog::addLogger(array('logger' => 'phpconsole', 'extension' => 'com_j2xml_phpconsole'),  JLOG::DEBUG, array('lib_j2xml','com_j2xml'));
}
JLog::add(new JLogEntry('J2XML', JLog::DEBUG, 'com_j2xml'));

if (file_exists(JPATH_LIBRARIES.'/vendor/eshiol/oauth2-joomla/src/Provider/JoomlaProvider.php'))
{
	$params->set('oauth2', 1);
}

// Merge the default translation with the current translation
$lang = JFactory::getLanguage();
// Back-end translation
$lang->load('com_j2xml', JPATH_ADMINISTRATOR, 'en-GB', true);
$lang->load('com_j2xml', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
$lang->load('com_j2xml', JPATH_ADMINISTRATOR, null, true);

$lang->load('lib_j2xml', JPATH_SITE, null, false, false)
	|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, false, false)
	// Fallback to the lib_j2xml file in the default language
	|| $lang->load('lib_j2xml', JPATH_SITE, null, true)
	|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, true);

$jinput = JFactory::getApplication()->input;
$controllerClass = 'J2XMLController';
if (class_exists('JPlatform'))
{
	$task = $jinput->getCmd('task', 'cpanel');
}
elseif ($view = $jinput->getCmd('view') == 'websites')
{
	JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_j2xml', false));
}

if (strpos($task, '.') === false)
{
	$controllerPath	= JPATH_COMPONENT_ADMINISTRATOR.DS.'controller.php';
}
else
{
	// We have a defined controller/task pair -- lets split them out
	list($controllerName, $task) = explode('.', $task);

	// Define the controller name and path
	$controllerName	= strtolower($controllerName);

	$controllerPath	= JPATH_COMPONENT_ADMINISTRATOR.DS.'controllers'.DS.$controllerName;
	$format = $jinput->getCmd('format');
	if ($format == 'json')
	{
		$controllerPath .= '.'.strtolower($format);
	}
	$controllerPath	.= '.php';
	// Set the name for the controller and instantiate it
	$controllerClass .= ucfirst($controllerName);
}

// If the controller file path exists, include it ... else lets die with a 500 error
if (file_exists($controllerPath)) 
{
	require_once($controllerPath);
} 
else
{
	throw new Exception('Invalid Controller '.$controllerName);
}

JLog::add(new JLogEntry($controllerClass, JLog::DEBUG, 'com_j2xml'));

if (class_exists($controllerClass)) 
{
	$controller = new $controllerClass();
} 
else 
{
	throw new Exception('Invalid Controller Class '.$controllerClass);
}

//$config	= JFactory::getConfig();

// Perform the Request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
