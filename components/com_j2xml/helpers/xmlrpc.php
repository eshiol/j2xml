<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
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

// no direct access
defined('_JEXEC') or die('Restricted access.');

use eshiol\J2XML\Messages;

jimport('eshiol.j2xml.Importer');
jimport('eshiol.j2xmlpro.Importer');
jimport('eshiol.j2xml.Messages');
jimport('eshiol.j2xml.Version');
jimport('eshiol.j2xmlpro.Version');

// Import JTableCategory
JLoader::register('JTableCategory', JPATH_PLATFORM . '/joomla/database/table/category.php');
// Import JTableContent
JLoader::register('JTableContent', JPATH_PLATFORM . '/joomla/database/table/content.php');

require_once JPATH_ADMINISTRATOR . '/components/com_j2xml/helpers/j2xml.php';

/**
 * Joomla! J2XML XML-RPC Plugin
 *
 * @version 3.7.195
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
	 *        	Username
	 * @param string $password
	 *        	Password
	 * @return string
	 * @since 1.5
	 */
	public static function import($xml, $username = '', $password = '')
	{
		global $xmlrpcerruser, $xmlrpcI4, $xmlrpcInt, $xmlrpcBoolean, $xmlrpcDouble, $xmlrpcString, $xmlrpcDateTime, $xmlrpcBase64, $xmlrpcArray, $xmlrpcStruct, $xmlrpcValue;
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));
		
		$lang = JFactory::getLanguage();
		$lang->load('lib_j2xml', JPATH_SITE, null, false, false) || 
		// Fallback to the library file in the default language
		$lang->load('lib_j2xml', JPATH_SITE, null, true);
		
		$params = JComponentHelper::getParams('com_j2xml');
		if ((int) $params->get('xmlrpc', 0) == 0) {
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_XMLRPC_DISABLED'), JLog::ERROR, 'com_j2xml'));
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}
		
		$app = JFactory::getApplication();
		$options = array();
		$response = $app->login(array(
			'username' => $username,
			'password' => $password
		), $options);
		if (true !== $response) {
			JLog::add(new JLogEntry(JText::_('JGLOBAL_AUTH_NO_USER'), JLog::DEBUG, 'com_j2xml'));
			JLog::add(new JLogEntry(JText::_('JGLOBAL_AUTH_NO_USER'), JLog::ERROR, 'com_j2xml'));
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}
		
		$canDo = J2XMLHelper::getActions();
		if (! $canDo->get('core.create') && ! $canDo->get('core.edit') && ! $canDo->get('core.edit.own')) {
			JLog::add(new JLogEntry(JText::_('JLIB_LOGIN_DENIED'), JLog::DEBUG, 'com_j2xml'));
			JLog::add(new JLogEntry(JText::_('JLIB_LOGIN_DENIED'), JLog::ERROR, 'com_j2xml'));
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}
		
		$data = self::gzdecode($xml);
		if (! $data)
			$data = $xml;
		
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($data);
		if (! $xml) {
			$data = base64_decode($data);
			libxml_clear_errors();
		}
		
		if (! mb_detect_encoding($data, 'UTF-8')) {
			$data = mb_convert_encoding($data, 'UTF-8');
		}
		
		$data = strstr($data, '<?xml version="1.0" ');
		
		$data = J2XMLHelper::stripInvalidXml($data);
		if (! defined('LIBXML_PARSEHUGE'))
			define(LIBXML_PARSEHUGE, 524288);
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);
		
		if (! $xml) {
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$msg = $error->code . ' - ' . JText::_($error->message);
				switch ($error->level) {
					default:
					case LIBXML_ERR_WARNING:
						JLog::add(new JLogEntry(JText::_($msg), JLog::DEBUG, 'com_j2xml'));
						JLog::add(new JLogEntry(JText::_($msg), JLog::WARNING, 'com_j2xml'));
						return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
						break;
					case LIBXML_ERR_ERROR:
						JLog::add(new JLogEntry(JText::_($msg), JLog::DEBUG, 'com_j2xml'));
						JLog::add(new JLogEntry(JText::_($msg), JLog::ERROR, 'com_j2xml'));
						return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
						break;
					case LIBXML_ERR_FATAL:
						JLog::add(new JLogEntry(JText::_($msg), JLog::DEBUG, 'com_j2xml'));
						JLog::add(new JLogEntry(JText::_($msg), JLog::CRITICAL, 'com_j2xml'));
						return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
						break;
				}
			}
			libxml_clear_errors();
		}
		
		$dispatcher = \JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');
		
		JLog::add(new JLogEntry('onBeforeImport', JLog::DEBUG, 'com_j2xml'));
		$results = $dispatcher->trigger('onBeforeImport', array(
			'com_j2xml.xmlrpc',
			&$xml
		));
		
		if (! isset($xml['version'])) {
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLog::DEBUG, 'com_j2xml'));
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
			$params = JComponentHelper::getParams('com_j2xml');
			
			$iparams = new \JRegistry();
			$iparams->set('version', (string) $xml['version']);
			$iparams->set('categories', $params->get('import_categories', 1));
			$iparams->set('contacts', $params->get('import_contacts', 1));
			$iparams->set('fields', $params->get('import_fields', 1));
			$iparams->set('images', $params->get('import_images', 1));
			$iparams->set('keep_id', $params->get('keep_id', 0));
			$iparams->set('tags', $params->get('import_tags', 1));
			$iparams->set('users', $params->get('import_users', 1));
			$iparams->set('superusers', $params->get('import_superusers', 0));
			$iparams->set('usernotes', $params->get('import_usernotes', 1));
			$iparams->set('viewlevels', $params->get('import_viewlevels', 1));
			$iparams->set('content', $params->get('import_content'));
			$iparams->set('logger', 'xmlrpc');
			
			if ($params->get('keep_category', 1) == 2) {
				$iparams->set('content_category_forceto', $params->get('category'));
			}
			
			$importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer() : new eshiol\J2xml\Importer();
			$importer->import($xml, $iparams);
		} else {
			JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion), JLog::ERROR, 'com_j2xml'));
		}
		
		$app->logout();
		return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
	}

	static function gzdecode($data, &$filename = '', &$error = '', $maxlength = null)
	{
		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
			$error = "Not in GZIP format.";
			return null; // Not GZIP format (See RFC 1952)
		}
		$method = ord(substr($data, 2, 1)); // Compression method
		$flags = ord(substr($data, 3, 1)); // Flags
		if ($flags & 31 != $flags) {
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
		if ($flags & 4) {
			// 2-byte length prefixed EXTRA data in header
			if ($len - $headerlen - 2 < 8) {
				return false; // invalid
			}
			$extralen = unpack("v", substr($data, 8, 2));
			$extralen = $extralen[1];
			if ($len - $headerlen - 2 - $extralen < 8) {
				return false; // invalid
			}
			$extra = substr($data, 10, $extralen);
			$headerlen += 2 + $extralen;
		}
		$filenamelen = 0;
		$filename = "";
		if ($flags & 8) {
			// C-style string
			if ($len - $headerlen - 1 < 8) {
				return false; // invalid
			}
			$filenamelen = strpos(substr($data, $headerlen), chr(0));
			if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
				return false; // invalid
			}
			$filename = substr($data, $headerlen, $filenamelen);
			$headerlen += $filenamelen + 1;
		}
		$commentlen = 0;
		$comment = "";
		if ($flags & 16) {
			// C-style string COMMENT data in header
			if ($len - $headerlen - 1 < 8) {
				return false; // invalid
			}
			$commentlen = strpos(substr($data, $headerlen), chr(0));
			if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
				return false; // Invalid header format
			}
			$comment = substr($data, $headerlen, $commentlen);
			$headerlen += $commentlen + 1;
		}
		$headercrc = "";
		if ($flags & 2) {
			// 2-bytes (lowest order) of CRC32 on header present
			if ($len - $headerlen - 2 < 8) {
				return false; // invalid
			}
			$calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
			$headercrc = unpack("v", substr($data, $headerlen, 2));
			$headercrc = $headercrc[1];
			if ($headercrc != $calccrc) {
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
		if ($bodylen < 1) {
			// IMPLEMENTATION BUG!
			return null;
		}
		$body = substr($data, $headerlen, $bodylen);
		$data = "";
		if ($bodylen > 0) {
			switch ($method) {
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
		if (! $lenOK || ! $crcOK) {
			$error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
			return false;
		}
		return $data;
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param string $message
	 *        	The message to log.
	 * @param string $priority
	 *        	Message priority based on {$this->priorities}.
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
		
		$found = false;
		$msgs = array();
		
		foreach (Messages::$messages as $i => $m) {
			if ($message == JText::_($m)) {
				self::$_messageQueue[] = new xmlrpcval(array(
					"code" => new xmlrpcval($i, 'int'),
					"string" => new xmlrpcval($message, 'string'),
					"message" => new xmlrpcval($message, 'string')
				), "struct");
				$found = true;
				break;
			} else {
				$pattern = '/' . str_replace(array(
					'(',
					')',
					'[',
					']',
					'.',
					'%s'
				), array(
					'\(',
					'\)',
					'\[',
					'\]',
					'\.',
					'(.+)'
				), JText::_($m)) . '/i';
				if (preg_match($pattern, $message, $matches)) {
					self::$_messageQueue[] = new xmlrpcval(array(
						"code" => new xmlrpcval($i, 'int'),
						"string" => new xmlrpcval($matches[1], 'string'),
						"message" => new xmlrpcval($message, 'string')
					), "struct");
					$found = true;
					break;
				}
			}
		}
		if (! $found)
			self::$_messageQueue[] = new xmlrpcval(array(
				"code" => new xmlrpcval(isset($codes[$priority]) ? $codes[$priority] : 28, 'int'),
				"string" => new xmlrpcval($message, 'string'),
				"message" => new xmlrpcval($message, 'string')
			), "struct");
	}
}