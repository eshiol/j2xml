<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  eshiol.J2XML
 *
 * @version     __DEPLOY_VERSION__
 * @since       1.5.3beta3.38
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
namespace eshiol\J2xml;

// no direct access
defined('_JEXEC') or die('Restricted access.');

use eshiol\J2xml\Messages;
use eshiol\J2xml\Version;
\JLoader::import('joomla.log.log');
\JLoader::import('eshiol.J2xml.Messages');
\JLoader::import('eshiol.J2xml.Version');

if (file_exists('../phpxmlrpc/lib/xmlrpc.inc'))
{
	include_once JPATH_LIBRARIES . '/eshiol/phpxmlrpc/lib/xmlrpc.inc';
	include_once JPATH_LIBRARIES . '/eshiol/phpxmlrpc/lib/xmlrpcs.inc';
}

/**
 *
 * Sender
 *
 */
class Sender
{

	public static $codes = array(
		'-1' => 'message',
		'message', // LIB_J2XML_MSG_ARTICLE_IMPORTED
		'notice', // LIB_J2XML_MSG_ARTICLE_NOT_IMPORTED
		'message', // LIB_J2XML_MSG_USER_IMPORTED
		'notice', // LIB_J2XML_MSG_USER_NOT_IMPORTED
		'notice', // 'message', // not used: LIB_J2XML_MSG_SECTION_IMPORTED
		'notice', // not used: LIB_J2XML_MSG_SECTION_NOT_IMPORTED
		6 => 'message', // LIB_J2XML_MSG_CATEGORY_IMPORTED
		'notice', // LIB_J2XML_MSG_CATEGORY_NOT_IMPORTED
		'message', // LIB_J2XML_MSG_FOLDER_WAS_SUCCESSFULLY_CREATED
		'notice', // LIB_J2XML_MSG_ERROR_CREATING_FOLDER
		'message', // LIB_J2XML_MSG_IMAGE_IMPORTED
		'notice', // LIB_J2XML_MSG_IMAGE_NOT_IMPORTED
		'message', // LIB_J2XML_MSG_WEBLINK_IMPORTED
		'notice', // LIB_J2XML_MSG_WEBLINK_NOT_IMPORTED
		'notice', // not used: LIB_J2XML_MSG_WEBLINKCAT_NOT_PRESENT
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
		'notice', // LIB_J2XML_MSG_XMLRPC_DISABLED 32
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
		'notice', // LIB_J2XML_MSG_USERNOTE_NOT_IMPORTED 43
		'message', // LIB_J2XML_MSG_FIELDGROUP_IMPORTED 44
		'notice', // LIB_J2XML_MSG_FIELDGROUP_NOT_IMPORTED 45
		'notice' // LIB_J2XML_MSG_USER_SKIPPED 46
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
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

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
		{
			return;
		}

		$str = $server['remote_url'];

		if (strpos($str, "://") === false)
		{
			$server['remote_url'] = "http://" . $server['remote_url'];
		}

		if ($str[strlen($str) - 1] != '/')
		{
			$server['remote_url'] .= '/';
		}
		$server['remote_url'] .= 'index.php?option=com_j2xml&task=services.import&format=xmlrpc';

		$headers = false;
		if (! function_exists('xmlrpc_set_type'))
		{
			$headers = false;
			// $app->enqueueMessage(\JText::_('LIB_J2XML_MSG_XMLRPC_ERROR'),
			// 'error');
			// return;
		}
		else
		{
			$objData = $data;
			xmlrpc_set_type($objData, 'base64');
			$request = xmlrpc_encode_request('j2xml.import', array(
				$objData,
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

			$headers = @get_headers($server['remote_url']);
		}

		if ($headers === false)
		{
			$res = self::_xmlrpc_j2xml_send($server['remote_url'], $data, $server['username'], $server['password'], $options['debug']);
			if ($res->faultcode())
			{
				$app->enqueueMessage($server['title'] . ': ' . \JText::_($res->faultString()), 'error');
			}
			else
			{
				$msgs = $res->value();
				$len = $msgs->arraysize();
				for ($i = 0; $i < $len; $i ++)
				{
					$msg = $msgs->arraymem($i);
					$code = $msg->structmem('code')->scalarval();
					//$string = $msg->structmem('string')->scalarval();
					$matches = $msg->structmem('strings');

					if (! isset(Messages::$messages[$code]))
					{
						$app->enqueueMessage($server['title'] . ': ' . $msg->structmem('message')
							->scalarval(), 'notice');
					}
					elseif ($matches)
					{
						$strings = array();
						foreach ($matches->scalarval() as $string) {
							$strings[] = $string->scalarval();
						}
						//$app->enqueueMessage($server['title'] . ': ' . \JText::sprintf(Messages::$messages[$code], $string), self::$codes[$code]);
						$app->enqueueMessage($server['title'] . ': ' . vsprintf(\JText::_(Messages::$messages[$code]), $strings), self::$codes[$code]);
					}
					else
					{
						$app->enqueueMessage($server['title'] . ': ' . $msg->structmem('message')
							->scalarval(), self::$codes[$code]);
					}
				}
			}
		}
		else
		{
			if (substr($headers[0], 9, 3) == '301')
			{
				foreach ($headers as $header)
				{
					if (substr($header, 0, 10) == 'Location: ')
					{
						$server['remote_url'] = substr($header, 10);
						$headers = @get_headers($server['remote_url']);
						break;
					}
				}
			}
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

					if ($response && xmlrpc_is_fault($response))
					{
						$app->enqueueMessage($server['title'] . ': ' . \JText::_($response['faultString']), 'error');
					}
					elseif (is_array($response))
					{
						foreach ($response as $msg)
						{
							if (! isset(Messages::$messages[$msg['code']]))
							{
								$app->enqueueMessage($server['title'] . ': ' . $msg['message'], 'notice');
							}
							elseif (isset($msg['strings']))
							{
								// $app->enqueueMessage($server['title'] . ': ' . \JText::sprintf(Messages::$messages[$msg['code']], $msg['string']), self::$codes[$msg['code']]);
								$app->enqueueMessage($server['title'] . ': ' . vsprintf(\JText::_(Messages::$messages[$msg['code']]), $msg['strings']), self::$codes[$msg['code']]);
							}
							else
							{
								$app->enqueueMessage($server['title'] . ': ' . $msg['message'], self::$codes[$msg['code']]);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Send xml data to
	 *
	 * @param string $remote_url
	 * @param string $xml
	 * @param string $username
	 * @param string $password
	 * @param int $debug
	 *			when 1 (or 2) will enable debugging of the underlying xmlrpc
	 *			call (defaults to 0)
	 * @return xmlrpcresp obj instance
	 */
	private static function _xmlrpc_j2xml_send ($remote_url, $xml, $username, $password, $debug = 0)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$debug = 0;
		$protocol = '';
		$GLOBALS['xmlrpc_internalencoding'] = 'UTF-8';
		$client = new \xmlrpc_client($remote_url);
		$client->return_type = 'xmlrpcvals';
		$client->request_charset_encoding = 'UTF-8';
		$client->user_agent = Version::$PRODUCT . ' ' . Version::getFullVersion();
		if (\JFactory::getApplication()->get('gzip') && !ini_get('zlib.output_compression') && ini_get('output_handler') !== 'ob_gzhandler')
		{
			// default values
			$client->accepted_compression = array('gzip', 'deflate');
		}
		else
		{
			$client->accepted_compression = array('deflate');
		}
		$client->setDebug($debug);
		$msg = new \xmlrpcmsg('j2xml.import');
		$p1 = new \xmlrpcval(base64_encode($xml), 'base64');
		$msg->addparam($p1);
		$p2 = new \xmlrpcval($username, 'string');
		$msg->addparam($p2);
		$p3 = new \xmlrpcval($password, 'string');
		$msg->addparam($p3);

		$res = $client->send($msg, 0);

		if (! $res->faultcode())
		{
			return $res;
		}

		if ($res->faultString() == "Didn't receive 200 OK from remote server. (HTTP/1.1 301 Foun)")
		{
			$res = $client->send($msg, 0, $protocol = 'http11');
			if (! $res->faultcode())
			{
				return $res;
			}
		}
		if ($res->faultString() == "Didn't receive 200 OK from remote server. (HTTP/1.1 303 See other)")
		{
			$headers = http_parse_headers($res->raw_data);
			$url = $headers['Location'];
			$parse = parse_url($url);
			if (! isset($parse['host']))
			{
				$parse = parse_url($remote_url);
				$url = $parse['scheme'] . '://' . $parse['host'] . $url;
			}
			$client = new \xmlrpc_client($url);
			$client->return_type = 'xmlrpcvals';
			$client->request_charset_encoding = 'UTF-8';
			$client->user_agent = Version::$PRODUCT . ' ' . Version::getFullVersion();
			$client->setDebug($debug);
			$res = $client->send($msg, 0, $protocol);
		}
		return $res;
	}
}

if (! function_exists('http_parse_headers'))
{

	function http_parse_headers ($raw_headers)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'com_j2xml'));

		$headers = array();
		$key = '';

		foreach (explode("\n", $raw_headers) as $i => $h)
		{
			$h = explode(':', $h, 2);

			if (isset($h[1]))
			{
				if (! isset($headers[$h[0]]))
					$headers[$h[0]] = trim($h[1]);
				elseif (is_array($headers[$h[0]]))
				{
					$headers[$h[0]] = array_merge($headers[$h[0]], array(
							trim($h[1])
					));
				}
				else
				{
					$headers[$h[0]] = array_merge(array(
							$headers[$h[0]]
					), array(
							trim($h[1])
					)); // [+]
				}
				$key = $h[0];
			}
			else
			{
				if (substr($h[0], 0, 1) == "\t")
					$headers[$key] .= "\r\n\t" . trim($h[0]);
				elseif (! $key)
					$headers[0] = trim($h[0]);
				trim($h[0]);
			}
		}

		return $headers;
	}
}
?>