<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2023 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

require_once JPATH_LIBRARIES . '/eshiol/phpxmlrpc/lib/xmlrpc.inc';
require_once JPATH_LIBRARIES . '/eshiol/phpxmlrpc/lib/xmlrpcs.inc';

require_once JPATH_SITE . '/components/com_j2xml/helpers/xmlrpc.php';

/**
 *
 * @since 2.5
 */
class J2xmlControllerServices extends JControllerLegacy
{

	public function import()
	{
		global $xmlrpcString, $xmlrpcBase64, $xmlrpc_internalencoding;
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$params = JComponentHelper::getParams('com_j2xml');

		$jversion = new JVersion();
		if ($jversion->isCompatible('3.9'))
		{
			$lib_xmlrpc = 'eshiol/phpxmlrpc';
		} else {
			$lib_xmlrpc = 'phpxmlrpc';
		}

		if (!JLibraryHelper::isEnabled($lib_xmlrpc) || !$params->get('xmlrpc'))
		{
			echo '<?xml version="1.0"?><methodResponse><fault><value><struct><member><name>faultCode</name><value><int>32</int></value></member><member><name>faultString</name><value><string>' . JText::_('LIB_J2XML_MSG_XMLRPC_DISABLED') . '</string></value></member></struct></value></fault></methodResponse>';
			exit();
		}

		$xmlrpcServer = new xmlrpc_server(array(
			'j2xml.import' => array(
				'function' => 'XMLRPCJ2XMLServices::import',
				'docstring' => 'Import data from xml file',
				'signature' => array(
					array(
						$xmlrpcString,
						$xmlrpcBase64,
						$xmlrpcString,
						$xmlrpcString
					)
				)
			),
			'j2xml.importAjax' => array(
				'function' => 'XMLRPCJ2XMLServices::importAjax',
				'docstring' => 'Import data from xml file',
				'signature' => array(
					array(
						$xmlrpcString,
						$xmlrpcString,
						$xmlrpcString
					)
				)
			)
		), false);
		// allow casting to be defined by that actual values passed
		$xmlrpcServer->functions_parameters_type = 'phpvals';
		// define UTF-8 as the internal encoding for the XML-RPC server
		$xmlrpc_internalencoding = 'UTF-8';
		// debug level
		$xmlrpcServer->setDebug($params->get('debug', 2));
		// disable compression
		$xmlrpcServer->compress_response = false;
		// start the service
		$xmlrpcServer->service();
	}
}
