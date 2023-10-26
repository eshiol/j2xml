<?php
/**
 * @package     Joomla.Cli
 * @subpackage  J2xml
 *
 * @since		2.5
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

/**
 * This is a J2XML script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/j2xml.php -f j2xml_file.xml
 */

// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) die();

define('DS', DIRECTORY_SEPARATOR);

// Initialize Joomla framework
const _JEXEC = 1;
//define('_JEXEC', 1);

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(dirname(__FILE__)).'/defines.php'))
{
	require_once dirname(dirname(__FILE__)).'/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(dirname(__FILE__)));
	require_once JPATH_BASE.'/includes/defines.php';
}

// Get the framework.
if (file_exists(JPATH_LIBRARIES.'/import.legacy.php'))
{
	require_once JPATH_LIBRARIES.'/import.legacy.php';
}
else
{
	require_once JPATH_LIBRARIES.'/import.php';
	// Import necessary classes not handled by the autoloaders
	jimport('joomla.application.component.helper');
	// Force library to be in JError legacy mode
	JError::$legacy = true;
}

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES.'/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION.'/configuration.php';

// System configuration.
$config = new JConfig();

// Load Library language
$lang = JFactory::getLanguage();

// Try the j2xmlimporter file in the current language (without allowing the loading of the file in the default language)
$lang->load('com_j2xml', JPATH_ADMINISTRATOR, null, false, false)
	// Fallback to the j2xmlimporter file in the default language
	|| $lang->load('com_j2xml', JPATH_ADMINISTRATOR, null, true);

jimport('eshiol.J2xml.Importer');
jimport('eshiol.J2xmlpro.Importer');
jimport('eshiol.J2xml.Version');
jimport('eshiol.J2xmlpro.Version');
jimport('eshiol.J2xml.messages');

/**
 * @package  Joomla.CLI
 * @since    2.5
 */
class J2xmlCli extends JApplicationCli
{
	private static $codes = array('message'=>'i','notice'=>'!','error'=>'x');

	/**
	 * Entry point for the script
	 *
	 * @return  void
	 *
	 * @since   2.5.1
	 */
	public function doExecute()
	{
		// Merge the default translation with the current translation
		$lang = JFactory::getLanguage();
		$lang->load('lib_j2xml', JPATH_SITE, null, false, false)
			|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, false, false)
			// Fallback to the lib_j2xml file in the default language
			|| $lang->load('lib_j2xml', JPATH_SITE, null, true)
			|| $lang->load('lib_j2xml', JPATH_ADMINISTRATOR, null, true);

		$filename = $this->input->get('f',null,'');

		if (!$filename)
		{
			echo "Usage /usr/bin/php /path/to/site/cli/j2xml.php -f j2xml_file.xml";
			exit(1);
		}

		if (!file_exists($filename))
		{
			echo "File {$filename} not found";
			exit(1);
		}

		JLog::addLogger(array('text_file' => 'j2xml.php', 'extension' => 'com_j2xml'), JLog::ALL, array('lib_j2xml','cli_j2xml'));
		JLog::addLogger(array('logger' => 'echo', 'extension' => 'com_j2xml'), JLog::ALL & ~JLog::DEBUG, array('lib_j2xml','cli_j2xml'));

		if (!($data = implode(gzfile($filename))))
		{
			$data = file_get_contents($filename);
		}

		if (!mb_detect_encoding($data, 'UTF-8'))
		{
			$data = mb_convert_encoding($data, 'UTF-8');
		}

		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($data);
		if (!$xml)
		{
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$msg = $error->code.' - '.$error->message.' at line '.$error->line;
				switch ($error->level) {
					default:
					case LIBXML_ERR_WARNING:
						$this->out(sprintf('%d - %s at line %d',
							$error->message, $error->line,
							'message')
						);
						break;
					case LIBXML_ERR_ERROR:
						$this->out(sprintf('%d - %s at line %d',
							$error->message, $error->line,
							'notice')
						);
						break;
					case LIBXML_ERR_FATAL:
						$this->out(sprintf('%d - %s at line %d',
							$error->message, $error->line,
							'error')
						);
						break;
				}
			}
			libxml_clear_errors();
			exit(0);
		}

		if (!$xml)
		{
			$this->out(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
			exit(0);
		}

		$dispatcher = \JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('j2xml');
		$results = $dispatcher->trigger('onBeforeImport', array('cli_j2xml.import', &$xml));
		if (!$xml)
		{
			$this->out(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
		}
		elseif (strtoupper($xml->getName()) != 'J2XML')
		{
			$this->out(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
		}
		elseif(!isset($xml['version']))
		{
			$this->out(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'),'error');
		}
		else
		{
			$xmlVersion = $xml['version'];
			$version = explode(".", $xmlVersion);
			$xmlVersionNumber = $version[0] . substr('0' . $version[1], strlen($version[1]) - 1) . substr('0' . $version[2], strlen($version[2]) - 1);

			$j2xmlVersion = class_exists('eshiol\J2xmlpro\Version') ? eshiol\J2xmlpro\Version::$DOCVERSION : eshiol\J2xml\Version::$DOCVERSION;
			$version = explode(".", $j2xmlVersion);
			$j2xmlVersionNumber = $version[0] . substr('0' . $version[1], strlen($version[1]) - 1) . substr('0' . $version[2], strlen($version[2]) - 1);

			if (($xmlVersionNumber == $j2xmlVersionNumber) || ($xmlVersionNumber == "150900") || ($xmlVersionNumber == "120500"))
			{
				set_time_limit(120);
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
				$iparams->set('weblinks', $params->get('import_weblinks'));
				$iparams->set('logger', 'xmlrpc');

				$iparams->set('keep_frontpage', $params->get('keep_frontpage'));
				$iparams->set('keep_rating', $params->get('keep_rating'));

				if ($params->get('keep_category', 1) == 2)
				{
					$iparams->set('content_category_forceto', $params->get('category'));
				}

				$iparams->set('keep_data', $params->get('keep_data'));

				$importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer() : new eshiol\J2xml\Importer();
				$importer->import($xml, $iparams);
			}
			else
			{
				$this->out(JText::sprintf('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED', $xmlVersion),'error');
			}
		}
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  void
	 *
	 * @since   2.5.1
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		$this->out(sprintf("%s - %s",self::$codes[$type],$msg));
	}
}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
$cli = JApplicationCli::getInstance('J2XMLCli');
JFactory::$application = $cli;
$cli->execute();
