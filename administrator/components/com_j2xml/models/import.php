<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2021 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

jimport('eshiol.J2xml.Importer');
jimport('eshiol.J2xmlpro.Importer');
jimport('eshiol.J2xml.Messages');
jimport('eshiol.J2xml.Version');
jimport('eshiol.J2xmlpro.Version');

/**
 * J2XML Import Model
 *
 * @since  3.9
 */
class J2xmlModelImport extends JModelForm
{
	/**
	 * @var object JTable object
	 */
	protected $_table = null;

	/**
	 * @var object JTable object
	 */
	protected $_url = null;

	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $_context = 'com_j2xml.import';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   3.9
	 */
	protected function populateState ()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$app = JFactory::getApplication('administrator');

		$this->setState('message', $app->getUserState('com_j2xml.message'));
		$app->setUserState('com_j2xml.message', '');

		parent::populateState();
	}

	/**
	 * Import data from either folder, URL or upload.
	 *
	 * @return  boolean result of import.
	 *
	 * @since   3.9
	 */
	public function import()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$this->setState('action', 'import');

		// Set FTP credentials, if given.
		JClientHelper::setCredentialsFromRequest('ftp');
		$app = JFactory::getApplication();

		// Load j2xml plugins for assistance if required:
		JPluginHelper::importPlugin('j2xml');

		$package = null;

		$installType = $app->input->getWord('installtype');

		if ($package === null)
		{
			switch ($installType)
			{
				case 'folder':
					// Remember the 'Import from Directory' path.
					$app->getUserStateFromRequest($this->_context . '.install_directory', 'install_directory');
					$package = $this->_getDataFromFolder();
					break;

				case 'upload':
					$package = $this->_getDataFromUpload();
					break;

				case 'url':
					$package = $this->_getDataFromUrl();
					break;

				default:
					$app->setUserState('com_j2xml.message', JText::_('COM_J2XML_NO_IMPORT_TYPE_FOUND'));

					return false;
					break;
			}
		}

		JLog::add(new JLogEntry('package: ' . print_r($package, true), JLog::DEBUG, 'com_j2xml'));

		if (! ($data = implode(gzfile($package['packagefile'])))) {
			$data = file_get_contents($package['packagefile']);
		}
		JLog::add(new JLogEntry('data: ' . $data, JLog::DEBUG, 'com_j2xml'));
		
		$jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');
		
		$fparams = new JRegistry($jform);
		JLog::add(new JLogEntry('jform: ' . print_r($fparams->toArray(), true), JLog::DEBUG, 'com_j2xml'));
		
		$cparams = JComponentHelper::getParams('com_j2xml');
		JLog::add(new JLogEntry('cparams: ' . print_r($cparams->toArray(), true), JLog::DEBUG, 'com_j2xml'));
		
		$params = new JRegistry();
		$params->set('categories', $fparams->get('import_categories', $cparams->get('import_categories', 1)));
		$params->set('contacts', $fparams->get('import_contacts', $cparams->get('import_contacts', 0)));
		$params->set('content', $fparams->get('import_content', $cparams->get('import_content', 1)));
		$params->set('fields', $fparams->get('import_fields', $cparams->get('import_fields', 0)));
		$params->set('images', $fparams->get('import_images', $cparams->get('import_images', 0)));
		$params->set('keep_category', $fparams->get('import_keep_category', $cparams->get('import_keep_category', 1)));
		if ($params->get('import_keep_category') == 2)
		{
			$params->set('content_category_forceto', $fparams->get('import_category', $cparams->get('import_category')));
		}
		$params->set('keep_id', $fparams->get('import_keep_id', $cparams->get('import_keep_id', 0)));
		$params->set('keep_user_id', $fparams->get('import_keep_user_id', $cparams->get('import_keep_user_id', 0)));
		$params->set('tags', $fparams->get('import_tags', $cparams->get('import_tags', 1)));
		$params->set('superusers', $fparams->get('import_superusers', $cparams->get('import_superusers', 0)));
		$params->set('usernotes', $fparams->get('import_usernotes', $cparams->get('import_usernotes', 0)));
		$params->set('users', $fparams->get('import_users', $cparams->get('import_users', 1)));
		$params->set('viewlevels', $fparams->get('import_viewlevels', $cparams->get('import_viewlevels', 1)));
		$params->set('weblinks', $fparams->get('import_weblinks', $cparams->get('import_weblinks', 0)));

		JLog::add(new JLogEntry('params: ' . print_r($params->toArray(), true), JLog::DEBUG, 'com_j2xml'));

		// This event allows a custom import of the data or a customization of the data:
		JPluginHelper::importPlugin('j2xml');
		$results = JFactory::getApplication()->triggerEvent('onJ2xmlPrepareData', array('com_j2xml.import', &$data, $params));

		if (in_array(true, $results, true))
		{
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_PLUGIN_ERROR'), JLog::ERROR, 'com_j2xml'));
			return true;
		}

		if (in_array(false, $results, true))
		{
			if (in_array($installType, array('upload', 'url')))
			{
				JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			}

			return false;
		}

		$data = strstr($data, '<?xml version="1.0" ');
		if (! defined('LIBXML_PARSEHUGE'))
		{
			define(LIBXML_PARSEHUGE, 524288);
		}

		$xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_PARSEHUGE);
		if (! $xml)
		{
			return;
		}
		elseif (strtoupper($xml->getName()) != 'J2XML')
		{
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLog::ERROR, 'com_j2xml'));
			return false;
		}
		elseif (! isset($xml['version']))
		{
			JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'), JLog::ERROR, 'com_j2xml'));
			return false;
		}
		else
		{
			JLog::add(new JLogEntry('Importing...', JLog::DEBUG, 'com_j2xml'));
			
			$xmlVersion = $xml['version'];

			if (version_compare($xml['version'], eshiol\J2xml\Version::$DOCVERSION, 'le'))
			{
				$params->set('version', (string) $xml['version']);

				$results = JFactory::getApplication()->triggerEvent('onJ2xmlBeforeImport', array('com_j2xml.import', &$xml, $params));

				$importer = class_exists('eshiol\J2xmlpro\Importer') ? new eshiol\J2xmlpro\Importer() : new eshiol\J2xml\Importer();
				$importer->import($xml, $params);

				JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			}
			else
			{
				JLog::add(new JLogEntry(JText::_('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED'), JLog::ERROR, 'com_j2xml'));
				return false;
			}
		}

		// Cleanup the install files.
		if (!is_file($package['packagefile']))
		{
			$config = JFactory::getConfig();
			$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
		}

		JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

		// Clear the cached extension data and menu cache
		//$this->cleanCache('com_content', 0);
		//$this->cleanCache('com_content', 1);

		return true;
	}

	/**
	 * Works out an import data package from a HTTP upload.
	 *
	 * @return Package definition or false on failure.
	 */
	protected function _getDataFromUpload()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		// Get the uploaded file information.
		$input	= JFactory::getApplication()->input;

		// Do not change the filter type 'raw'. We need this to let files containing PHP code to upload. See JInputFiles::get.
		$userfile = $input->files->get('install_package', null, 'raw');

		// Make sure that file uploads are enabled in php.
		if (!(bool) ini_get('file_uploads'))
		{
			JError::raiseWarning('', JText::_('COM_J2XML_MSG_IMPORT_WARNXMLFILE'));

			return false;
		}

		// Make sure that zlib is loaded so that the package can be unpacked.
		if (!extension_loaded('zlib'))
		{
			JError::raiseWarning('', JText::_('COM_J2XML_MSG_IMPORT_WARNXMLZLIB'));

			return false;
		}

		// If there is no uploaded file, we have a problem...
		if (!is_array($userfile))
		{
			JError::raiseWarning('', JText::_('COM_J2XML_MSG_INSTALL_NO_FILE_SELECTED'));

			return false;
		}

		// Is the PHP tmp directory missing?
		if ($userfile['error'] && ($userfile['error'] == UPLOAD_ERR_NO_TMP_DIR))
		{
			JError::raiseWarning(
				'',
				JText::_('COM_J2XML_MSG_IMPORT_WARNXMLUPLOADERROR') . '<br />' . JText::_('COM_J2XML_MSG_WARNINGS_PHPUPLOADNOTSET')
			);

			return false;
		}

		// Is the max upload size too small in php.ini?
		if ($userfile['error'] && ($userfile['error'] == UPLOAD_ERR_INI_SIZE))
		{
			JError::raiseWarning(
				'',
				JText::_('COM_J2XML_MSG_IMPORT_WARNXMLUPLOADERROR') . '<br />' . JText::_('COM_J2XML_MSG_WARNINGS_SMALLUPLOADSIZE')
			);

			return false;
		}

		// Check if there was a different problem uploading the file.
		if ($userfile['error'] || $userfile['size'] < 1)
		{
			JError::raiseWarning('', JText::_('COM_J2XML_MSG_IMPORT_WARNXMLUPLOADERROR'));

			return false;
		}

		// Build the appropriate paths.
		$config   = JFactory::getConfig();
		$tmp_dest = $config->get('tmp_path') . '/' . $userfile['name'];
		$tmp_src  = $userfile['tmp_name'];

		// Move uploaded file.
		jimport('joomla.filesystem.file');
		JFile::upload($tmp_src, $tmp_dest, false, true);

		// Unpack the downloaded package file.
		$package = JInstallerHelper::unpack($tmp_dest, true);

		return $package;
	}

	/**
	 * Import data from a directory
	 *
	 * @return  array  Package details or false on failure
	 *
	 * @since   3.9
	 */
	protected function _getDataFromFolder()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$input = JFactory::getApplication()->input;

		// Get the path to the package to install.
		$p_dir = $input->getString('install_directory');
		$p_dir = JPath::clean($p_dir);

		// Did you give us a valid directory?
		if (!is_dir($p_dir))
		{
			JError::raiseWarning('', JText::_('COM_J2XML_MSG_INSTALL_PLEASE_ENTER_A_PACKAGE_DIRECTORY'));

			return false;
		}

		$package['packagefile'] = null;
		$package['extractdir'] = null;
		$package['dir'] = $p_dir;

		return $package;
	}

	/**
	 * Import data from a URL.
	 *
	 * @return  Package details or false on failure.
	 *
	 * @since   3.9
	 */
	protected function _getDataFromUrl()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$input = JFactory::getApplication()->input;

		// Get the URL of the data to install.
		$url = $input->getString('install_url');

		// Did you give us a URL?
		if (!$url)
		{
			JError::raiseWarning('', JText::_('COM_J2XML_MSG_INSTALL_ENTER_A_URL'));

			return false;
		}

		// Handle updater XML file case:
