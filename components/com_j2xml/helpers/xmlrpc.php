<?php
/**
 * @version		3.3.143 components/com_j2xml/helpers/xmlrpc.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2011-2015 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

 
// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('eshiol.j2xml.importer');
jimport('eshiol.j2xml.messages');

// Import JTableCategory
JLoader::register('JTableCategory', JPATH_PLATFORM . '/joomla/database/table/category.php');
// Import JTableContent
JLoader::register('JTableContent', JPATH_PLATFORM . '/joomla/database/table/content.php');

require_once(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/j2xml.php');
require_once(JPATH_COMPONENT.'/helpers/log.php');

// Merge the default translation with the current translation
$jlang = JFactory::getLanguage();
// Back-end translation
$jlang->load('com_j2xml', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_j2xml', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_j2xml', JPATH_ADMINISTRATOR, null, true);

/**
 * Joomla! J2XML XML-RPC Plugin
 *
 * @package J2XML
 * @since 1.5
 */
class plgXMLRPCJ2XMLServices
{
	/**
	 * The service message queue.
	 *
	 * @var    array
	 * @since  3.1.107
	 */
	protected static $_messageQueue = array();
	
	/**
	 * Import articles from xml file
	 *
	 * @param base64 $xml
	 * @param string $username Username
	 * @param string $password Password
	 * @return string
	 * @since 1.5
	 */
	public static function import($sessionid, $xml)
	{
		JLog::add(new JLogEntry(__METHOD__,JLOG::DEBUG,'lib_j2xml'));
		JLog::add(new JLogEntry($sessionid,JLOG::DEBUG,'lib_j2xml'));
		//JLog::add(new JLogEntry($xml,JLOG::DEBUG,'lib_j2xml'));
		global $xmlrpcerruser, $xmlrpcI4, $xmlrpcInt, $xmlrpcBoolean, $xmlrpcDouble, $xmlrpcString, $xmlrpcDateTime, $xmlrpcBase64, $xmlrpcArray, $xmlrpcStruct, $xmlrpcValue;
				
		$app = JFactory::getApplication();
		$options = array();
		
		/*
		$cookieName = 'fa73a482f1c240dc4319b2fa73a8a9ee';
		JLog::add(new JLogEntry($cookieName,JLOG::DEBUG,'lib_j2xml'));
		
		JLog::add(new JLogEntry(
		get_class (JFactory::getApplication()->input->cookie)
				,JLOG::DEBUG,'lib_j2xml'));
		$sessionid = JFactory::getApplication()->input->cookie->get($cookieName);
		JLog::add(new JLogEntry($sessionid,JLOG::DEBUG,'lib_j2xml'));
		// Check for the cookie
		if ($sessionid)
		{
			JFactory::getApplication()->login(array('username' => ''), array('silent' => true));
		}
		*/
		/*
		$id = JFactory::getSession()->getId();
		JLog::add(new JLogEntry($id,JLOG::DEBUG,'com_j2xml'));
		// TODO: use JSession
		//JSession::getInstance('database', array('id'=>$sessionid));
		 */
		session_write_close();             // End the previously-started session
		//$jd = new JSessionStorageDatabase();
		session_id($sessionid);   // Set the new session ID
		session_start();                   // Start it
/*
		if (!JSession::checkToken('get'))
		{
			JLog::add(new JLogEntry(JText::_('COM_J2XML_MSG_TOKEN_ERROR')),JLOG::ERROR,'lib_j2xml');
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}
		
			JLog::add(new JLogEntry(JText::_('COM_J2XML_MSG_OK')),JLOG::ERROR,'lib_j2xml');
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
*/		
		$canDo	= J2XMLHelper::getActions();
		if (!$canDo->get('core.create') &&
				!$canDo->get('core.edit') &&
				!$canDo->get('core.edit.own')) {
			JLog::add(new JLogEntry(JText::_('COM_J2XML_MSG_ALERTNOTAUTH')),JLOG::ERROR,'lib_j2xml');
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}

		//$xml = base64_decode($xml);

		$data = self::gzdecode($xml);
		if (!$data)
			$data = $xml;
		$data = trim($data);

		$xml = simplexml_load_string($data);
		
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');
		$results = $dispatcher->trigger('onBeforeImport', array('com_j2xml.cpanel', &$xml));
		
		if (!$xml)
		{
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$msg = $error->code.' - '.JText::_($error->message);
			    switch ($error->level) {
		    	default:
		        case LIBXML_ERR_WARNING:
					JLog::add(new JLogEntry(JText::_($msg)),JLog::WARNING,'lib_j2xml');
					return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		            break;
		         case LIBXML_ERR_ERROR:
					JLog::add(new JLogEntry(JText::_($msg)),JLOG::ERROR,'lib_j2xml');
					return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		         	break;
		        case LIBXML_ERR_FATAL:
					JLog::add(new JLogEntry(JText::_($msg)),JLOG::CRITICAL,'lib_j2xml');
					return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		        	break;
			    }
			}
			libxml_clear_errors();
		}
		
