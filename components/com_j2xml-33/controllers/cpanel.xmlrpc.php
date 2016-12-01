<?php
/**
 * @version		3.1.108 components/com_j2xml/controllers/cpanel.xmlrpc.php
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		2.5
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
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
jimport('eshiol.core.xmlrpc');
jimport('eshiol.core.xmlrpcs');
require_once(JPATH_COMPONENT.'/helpers/xmlrpc.php');

class J2XMLControllerCpanel extends JControllerLegacy
{
	public function import()
	{
		global $xmlrpcString, $xmlrpcBase64, $xmlrpc_internalencoding;
/*
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('element'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('type') . ' = ' . $db->quote('library'));
		$query->where($db->quoteName('element') . ' = ' . $db->quote('xmlrpc'));
		$query->where($db->quoteName('enabled') . ' = 1');
		$db->setQuery($query);
		
		if ($db->loadResult() == null)
		{
			echo '<?xml version="1.0"?>
				<methodResponse><params><param><value><array><data><value><struct>
				<member><name>code</name><value><int>15</int></value></member>
				<member><name>string</name><value><string>XMLRC not supported</string></value></member>
				</struct></value></data></array></value></param></params></methodResponse>';
			return;	
		}
*/				
		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_j2xml');

		$xmlrpcServer = new xmlrpc_server(
			array(
				'j2xml.import' => array(
					'function' => 'plgXMLRPCJ2XMLServices::import',
					'docstring' => 'Import articles from xml file',
					'signature' => array(
						array($xmlrpcString, $xmlrpcBase64, $xmlrpcString, $xmlrpcString)
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
