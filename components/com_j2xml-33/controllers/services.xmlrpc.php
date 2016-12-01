<?php
/**
 * @version		3.3.143 components/com_j2xml/controllers/services.xmlrpc.php
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		3.3.143
 * 
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
jimport('eshiol.core.xmlrpc');
jimport('eshiol.core.xmlrpcs');
require_once(JPATH_COMPONENT.'/helpers/xmlrpc.php');

class J2XMLControllerServices extends JControllerLegacy
{
	public function __construct($config = array())
	{
		if (defined('JDEBUG') && JDEBUG)
			JLog::addLogger(array('text_file' => 'j2xml.php', 'extension' => 'com_j2xml'), JLog::ALL, array('lib_j2xml','com_j2xml'));
		JLog::addLogger(array('logger' => 'xmlrpc', 'extension' => 'com_j2xml'), JLOG::ALL & ~JLOG::DEBUG, array('lib_j2xml','com_j2xml'));
		parent::__construct($config);
	}
	
	public function display($cachable = false, $urlparams = Array())
	{
		JLog::add(new JLogEntry(__METHOD__,JLOG::DEBUG,'com_j2xml'));
		global $xmlrpcString, $xmlrpcBase64, $xmlrpc_internalencoding;

		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_j2xml');

		$xmlrpcServer = new xmlrpc_server(
			array(
				'j2xml.login' => array(
					'function' => 'plgXMLRPCJ2XMLServices::login',
					'docstring' => 'Login',
					'signature' => array(
						array($xmlrpcString, $xmlrpcString, $xmlrpcString)
					)
				),
				'j2xml.import' => array(
					'function' => 'plgXMLRPCJ2XMLServices::import',
					'docstring' => 'Import articles from xml file',
					'signature' => array(
						array($xmlrpcString, $xmlrpcString, $xmlrpcBase64)
					)
				)
			)
			, false);
		// allow casting to be defined by that actual values passed
		$xmlrpcServer->functions_parameters_type = 'phpvals';
		// define UTF-8 as the internal encoding for the XML-RPC server
		$xmlrpcServer->xml_header('UTF-8');
		$xmlrpc_internalencoding = 'UTF-8';
		// debug level
		$xmlrpcServer->setDebug($params->get('debug'));
		// start the service
		$xmlrpcServer->service();
	}

}
?>
