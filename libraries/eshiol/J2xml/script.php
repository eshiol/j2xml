<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
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
 * Installation class to perform additional changes during
 * install/uninstall/update
 *
 * @version __DEPLOY_VERSION__
 * @since 18.11.311
 */
class eshiolj2xmlInstallerScript
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

		$db = \JFactory::getDbo();
		$serverType = $version->isCompatible('3.5') ? $db->getServerType() : 'mysql';

		$queries = array();
		if ($serverType === 'mysql')
		{
			$queries[] = "DROP PROCEDURE IF EXISTS usergroups_getpath;";
			$queries[] = preg_replace('!\s+!', ' ',<<<EOL
CREATE PROCEDURE usergroups_getpath(IN id INT, OUT path TEXT)
BEGIN
    DECLARE temp_title VARCHAR(100);
    DECLARE temp_path TEXT;
    DECLARE temp_parent INT;
	SET max_sp_recursion_depth = 255;

	SELECT a.title, a.parent_id FROM #__usergroups a WHERE a.id=id INTO temp_title, temp_parent;

	IF temp_parent = 0
    THEN
       SET path = temp_title;
    ELSE
        CALL usergroups_getpath(temp_parent, temp_path);
        SET path = CONCAT(temp_path, '","', temp_title);
    END IF;
END;
EOL
					);
			$queries[] = "DROP FUNCTION IF EXISTS usergroups_getpath;";
			$queries[] = preg_replace('!\s+!', ' ',<<<EOL
CREATE FUNCTION usergroups_getpath(id INT) RETURNS TEXT DETERMINISTIC
BEGIN
    DECLARE res TEXT;
    CALL usergroups_getpath(id, res);
    RETURN CONCAT('["', res, '"]');
END;
EOL
					);
		}
		elseif ($serverType === 'postgresql')
		{
			$queries[] = "DROP FUNCTION IF EXISTS usergroups_getpath(INT);";
			$queries[] = <<<EOL
CREATE OR REPLACE FUNCTION usergroups_getpath(id INT, level INT default 0) RETURNS TEXT
AS $$
DECLARE temp_title VARCHAR(100);
	temp_path TEXT;
	temp_parent INT;
BEGIN
	SELECT a.title, a.parent_id FROM #__usergroups a WHERE a.id = $1 INTO temp_title, temp_parent;

	IF temp_parent = 0
	THEN
		temp_path := temp_title;
	ELSE
		temp_path := CONCAT(usergroups_getpath(temp_parent, $2 + 1), '","', temp_title);
	END IF;
	IF $2 = 0
	THEN
		temp_path = CONCAT('["', temp_path, '"]');
	END IF;
	RETURN temp_path;
END;
$$ LANGUAGE plpgsql;
EOL;
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