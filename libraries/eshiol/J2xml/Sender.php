<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
namespace eshiol\J2XML;

// no direct access
defined('_JEXEC') or die('Restricted access.');

use eshiol\J2XML\Messages;
use eshiol\J2XML\Version;
jimport('joomla.log.log');
jimport('eshiol.j2xml.Messages');
jimport('eshiol.j2xml.Version');

/**
 *
 * @version 19.2.322
 * @since 1.5.3beta3.38
 */
class Sender
{

	private static $codes = array(
			'-1' => 'message',
			'message', // LIB_J2XML_MSG_ARTICLE_IMPORTED
			'notice', // LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED
			'message', // LIB_J2XML_MSG_USER_IMPORTED
			'notice', // LIB_J2XML_MSG_USER_NOT_IMPORTED
			          // 'message', // LIB_J2XML_MSG_SECTION_IMPORTED
			          // 'notice', // LIB_J2XML_MSG_SECTION_NOT_IMPORTED
			6 => 'message', // LIB_J2XML_MSG_CATEGORY_IMPORTED
			'notice', // LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED
			'message', // LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED
			'notice', // LIB_J2XML_MSG_ERROR_CREATING_FOLDER
			'message', // LIB_J2XML_MSG_IMAGE_IMPORTED
			'notice', // LIB_J2XML_MSG_IMAGE_NOT_IMPORTED
			'message', // LIB_J2XML_MSG_WEBLINK_IMPORTED
			'notice', // LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED
			          // 'notice', // LIB_J2XML_MSG_WEBLINKCAT_NOT_PRESENT
			15 => 'error', // LIB_J2XML_MSG_XMLRPC_NOT_SUPPORTED
			'notice', // LIB_J2XML_MSG_CATEGORY_ID_PRESENT 16
			'error', // LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED 17
			'error', // LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN 18
			'error', // JERROR_ALERTNOTAUTH 19
			'message', // LIB_J2XML_MSG_TAG_IMPORTED 20
			'notice', // LIB_J2XML_MSG_TAG_NOT_IMPORTED 21
			'message', // LIB_J2XML_MSG_CONTACT_IMPORTED 22
			'notice', // LIB_J2XML_MSG_CONTACT_NOT_IMPORTED 23
			'message', // LIB_J2XML_MSG_VIEWLEVEL_IMPORTED 24
			'notice', // LIB_J2XML_MSG_VIEWLEVEL_NOT_IMPORTED 25
			'message', // LIB_J2XML_MSG_BUTTON_IMPORTED 26
			'notice', // LIB_J2XML_MSG_BUTTON_NOT_IMPORTED 27
			'error', // LIB_J2XML_MSG_UNKNOWN_ERROR 28
			'warning', // LIB_J2XML_MSG_UNKNOWN_WARNING 29
			'notice', // LIB_J2XML_MSG_UNKNOWN_NOTICE 30
			'message', // LIB_J2XML_MSG_UNKNOWN_MESSAGE 31
			32 => 'notice', // LIB_J2XML_MSG_XMLRPC_DISABLED 32
			'message', // LIB_J2XML_MSG_MENUTYPE_IMPORTED 33
			'notice', // LIB_J2XML_MSG_MENUTYPE_NOT_IMPORTED 34
			'message', // LIB_J2XML_MSG_MENU_IMPORTED 35
			'notice', // LIB_J2XML_MSG_MENU_NOT_IMPORTED 36
			'notice', // LIB_J2XML_ERROR_COMPONENT_NOT_FOUND 37
			'message', // LIB_J2XML_MSG_MODULE_IMPORTED 38
			'notice', // LIB_J2XML_MSG_MODULE_NOT_IMPORTED 39
			'message', // LIB_J2XML_MSG_FIELD_IMPORTED 40
			'notice', // LIB_J2XML_MSG_FIELD_NOT_IMPORTED 41
			'message', // LIB_J2XML_MSG_USERNOTE_IMPORTED 42
			'notice' // LIB_J2XML_MSG_USERNOTE_NOT_IMPORTED 43
	);

