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
 * @copyright   Copyright (C) 2010 - 2022 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

defined('JPATH_PLATFORM') or die;

\JLog::add(new \JLogEntry(__FILE__, \JLog::DEBUG, 'plg_system_j2xml'));


// Make alias of original FileLayout
\eshiol\J2xml\Helper\Joomla::makeAlias(JPATH_LIBRARIES . '/cms/layout/file.php', 'JLayoutFile', '_JLayoutFile');

// Override original FileLayout to trigger event when find layout
class JLayoutFile extends _JLayoutFile
{
    public function getDefaultIncludePaths()
    {
    	\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'plg_system_j2xml'));

    	$layoutPath = array(JPATH_PLUGINS . '/system/j2xml/layouts');

    	$paths = parent::getDefaultIncludePaths();
    	if (empty($paths))
    	{
    		$paths = $layoutPath;
    	}
    	else //if (is_array($paths))
    	{
    		$paths = array_unique(array_merge($paths, $layoutPath));
    	}

        return $paths;
    }
}
