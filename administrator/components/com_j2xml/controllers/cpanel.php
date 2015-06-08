<?php
/**
 * @version		3.2.131 administrator/components/com_j2xml/controllers/cpanel.php
 *
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		1.5.3
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010-2015 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

class j2xmlControllerCpanel extends JControllerAbstract
{
	/**
	 * Custom Constructor
	 */
	function __construct( $default = array())
	{
		parent::__construct($default);
	}

	public function display($cachable = false, $urlparams = false)
	{
		JRequest::setVar('view', 'cpanel');
		JRequest::setVar('layout', 'default');
		parent::display($cachable, $urlparams);
	}

	/**
	 * Removes invalid XML
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	static function stripInvalidXml($value)
	{
	    $ret = "";
	    $current;
	    if (empty($value)) 
	    {
	        return $ret;
	    }
	
	    $length = strlen($value);
	    for ($i=0; $i < $length; $i++)
	    {
	        $current = ord($value{$i});
	        if (($current == 0x9) ||
	            ($current == 0xA) ||
	            ($current == 0xD) ||
	            (($current >= 0x20) && ($current <= 0xD7FF)) ||
	            (($current >= 0xE000) && ($current <= 0xFFFD)) ||
	            (($current >= 0x10000) && ($current <= 0x10FFFF)))
	        {
	            $ret .= chr($current);
	        }
	        else
	        {
	            $ret .= " ";
	        }
	    }
	    return $ret;
	}
	
	function import()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit('Invalid Token');
		
		JLog::addLogger(array('logger' => 'messagequeue'), JLOG::ALL, array('j2xml'));
		
		$app = JFactory::getApplication('administrator');
		$msg='';
		$db = JFactory::getDBO();
		$date = JFactory::getDate();
		$now = $date->toSQL();
		$params = JComponentHelper::getParams('com_j2xml');
		$this->setRedirect('index.php?option=com_j2xml');
		libxml_use_internal_errors(true);

		$filetype = JRequest::getVar('j2xml_filetype', 1);
		switch (JRequest::getVar('j2xml_filetype', 1)) {
			case 1:
				//Retrieve file details from uploaded file, sent from upload form:
				$file = JRequest::getVar('j2xml_local', null, 'files', 'array');
				if(!isset($file))
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'),'error');
					return false;
				} 
				elseif($file['error'] > 0)
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'),'error');
					return false;
				}
				$filename = $file['tmp_name'];
				break;
			case 2:
				if (!($filename = JRequest::getVar('j2xml_url')))
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'),'error');
					return false;
				}
				break;
			case 3:
				if ($filename = JRequest::getVar('j2xml_server', null))
					$filename = JPATH_ROOT.'/'.$filename;
				else 
				{
					$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'),'error');
					return false;
				}
				break;
			default:
				$app->enqueueMessage(JText::_('COM_J2XML_MSG_UPLOAD_ERROR'),'error');
				return false;
		}		
		if (!($data = implode(gzfile($filename))))
			$data = file_get_contents($filename);
		
		$data = substr($data, strpos($data, '<?xml version="1.0" encoding="UTF-8" ?>'));
		$data = self::stripInvalidXml($data);
		if (!defined('LIBXML_PARSEHUGE'))
			define(LIBXML_PARSEHUGE, 524288);
		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);
		
		if (!$xml)
		{
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$msg = $error->code.' - '.$error->message.' at line '.$error->line;
				switch ($error->level) {
					default:
					case LIBXML_ERR_WARNING:
						$app->enqueueMessage($msg,'message');
						break;
					case LIBXML_ERR_ERROR:
						$app->enqueueMessage($msg,'notice');
						break;
					case LIBXML_ERR_FATAL:
						$app->enqueueMessage($msg,'error');
						break;
				}
			}
			libxml_clear_errors();
			$this->setRedirect('index.php?option=com_j2xml');	
		}
		
		if (!$xml)
		{
			$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
			return false;
		}
		
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');
		$results = $dispatcher->trigger('onBeforeImport', array('com_j2xml.cpanel', &$xml));
		
		if (!$xml)
			$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
		elseif (strtoupper($xml->getName()) == 'J2XML')
		{
			if(!isset($xml['version']))
   				$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
			else 
			{
				jimport('eshiol.j2xml.importer');
				
				$xmlVersion = $xml['version'];
				$version = explode(".", $xmlVersion);
				$xmlVersionNumber = $version[0].substr('0'.$version[1], strlen($version[1])-1).substr('0'.$version[2], strlen($version[2])-1); 
				if ($xmlVersionNumber == 120500)
				{
					//set_time_limit(120);
					$params = JComponentHelper::getParams('com_j2xml');
					J2XMLImporter::import($xml,$params);
				}
				elseif ($xmlVersionNumber == 10506)
				{
					$app->enqueueMessage(JText::sprintf('COM_J2XML_MSG_FILE_FORMAT_J2XML15', $xmlVersion),'error');
				}
				else
					$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion),'error');
			}	
		}
		elseif (strtoupper($xml->getName()) == 'RSS')
		{
			$namespaces = $xml->getNamespaces(true);
			if (isset($namespaces['wp']))
				if ($generator = $xml->xpath('/rss/channel/generator'))
					if (preg_match("/http:\/\/wordpress.(org|com)\//", (string)$generator[0]) != false)
					{
						$xml->registerXPathNamespace('wp', $namespaces['wp']);
						if (!($wp_version = $xml->xpath('/rss/channel/wp:wxr_version')))
							$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
						else if ($wp_version[0] == '1.2')
							$app->enqueueMessage(JText::_('COM_J2XML_MSG_FILE_FORMAT_J2XMLWP'),'error');
						else if ($wp_version[0] == '1.1')
							$app->enqueueMessage(JText::_('COM_J2XML_MSG_FILE_FORMAT_J2XMLWP'),'error');
						else
							$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
					}
					else
						$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
				else
					$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
			else
				$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
		}
		else
			$app->enqueueMessage(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
		$this->setRedirect('index.php?option=com_j2xml');	
	}

	function clean()
	{
		// Check for request forgeries
		JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
//		$params = JComponentHelper::getParams('com_j2xml');
		$hostname = JFactory::getURI()->getHost();
		if (
//				($params->get('deveopment') &&
				($hostname == 'localhost') &&
				(JRequest::getCmd('develop', '0') === '1') 
		)
		{
			jimport('eshiol.j2xml.importer');
			
			J2XMLImporter::clean();
			$app = JFactory::getApplication('administrator');
			$app->enqueueMessage(JText::_('COM_J2XML_MSG_CLEANED','info'));
		}
		$this->setRedirect('index.php?option=com_j2xml');	
	}
}