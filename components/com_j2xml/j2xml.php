<?php
/**
 * @version		3.1.113 /components/com_j2xml/j2xml.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.7.0.64
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2013 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

// Merge the default translation with the current translation
$lang = JFactory::getLanguage();

$lang->load('com_j2xml', JPATH_SITE, 'en-GB', true);
$lang->load('com_j2xml', JPATH_SITE, $lang->getDefault(), true);
$lang->load('com_j2xml', JPATH_SITE, null, true);

$lang->load('lib_j2xml', JPATH_SITE, null, false, false)
|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, false, false)
// Fallback to the lib_j2xml file in the default language
|| $lang->load('lib_j2xml', JPATH_SITE, null, true)
|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, true);

$controllerClass = 'J2XMLController';
$task = JRequest::getCmd('task', 'cpanel');

if (strpos($task, '.') === false) 
	$controllerPath	= JPATH_COMPONENT.'/controller.php';
else
{
	// We have a defined controller/task pair -- lets split them out
	list($controllerName, $task) = explode('.', $task);

	// Define the controller name and path
	$controllerName	= strtolower($controllerName);

	$controllerPath	= JPATH_COMPONENT.'/controllers/'.$controllerName;	
	
	$format = JRequest::getCmd('format');
	if ($format == 'xmlrpc')
		$controllerPath .= '.'.strtolower($format);
	$controllerPath	.= '.php';
	// Set the name for the controller and instantiate it
	$controllerClass .= ucfirst($controllerName);
}

// If the controller file path exists, include it ... else lets die with a 500 error
if (file_exists($controllerPath)) {
	require_once($controllerPath);
} else {
	JError::raiseError(500, 'Invalid Controller '.$controllerName);
}

if (class_exists($controllerClass)) {
	$controller = new $controllerClass();
} else {
	JError::raiseError(500, 'Invalid Controller Class - '.$controllerClass );
}

//$config	= JFactory::getConfig();

// Perform the Request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();