		if(!isset($xml['version']))
		{
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN')),JLOG::ERROR,'lib_j2xml');
			return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		}

		jimport('eshiol.j2xml.importer');
		jimport('eshiol.j2xml.version');
		
		$xmlVersion = $xml['version'];
		$version = explode(".", $xmlVersion);
		$xmlVersionNumber = $version[0].substr('0'.$version[1], strlen($version[1])-1).substr('0'.$version[2], strlen($version[2])-1); 
		
		$j2xmlVersion = J2XMLVersion::$DOCVERSION;
		$version = explode(".", $j2xmlVersion);
		$j2xmlVersionNumber = $version[0].substr('0'.$version[1], strlen($version[1])-1).substr('0'.$version[2], strlen($version[2])-1); 
		
		if (($xmlVersionNumber == $j2xmlVersionNumber) || ($xmlVersionNumber == "120500")) 
		{
			//set_time_limit(120);
			$params = JComponentHelper::getParams('com_j2xml');
			$params['logger'] = 'xmlrpc';
			$importer = new J2XMLImporter();
			$importer->import($xml,$params);
		}
		else
			JLog::add(new JLogEntry(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion)),JLOG::ERROR,'lib_j2xml');

//		$app->logout();
		return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
	}

	
	static function gzdecode($data,&$filename='',&$error='',$maxlength=null)
	{
	    $len = strlen($data);
	    if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
	        $error = "Not in GZIP format.";
	        return null;  // Not GZIP format (See RFC 1952)
	    }
	    $method = ord(substr($data,2,1));  // Compression method
	    $flags  = ord(substr($data,3,1));  // Flags
	    if ($flags & 31 != $flags) {
	        $error = "Reserved bits not allowed.";
	        return null;
	    }
	    // NOTE: $mtime may be negative (PHP integer limitations)
	    $mtime = unpack("V", substr($data,4,4));
	    $mtime = $mtime[1];
	    $xfl   = substr($data,8,1);
	    $os    = substr($data,8,1);
	    $headerlen = 10;
	    $extralen  = 0;
	    $extra     = "";
	    if ($flags & 4) {
	        // 2-byte length prefixed EXTRA data in header
	        if ($len - $headerlen - 2 < 8) {
	            return false;  // invalid
	        }
	        $extralen = unpack("v",substr($data,8,2));
	        $extralen = $extralen[1];
	        if ($len - $headerlen - 2 - $extralen < 8) {
	            return false;  // invalid
	        }
	        $extra = substr($data,10,$extralen);
	        $headerlen += 2 + $extralen;
	    }
	    $filenamelen = 0;
	    $filename = "";
	    if ($flags & 8) {
	        // C-style string
	        if ($len - $headerlen - 1 < 8) {
	            return false; // invalid
	        }
	        $filenamelen = strpos(substr($data,$headerlen),chr(0));
	        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
	            return false; // invalid
	        }
	        $filename = substr($data,$headerlen,$filenamelen);
	        $headerlen += $filenamelen + 1;
	    }
	    $commentlen = 0;
	    $comment = "";
	    if ($flags & 16) {
	        // C-style string COMMENT data in header
	        if ($len - $headerlen - 1 < 8) {
	            return false;    // invalid
	        }
	        $commentlen = strpos(substr($data,$headerlen),chr(0));
	        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
	            return false;    // Invalid header format
	        }
	        $comment = substr($data,$headerlen,$commentlen);
	        $headerlen += $commentlen + 1;
	    }
	    $headercrc = "";
	    if ($flags & 2) {
	        // 2-bytes (lowest order) of CRC32 on header present
	        if ($len - $headerlen - 2 < 8) {
	            return false;    // invalid
	        }
	        $calccrc = crc32(substr($data,0,$headerlen)) & 0xffff;
	        $headercrc = unpack("v", substr($data,$headerlen,2));
	        $headercrc = $headercrc[1];
	        if ($headercrc != $calccrc) {
	            $error = "Header checksum failed.";
	            return false;    // Bad header CRC
	        }
	        $headerlen += 2;
	    }
	    // GZIP FOOTER
	    $datacrc = unpack("V",substr($data,-8,4));
	    $datacrc = sprintf('%u',$datacrc[1] & 0xFFFFFFFF);
	    $isize = unpack("V",substr($data,-4));
	    $isize = $isize[1];
	    // decompression:
	    $bodylen = $len-$headerlen-8;
	    if ($bodylen < 1) {
	        // IMPLEMENTATION BUG!
	        return null;
	    }
	    $body = substr($data,$headerlen,$bodylen);
	    $data = "";
	    if ($bodylen > 0) {
	        switch ($method) {
	        case 8:
	            // Currently the only supported compression method:
	            $data = gzinflate($body,$maxlength);
	            break;
	        default:
	            $error = "Unknown compression method.";
	            return false;
	        }
	    }  // zero-byte body content is allowed
	    // Verifiy CRC32
	    $crc   = sprintf("%u",crc32($data));
	    $crcOK = $crc == $datacrc;
	    $lenOK = $isize == strlen($data);
	    if (!$lenOK || !$crcOK) {
	        $error = ( $lenOK ? '' : 'Length check FAILED. ') . ( $crcOK ? '' : 'Checksum FAILED.');
	        return false;
	    }
	    return $data;
	}
	
	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $message   The message to log.
	 * @param   string  $priority  Message priority based on {$this->priorities}.
	 *
	 * @return  void
	 *
	 * @since   3.1.107
	 */
	public static function enqueueMessage($message, $priority)
	{
		$found = false;
		$msgs = array();

		foreach(J2XMLMessages::$messages as $i => $m)
		{
			if ($message == JText::_($m))
			{
				self::$_messageQueue[] = new xmlrpcval(
					array(
						"code" => new xmlrpcval($i, 'int'),
						"string" => new xmlrpcval($message, 'string'),
						"message" => new xmlrpcval($message, 'string')
					), "struct"
				);
				$found = true;
				break;
			}
			else
			{
				$pattern = '/'.str_replace(array('(', ')', '.', '%s'), array('\(', '\)', '\.', '(.+)'), JText::_($m)).'/i';
				
				if (preg_match($pattern, $message, $matches))
				{
					self::$_messageQueue[] = new xmlrpcval(
						array(
							"code" => new xmlrpcval($i, 'int'),
							"string" => new xmlrpcval($matches[1], 'string'),
							"message" => new xmlrpcval($message, 'string')
						), "struct"
					);
					$found = true;
					break;
				}
			}
		}
		if (!$found)
			self::$_messageQueue[] = new xmlrpcval(
				array(
					"code" => new xmlrpcval(-1, 'int'),
					"string" => new xmlrpcval($message, 'string'),
					"message" => new xmlrpcval($message, 'string')
				), "struct"
			);
	}	

	/**
	 * Import articles from xml file
	 *
	 * @param base64 $xml
	 * @param string $username Username
	 * @param string $password Password
	 * @return string
	 * @since 1.5
	 */
	public static function login($username='', $password='')
	{
		JLog::add(new JLogEntry(__METHOD__,JLOG::DEBUG,'com_j2xml'));
		JLog::add(new JLogEntry($username,JLOG::DEBUG,'com_j2xml'));
		JLog::add(new JLogEntry($password,JLOG::DEBUG,'com_j2xml'));
		global $xmlrpcerruser, $xmlrpcI4, $xmlrpcInt, $xmlrpcBoolean, $xmlrpcDouble, $xmlrpcString, $xmlrpcDateTime, $xmlrpcBase64, $xmlrpcArray, $xmlrpcStruct, $xmlrpcValue;
		
		$app = JFactory::getApplication();
		//$options = array('remember'=>true);
		$options = array();
		
		$result = $app->login(array('username'=>$username, 'password'=>$password), $options);
		
		if (JError::isError($result))
			JLog::add(new JLogEntry(JText::_('COM_J2XML_MSG_SETCREDENTIALSFROMREQUEST_FAILED'),JLOG::ERROR,'com_j2xml'));
		else
		{
//			jimport('joomla.user.helper');
//			JLog::add(new JLogEntry(JUserHelper::getShortHashedUserAgent(),JLOG::INFO,'com_j2xml'));
			JLog::add(new JLogEntry(JFactory::getSession()->getId(),JLOG::INFO,'com_j2xml'));
		}
		return new xmlrpcresp(new xmlrpcval(self::$_messageQueue, 'array'));
		
	}
}