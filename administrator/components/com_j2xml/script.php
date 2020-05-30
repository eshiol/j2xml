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
defined('_JEXEC') or die('Restricted access.');

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @version __DEPLOY_VERSION__
 * @since 3.7
 */
class Com_J2xmlInstallerScript
{
	/**
	 * This method is called after a component is installed.
	 *
	 * @param  \stdClass $parent - Parent object calling this method.
	 *
	 * @return void
	 */
	public function install($parent)
	{
	}

	/**
	 * This method is called after a component is uninstalled.
	 *
	 * @param  \stdClass $parent - Parent object calling this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
		$version = new \JVersion();
		if ($version->isCompatible('3.9')) return;

		$db = \JFactory::getDbo();
		$serverType = $version->isCompatible('3.5') ? $db->getServerType() : 'mysql';

		$queries = array();
		if ($serverType === 'mysql')
		{
			$queries[] = 'DROP PROCEDURE IF EXISTS usergroups_getpath;';
			$queries[] = 'DROP FUNCTION IF EXISTS usergroups_getpath;';
		}
		elseif ($serverType === 'postgresql')
		{
			$queries[] = 'DROP FUNCTION IF EXISTS usergroups_getpath(INT);';
		}

		if (count($queries))
		{
			// Process each query in the $queries array (split out of sql file).
			foreach ($queries as $query)
			{
				if ($version->isCompatible('3.5'))
				{
					$query = $db->convertUtf8mb4QueryToUtf8($query);
				}
				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (\JDatabaseExceptionExecuting $e)
				{
					\JLog::add(\JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()), \JLog::WARNING, 'jerror');

					return false;
				}
			}
		}
	}

	/**
	 * This method is called after a component is updated.
	 *
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	public function update($parent)
	{
	}

	/**
	 * Runs just before any installation action is preformed on the component.
	 * Verifications and pre-requisites should run in this function.
	 *
	 * @param  string    $type   - Type of PreFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @param  string    $type   - Type of PostFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	function postflight($type, $parent)
	{
		if ($type == 'discover_install') return;
		
		$version = new \JVersion();
		if ($version->isCompatible('3.9')) return;
		
		$db		 = \JFactory::getDbo();
		$serverType = $version->isCompatible('3.5') ? $db->getServerType() : 'mysql';

		try
		{
			if ($serverType == 'postgresql')
			{
				$query = "CREATE TABLE IF NOT EXISTS \"#__j2xml_usergroups\" (
					\"id\" serial NOT NULL,
					\"parent_id\" bigint DEFAULT 0 NOT NULL,
					\"title\" varchar(100) DEFAULT '' NOT NULL,
					PRIMARY KEY  (\"id\")
					);";
				$db->setQuery($query)->execute();
			}
			else
			{
				$query = "CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
					`id` int(10) unsigned NOT NULL,
					`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
					`title` varchar(100) NOT NULL DEFAULT '',
					PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;";
				$db->setQuery($query)->execute();
			}
			$db->setQuery("TRUNCATE TABLE #__j2xml_usergroups")->execute();
			$db->setQuery("INSERT INTO #__j2xml_usergroups " .
					"SELECT id, parent_id, CONCAT('[\"',REPLACE(`title`,'\"','\\\"'),'\"]') " .
					"FROM #__usergroups;")->execute();
			do {
				$db->setQuery("UPDATE `#__j2xml_usergroups` j " .
						"INNER JOIN `#__usergroups` g " .
						"ON j.parent_id = g.id " .
						"SET j.parent_id = g.parent_id," .
						"j.title = CONCAT('[\"',REPLACE(`g`.`title`,'\"','\\\"'), '\",', SUBSTR(`j`.`title`,2));")->execute();
				$n = $db->setQuery("SELECT COUNT(*) " .
						"FROM #__j2xml_usergroups " .
						"WHERE parent_id > 0")->loadResult();
			} while ($n > 0);
		}
		catch (Exception $e)
		{
			// If the query fails we will go on
		}
		
		$queries = array();
		if ($serverType === 'mysql')
		{
			$queries[] = "DROP PROCEDURE IF EXISTS usergroups_getpath;";
			$queries[] = "DROP FUNCTION IF EXISTS usergroups_getpath;";
		}
		elseif ($serverType === 'postgresql')
		{
			$queries[] = "DROP FUNCTION IF EXISTS usergroups_getpath(INT);";
		}

		if (count($queries))
		{
			// Process each query in the $queries array (split out of sql file).
			foreach ($queries as $query)
			{
				if ($version->isCompatible('3.5'))
				{
					$query = $db->convertUtf8mb4QueryToUtf8($query);
				}
				$db->setQuery($query);

				try
				{
					$db->execute();
				}
				catch (\JDatabaseExceptionExecuting $e)
				{
					\JLog::add(\JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $e->getMessage()), \JLog::WARNING, 'jerror');

					return false;
				}
			}
		}
	}
}
