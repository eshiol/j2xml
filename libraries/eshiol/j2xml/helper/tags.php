<?php
/**
 * @version		15.2.246 libraries/eshiol/j2xml/helper/tags.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		14.8.240
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2017 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

class eshHelperTags extends JHelperTags
{
	/**
	 * Function that converts tags paths into array of ids
	 *
	 * @param   array  $tags  Array of tags paths
	 *
	 * @return  array
	 *
	 * @since   14.8.240
	 */
	public static function convertPathsToIds($tags)
	{
		if ($tags)
		{
			// Remove duplicates
			$tags = array_unique($tags);

			$db = JFactory::getDbo();

			$query = $db->getQuery(true)
				->select('id')
				->from('#__tags')
				->where('path IN (' . implode(',', array_map(array($db, 'quote'), $tags)) . ')');
			$db->setQuery($query);

			try
			{
				$loadColumn = (class_exists('JPlatform') && version_compare(JPlatform::RELEASE, '12', 'ge')) ? 'loadColumn' : 'loadResultArray';
				$ids = $db->$loadColumn();
				return $ids;
			}
			catch (RuntimeException $e)
			{
				return false;
			}
		}

		return $tags;
	}
}