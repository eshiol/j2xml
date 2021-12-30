<?php
namespace eshiol\J2xml\Helper;

use Joomla\CMS\Factory as JFactory;

class Joomla {
	// Create alias class for original call in $filepath, then overload the class
	public static function makeAlias($filepath, $originClassName, $aliasClassName) 
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'plg_system_j2xml'));
		\JLog::add(new \JLogEntry($filepath, \JLog::DEBUG, 'plg_system_j2xml'));
		\JLog::add(new \JLogEntry($originClassName, \JLog::DEBUG, 'plg_system_j2xml'));
		\JLog::add(new \JLogEntry($aliasClassName, \JLog::DEBUG, 'plg_system_j2xml'));
		
		if (!is_file($filepath)) return false;

		$code = file_get_contents($filepath);
		$code = str_replace('class ' . $originClassName, 'class ' . $aliasClassName, $code);
		eval('?>'. $code);
		return true;
	}
}
