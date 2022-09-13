<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2022 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

//JLoader::registerNamespace('eshiol\\j2xml', JPATH_LIBRARIES);

$params = JComponentHelper::getParams('com_j2xml');
if ($params->get('debug', 0))
{
	ini_set('display_errors', 'On');
	error_reporting(E_ALL | E_STRICT);
}

JLoader::import('joomla.log.log');
if ($params->get('debug') || defined('JDEBUG') && JDEBUG)
{
	JLog::addLogger(
		array('text_file' => $params->get('log', 'eshiol.log.php'),	'extension' => 'com_j2xml_file'),
		JLog::DEBUG | JLog::ERROR,
		array('lib_j2xml', 'com_j2xml'));
}
JLog::addLogger(
	array('logger' => 'messagequeue', 'extension' => 'com_j2xml'),
	JLog::ALL & ~JLog::DEBUG,
	array('lib_j2xml', 'com_j2xml'));

$version = new JVersion();
JFactory::getDocument()->addScriptOptions('J2XML', array('Joomla' => ($version->isCompatible('4') ? 4 : 3) ));

// Merge the default translation with the current translation
$lang = JFactory::getApplication()->getLanguage();
// Back-end translation
$lang->load('com_j2xml', JPATH_ADMINISTRATOR, 'en-GB', true);
$lang->load('com_j2xml', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
$lang->load('com_j2xml', JPATH_ADMINISTRATOR, null, true);

$lang->load('lib_j2xml', JPATH_SITE, null, false, false) || $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, false, false) ||
// Fallback to the lib_j2xml file in the default language
$lang->load('lib_j2xml', JPATH_SITE, null, true) || $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, true);

$jinput = JFactory::getApplication()->input;
$controllerClass = 'J2xmlController';
$task = $jinput->getCmd('task', '');

if (strpos($task, '.') === false)
{
	$controllerPath = JPATH_COMPONENT_ADMINISTRATOR . '/controller.php';
}
else
{
	// We have a defined controller/task pair -- lets split them out
	list ($controllerName, $task) = explode('.', $task);

	// Define the controller name and path
	$controllerName = strtolower($controllerName);

	$controllerPath = JPATH_COMPONENT_ADMINISTRATOR . '/controllers/' . $controllerName;
	$format = $jinput->getCmd('format');
	if ($format == 'json')
	{
		$controllerPath .= '.' . strtolower($format);
	}
	$controllerPath .= '.php';
	// Set the name for the controller and instantiate it
	$controllerClass .= ucfirst($controllerName);
}

// If the controller file path exists, include it ... else lets die with a 500 error
if (file_exists($controllerPath))
{
	require_once ($controllerPath);
}
else
{
	throw new \Exception('Invalid Controller ' . $controllerName);
}

if (class_exists($controllerClass))
{
	$controller = new $controllerClass();
}
else
{
	throw new \Exception('Invalid Controller Class ' . $controllerClass);
}

// $config = JFactory::getConfig();

JLog::add(new JLogEntry("{$controllerClass}::execute({$task})", JLog::DEBUG, 'com_j2xml'));

// Perform the Request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();

/*
JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_j2xml'))
{
	throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = JControllerLegacy::getInstance('J2xml');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
*/