	/*
	 * Send data
	 * @param $xml data
	 * @param $options
	 * @param $sid remote server id
	 * @since 1.5.3beta3.38
	 */
	static function send ($xml, $options, $sid)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		\JLog::add(new \JLogEntry('xml: ' . $xml->asXML(), \JLog::DEBUG, 'lib_j2xml'));
		
		$app = \JFactory::getApplication();
		$version = explode(".", Version::$DOCVERSION);
		$xmlVersionNumber = $version[0] . $version[1] . substr('0' . $version[2], strlen($version[2]) - 1);
		
		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$data = $dom->saveXML();
		
		if ($options['gzip'])
		{
			$data = gzencode($data, 9);
		}
		
		$db = \JFactory::getDBO();
		$query = 'SELECT `title`, `remote_url`, `username`, `password` ' . 'FROM `#__j2xml_websites` WHERE `state`= 1 AND `id` = ' . $sid;
		$db->setQuery($query);
		if (! ($server = $db->loadAssoc()))
			return;
		
		$str = $server['remote_url'];
		
		if (strpos($str, "://") === false)
			$server['remote_url'] = "http://" . $server['remote_url'];
		
		if ($str[strlen($str) - 1] != '/')
			$server['remote_url'] .= '/';
		$server['remote_url'] .= 'index.php?option=com_j2xml&task=services.import&format=xmlrpc';
		
		if (! function_exists('xmlrpc_set_type'))
		{
			$app->enqueueMessage(\JText::_('LIB_J2XML_XMLRPC_ERROR'), 'error');
			return;
		}
		
		xmlrpc_set_type($data, 'base64');
		\JLog::add(
				new \JLogEntry(
						print_r(
								array(
										'http' => array(
												'method' => "POST",
												'url' => $server['remote_url'],
												'header' => "Content-Type: text/xml",
												'user_agent' => Version::$PRODUCT . ' ' . Version::getFullVersion(),
												'content' => xmlrpc_encode_request('j2xml.import',
														array(
																$data,
																$server['username'],
																'********'
														))
										)
								), true), \JLog::DEBUG, 'lib_j2xml'));
		$request = xmlrpc_encode_request('j2xml.import', array(
				$data,
				$server['username'],
				$server['password']
		));
		$context = stream_context_create(
				array(
						'http' => array(
								'method' => "POST",
								'header' => "Content-Type: text/xml",
								'user_agent' => Version::$PRODUCT . ' ' . Version::getFullVersion(),
								'content' => $request,
								'http' => array(
										'header' => 'Accept-Charset: UTF-8, *;q=0'
								)
						)
				));
		
		$headers = get_headers($server['remote_url']);
		\JLog::add(new \JLogEntry("GET " . $server['remote_url'] . "\n" . print_r($headers, true), \JLog::DEBUG, 'lib_j2xml'));
		if (substr($headers[0], 9, 3) != '200')
		{
			$app->enqueueMessage($server['title'] . ': ' . $headers[0], 'error');
		}
		else
		{
			$file = file_get_contents($server['remote_url'], false, $context);
			if ($file === false)
			{
				// Handle the error
			}
			else
			{
				$response = xmlrpc_decode($file);
				
				\JLog::add(new \JLogEntry(print_r($response, true), \JLog::DEBUG, 'lib_j2xml'));
				if ($response && xmlrpc_is_fault($response))
				{
					$app->enqueueMessage($server['title'] . ': ' . \JText::_($response['faultString']), 'error');
				}
				elseif (is_array($response))
				{
					foreach ($response as $msg)
					{
						if (isset(Messages::$messages[$msg['code']]))
							$app->enqueueMessage($server['title'] . ': ' . \JText::sprintf(Messages::$messages[$msg['code']], $msg['string']),
									self::$codes[$msg['code']]);
						elseif (isset(self::$codes[$msg['code']]))
							$app->enqueueMessage($server['title'] . ': ' . $msg['message'], self::$codes[$msg['code']]);
						else
							$app->enqueueMessage($server['title'] . ': ' . $msg['message'], 'notice');
					}
				}
			}
		}
	}
}
?>