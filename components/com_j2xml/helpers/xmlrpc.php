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

use eshiol\J2xml\Messages;

jimport('eshiol.J2xml.Importer');
jimport('eshiol.J2xmlpro.Importer');
jimport('eshiol.J2xml.Messages');
jimport('eshiol.J2xml.Version');
jimport('eshiol.J2xmlpro.Version');

// Import JTableCategory
//JLoader::register('JTableCategory', JPATH_PLATFORM . '/joomla/database/table/category.php');
// Import JTableContent
//JLoader::register('JTableContent', JPATH_PLATFORM . '/joomla/database/table/content.php');

//require_once JPATH_ADMINISTRATOR . '/components/com_j2xml/helpers/j2xml.php';

/**
 * Joomla! J2XML XML-RPC Plugin
 *
 * @since 1.5.3
 */
class XMLRPCJ2XMLServices
{

	/**
	 * The service message queue.
	 *
	 * @var array
	 * @since 3.1.107
	 */
	protected static $_messageQueue = array();

	/**
	 * Import articles from xml file
	 *
	 * @param base64 $xml
	 * @param string $username
	 *			Username
	 * @param string $password
	 *			Password
	 * @return string
	 * @since 1.5
	 */
	public static function import($xml, $username = '', $password = '')
	{
		$lang = JFactory::getApplication()->getLanguage();
		$lang->load('lib_j2xml', JPATH_SITE, null, false, false) ||
		// Fallback to the library file in the default language
		$lang->load('lib_j2xml', JPATH_SITE, null, true);

		$params = JComponentHelper::getParams('com_j2xml');
		if ((int) $params->get('xmlrpc', 0) == 0)
		{
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_XMLRPC_DISABLED'), JLog::ERROR, 'com_j2xml'));
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}

		$app = JFactory::getApplication();
		$options = array();
		$response = $app->login(array(
			'username' => $username,
			'password' => $password
		), $options);
		if (true !== $response)
		{
			JLog::add(new JLogEntry(JText::_('JGLOBAL_AUTH_NO_USER'), JLog::ERROR, 'com_j2xml'));
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}

		$cparams = JComponentHelper::getParams('com_j2xml');
		$params = new JRegistry();
		$params->set('categories', $cparams->get('categories', 1));
		$params->set('contacts', $cparams->get('contacts', 1));
		$params->set('content', $cparams->get('content'));
		$params->set('fields', $cparams->get('fields', 1));
		$params->set('images', $cparams->get('images', 1));
		if ($cparams->get('keep_category', 1) == 2)
		{
			$params->set('content_category_forceto', $cparams->get('category'));
		}
		$params->set('keep_id', $cparams->get('keep_id', 0));
		$params->set('keep_user_id', $cparams->get('keep_user_id', 0));
		$params->set('tags', $cparams->get('tags', 1));
		$params->set('superusers', $cparams->get('superusers', 0));
		$params->set('usernotes', $cparams->get('usernotes', 0));
		$params->set('users', $cparams->get('users', 1));
		$params->set('viewlevels', $cparams->get('viewlevels', 1));
		$params->set('weblinks', $cparams->get('weblinks'));
		$params->set('keep_data', $cparams->get('keep_data'));

		$options = $params->toString();
		return self::importAjax($xml, $params->toString());
	}

	/**
	 * Import content from xml file
	 *
	 * @param string $xml
	 * @param string $options
	 *			json string
	 *
	 * @return string
	 * @since 3.9
	 */
	public static function importAjax($xml, $options)
	{
		$lang = JFactory::getApplication()->getLanguage();
		$lang->load('lib_j2xml', JPATH_SITE, null, false, false) ||
		// Fallback to the library file in the default language
		$lang->load('lib_j2xml', JPATH_SITE, null, true);

		$user = JFactory::getUser();
		if (!$user->authorise('core.admin', 'com_j2xml'))
		{
			if ($user->guest)
			{
				JFactory::getApplication()->setHeader('status', 401, true);
			}
			JLog::add(new JLogEntry(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), JLog::ERROR, 'com_j2xml'));
			// return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'), 28, JText::_('JGLOBAL_AUTH_ACCESS_DENIED'));
		}