/*
		if (preg_match('/\.xml\s*$/', $url))
		{
			jimport('joomla.updater.update');
			$update = new JUpdate;
			$update->loadFromXml($url);
			$package_url = trim($update->get('downloadurl', false)->_data);

			if ($package_url)
			{
				$url = $package_url;
			}

			unset($update);
		}
*/

		// Download the package at the URL given.
		$p_file = JInstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			JError::raiseWarning('', JText::_('COM_J2XML_MSG_INSTALL_INVALID_URL'));

			return false;
		}

		$config   = JFactory::getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file.
		$package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		return $package;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param array $data
	 *			Data for the form.
	 * @param boolean $loadData
	 *			True if the form is to load its own data (default case), false if not.
	 *
	 * @return JForm|boolean A JForm object on success, false on failure
	 *
	 * @since 3.9.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		try
		{
			$form = $this->loadForm($this->_context, 'import', array(
				'control' => 'jform',
				'load_data' => false
			));

			if ($loadData)
			{
				// Get the data for the form.
				$data = $this->loadFormData();
			}
			else
			{
				$data = array();
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return mixed The data for the form.
	 *
	 * @since 3.9.0
	 */
	protected function loadFormData()
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));
		
		// Check the session for previously entered form data.
		$data   = JFactory::getApplication()->getUserState('com_j2xml.import.data', array());
		JLog::add(new JLogEntry('getUserState(\'com_j2xml.import.data\'): ' . print_r($data, true), JLog::DEBUG, 'com_j2xml'));
		$jform  = array();
		foreach($data as $k => $v)
		{
			$jform['import_' . $k] = $v;
		}

		$params = JComponentHelper::getParams('com_j2xml');
		$data   = array_merge($params->toArray(), $jform);
		JLog::add(new JLogEntry('data: ' . print_r($data, true), JLog::DEBUG, 'com_j2xml'));

		$this->preprocessData($this->_context, $data);

		return $data;
	}
}
