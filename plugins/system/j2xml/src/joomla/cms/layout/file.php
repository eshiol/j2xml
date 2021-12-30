<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