		$data = self::gzdecode($xml);
		if (!$data)
		{
			$data = $xml;
		}

		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($data);
		if (!$xml)
		{
			$data = base64_decode($data);
			libxml_clear_errors();
		}

		$data = strstr($data, '<?xml version="1.0" ');

		if (!defined('LIBXML_PARSEHUGE'))
		{
			define(LIBXML_PARSEHUGE, 524288);
		}

		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);

		if (!$xml)
		{
			$errors = libxml_get_errors();
			foreach ($errors as $error)
			{
				$msg = $error->code . ' - ' . JText::_($error->message);
				switch ($error->level)
				{
					default:
					case LIBXML_ERR_WARNING:
						JLog::add(new JLogEntry(JText::_($msg), JLog::WARNING, 'com_j2xml'));
						return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
						break;
					case LIBXML_ERR_ERROR:
						JLog::add(new JLogEntry(JText::_($msg), JLog::ERROR, 'com_j2xml'));
						return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
						break;
					case LIBXML_ERR_FATAL:
						JLog::add(new JLogEntry(JText::_($msg), JLog::CRITICAL, 'com_j2xml'));
						return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
						break;
				}
			}
			libxml_clear_errors();
		}

		$params = new JRegistry($options);

		JPluginHelper::importPlugin('j2xml');
		$results = JFactory::getApplication()->triggerEvent('onContentBeforeImport', array('com_j2xml.xmlrpc', &$xml, $params));

		if (!isset($xml['version']))
		{
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLog::ERROR, 'com_j2xml'));
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}

		$xmlVersion = $xml['version'];
		$version = explode(".", $xmlVersion);
		$xmlVersionNumber = $version[0] . substr('0' . $version[1], strlen($version[1]) - 1) . substr('0' . $version[2], strlen($version[2]) - 1);

		$j2xmlVersion = class_exists('eshiol\J2xmlpro\Version') ? eshiol\J2xmlpro\Version::$DOCVERSION : eshiol\J2xml\Version::$DOCVERSION;
		$version = explode(".", $j2xmlVersion);
		$j2xmlVersionNumber = $version[0] . substr('0' . $version[1], strlen($version[1]) - 1) . substr('0' . $version[2], strlen($version[2]) - 1);

		if (($xmlVersionNumber == $j2xmlVersionNumber) || ($xmlVersionNumber == "150900") || ($xmlVersionNumber == "120500"))
		{
			// set_time_limit(120);
			$params->set('version', (string) $xml['version']);
			$params->set('logger', 'xmlrpc');

			$importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer() : new eshiol\J2xml\Importer();
			$importer->import($xml, $params);
		}
		else
		{
			JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion), JLog::ERROR, 'com_j2xml'));
		}

		//$app->logout();
		return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
	}

	static function gzdecode($data, &$filename = '', &$error = '', $maxlength = null)
	{
		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b"))
		{
			$error = "Not in GZIP format.";
			return null; // Not GZIP format (See RFC 1952)
		}
		$method = ord(substr($data, 2, 1)); // Compression method
		$flags = ord(substr($data, 3, 1)); // Flags
		if ($flags & 31 != $flags)
		{
			$error = "Reserved bits not allowed.";
			return null;
		}
		// NOTE: $mtime may be negative (PHP integer limitations)
		$mtime = unpack("V", substr($data, 4, 4));
		$mtime = $mtime[1];
		$xfl = substr($data, 8, 1);
		$os = substr($data, 8, 1);
		$headerlen = 10;
		$extralen = 0;
		$extra = "";
		if ($flags & 4)
		{
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8)
			{
				return false; // invalid
			}
			$extralen = unpack("v", substr($data, 8, 2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8)
			{
				return false; // invalid
			}
			$extra = substr($data, 10, $extralen);
			$headerlen += 2 + $extralen;
		}
		$filenamelen = 0;
		$filename = "";
		if ($flags & 8)
		{
			// C-style string
			if ($len - $headerlen - 1 < 8)
			{
				return false; // invalid
			}
			$filenamelen = strpos(substr($data, $headerlen), chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8)
			{
				return false; // invalid
			}
			$filename = substr($data, $headerlen, $filenamelen);
			$headerlen += $filenamelen + 1;
		}
		$commentlen = 0;
		$comment = "";
		if ($flags & 16)
		{
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8)
			{
				return false; // invalid
			}
			$commentlen = strpos(substr($data, $headerlen), chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8)
			{
				return false; // Invalid header format
			}
			$comment = substr($data, $headerlen, $commentlen);
			$headerlen += $commentlen + 1;
		}
		$headercrc = "";
		if ($flags & 2)
		{
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8)
			{
				return false; // invalid
			}
			$calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
			$headercrc = unpack("v", substr($data, $headerlen, 2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc)
			{
				$error = "Header checksum failed.";
				return false; // Bad header CRC
			}
			$headerlen += 2;
		}
		// GZIP FOOTER
		$datacrc = unpack("V", substr($data, - 8, 4));
		$datacrc = sprintf('%u', $datacrc[1] & 0xFFFFFFFF);
		$isize = unpack("V", substr($data, - 4));
		$isize = $isize[1];
		// decompression:
		$bodylen = $len - $headerlen - 8;
		if ($bodylen < 1)
		{
			// IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data, $headerlen, $bodylen);
		$data = "";
		if ($bodylen > 0)
		{
			switch ($method)
			{
				case 8:
					// Currently the only supported compression method:
					$data = gzinflate($body, $maxlength);
					break;
				default:
					$error = "Unknown compression method.";
					return false;
			}
		} // zero-byte body content is allowed
		// Verifiy CRC32
		$crc = sprintf("%u", crc32($data));
		$crcOK = $crc == $datacrc;
		$lenOK = $isize == strlen($data);
		if (!$lenOK || !$crcOK)
		{
			$error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
			return false;
		}
		return $data;
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param string $message
	 *			The message to log.
	 * @param string $priority
	 *			Message priority based on {$this->priorities}.
	 *
	 * @return void
	 *
	 * @since 3.1.107
	 */
	public static function enqueueMessage($message, $priority)
	{
		$codes = array(
			'error' => 28,
			'warning' => 29,
			'notice' => 30,
			'message' => 31
		);

		$message = htmlentities($message);
		$found   = false;
		$msgs    = array();

		foreach (Messages::$messages as $i => $m)
		{
			if ($message == JText::_($m))
			{
				self::$_messageQueue[] = new xmlrpcval(array(
					"code" => new xmlrpcval($i, 'int'),
					"string" => new xmlrpcval($message, 'string'),
					"message" => new xmlrpcval($message, 'string')
				), "struct");
				$found = true;
				break;
			}
			else
			{
				$pattern = '/' . str_replace(array('(', ')', '[', ']', '.'), array('\(', '\)', '\[', '\]', '\.'), JText::_($m)) . '/i';
				$pattern = preg_replace('/%(?:\d+\$)?[+-]?(?:[ 0]|\'.{1})?-?\d*(?:\.\d+)?[bcdeEufFgGosxX]/', '(.+)', $pattern);

				if (preg_match($pattern, $message, $matches))
				{
					array_shift($matches);

					preg_match_all($pattern, JText::_($m), $expected);
					array_shift($expected);

					$j = 1;
					foreach($expected as $index => $value)
					{
						$expected[$index] = trim(strstr(strstr($value[0], '%'), '$', true), '%$') ?: $j++;
					}
					$expected = array_flip($expected);
					ksort($expected);

					$strings = array();
					foreach ($expected as $index => $value)
					{
						$strings[] = new xmlrpcval($matches[$value], 'string');
					}
					self::$_messageQueue[] = new xmlrpcval(array(
						"code" => new xmlrpcval($i, 'int'),
//						"string" => new xmlrpcval($matches[1], 'string'),
						"strings" => new xmlrpcval($strings, 'array'),
						"message" => new xmlrpcval($message, 'string')
					), "struct");
					$found = true;
					break;
				}
			}
		}
		if (!$found)
		{
			self::$_messageQueue[] = new xmlrpcval(array(
				"code" => new xmlrpcval(isset($codes[$priority]) ? $codes[$priority] : 28, 'int'),
				"string" => new xmlrpcval($message, 'string'),
				"message" => new xmlrpcval($message, 'string')
			), "struct");
		}
	}
}