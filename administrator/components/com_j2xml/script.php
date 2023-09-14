<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  System.J2xml
 *
 * @version     __DEPLOY_VERSION__
 * @since       3.9
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
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 *
 */
class Com_J2xmlInstallerScript
{
    /**
     * The J2XML Version we are updating from
     *
     * @var    string
     * @since  3.9.232
     */
    protected $fromVersion = null;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 * @since  3.9.232
	 */
	protected $db;

	/**
	 * This method is called after a extension is installed.
	 *
	 * @param  \stdClass $parent - Parent object calling this method.
	 *
	 * @return void
	 */
	public function install($parent)
	{
	}
 
	/**
	 * This method is called after a extension is uninstalled.
	 *
	 * @param  \stdClass $parent - Parent object calling this method.
	 *
	 * @return void
	 */
	public function uninstall($parent) 
	{
	}

	/**
	 * This method is called after a extension is updated.
	 *
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	public function update($parent) 
	{
		$this->deleteUnexistingFiles();
	}

    /**
     * Function to act prior to installation process begins
     *
     * @param   string     $action     Which action is happening (install|uninstall|discover_install|update)
     * @param   Installer  $installer  The class calling this method
     *
     * @return  boolean  True on success
     */
    public function preflight($action, $installer)
    {
       if ($action === 'update')
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote('component'))
				->where($db->quoteName('element') . ' = ' . $db->quote('com_j2xml'));

			$db->setQuery($query);

			$j2xml = $db->loadObject();
		
			if ($j2xml)
			{
				$manifestValues = json_decode($j2xml->manifest_cache, true);

                if (array_key_exists('version', $manifestValues))
				{
                    $this->fromVersion = $manifestValues['version'];

					if (version_compare($this->fromVersion, '3.9.3', '<'))
					{
						Factory::getApplication()->enqueueMessage(JText::_('COM_J2XML_NOTINSTALLED'));

						return false;
					}
                }
            }
        }

        return true;
    }

	/**
	 * Runs right after any installation action is preformed on the extension.
	 *
	 * @param  string	$type	 - Type of PostFlight action. Possible values are:
	 *							 - * install
	 *							 - * update
	 *							 - * discover_install
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	function postflight($type, $parent)
	{
	}

	/**
	 * Delete files that should not exist
	 *
	 * @return  void
	 */
	public function deleteUnexistingFiles()
	{
		$files = array(
			/*
			 * 3.9.232
			 */
			'/administrator/components/com_j2xml/controllers/cpanel.json.php',
			'/administrator/components/com_j2xml/controllers/cpanel.php',
			'/language/en-GB/en-GB.lib_eshiol.ini',
			'/language/en-GB/en-GB.lib_eshiol.sys.ini',
		);

		// TODO There is an issue while deleting folders using the ftp mode
		$folders = array(
			/*
			 * 3.9.232
			 */
			'/administrator/components/com_j2xml/views/cpanel',
		);

		Factory::getLanguage()->load('com_j2xml', JPATH_ADMINISTRATOR);

		jimport('joomla.filesystem.file');
		foreach ($files as $file)
		{
		    if (JFile::exists(JPATH_ROOT . $file))
			{
				if (JFile::delete(JPATH_ROOT . $file))
				{
					Factory::getApplication()->enqueueMessage(JText::sprintf('COM_J2XML_FILE_DELETED', $file));
				}
				else
				{
					Factory::getApplication()->enqueueMessage(JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file));
				}
			}
		}

		jimport('joomla.filesystem.folder');
		foreach ($folders as $folder)
		{
		    if (JFolder::exists(JPATH_ROOT . $folder))
			{
				if (JFolder::delete(JPATH_ROOT . $folder))
				{
					Factory::getApplication()->enqueueMessage(JText::sprintf('COM_J2XML_FOLDER_DELETED', $folder));
				}
				else
			    {
			        Factory::getApplication()->enqueueMessage(JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder));
				}
		    }
		}
	}
}
