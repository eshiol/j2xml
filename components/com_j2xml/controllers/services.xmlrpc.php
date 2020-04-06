<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2020 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

require_once JPATH_SITE . '/components/com_j2xml/helpers/xmlrpc.php';

/**
 *
 * @version __DEPLOY_VERSION__
 * @since 2.5
 */
class J2XMLControllerServices extends JControllerLegacy
{

	public function import ()
	{
		global $xmlrpcString, $xmlrpcBase64, $xmlrpc_internalencoding;

		// define UTF-8 as the internal encoding for the XML-RPC server
		$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';

		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_j2xml');

		$xmlrpcServer = new xmlrpc_server(
				array(
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
						)
				), false);
		// allow casting to be defined by that actual values passed
		$xmlrpcServer->functions_parameters_type = 'phpvals';
		// define UTF-8 as the internal encoding for the XML-RPC server
		$xmlrpcServer->response_charset_encoding = 'UTF-8';
		// debug level
		$xmlrpcServer->setDebug($params->get('debug'));
		// set compression
		$app = JFactory::getApplication();
		if ($app->get('gzip') && !ini_get('zlib.output_compression') && ini_get('output_handler') !== 'ob_gzhandler')
		{
			// default values
			// $xmlrpcServer->accepted_compression = array('gzip', 'deflate');
			// $xmlrpcServer->compress_response = true;
			$app->set('gzip', false);
		}
		else
		{
			$xmlrpcServer->accepted_compression = array('deflate');
			$xmlrpcServer->compress_response = false;
		}
		// start the service
		$xmlrpcServer->service();
	}
}
?>