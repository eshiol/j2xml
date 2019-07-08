<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2019 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
namespace eshiol\J2XML;

// no direct access
defined('_JEXEC') or die('Restricted access.');

/**
 *
 * @version 19.5.333
 * @since 1.5.3
 */
class Version
{

	/**
	 * @public static string Product
	 */
	public static $PRODUCT = 'J2XML';

	/**
	 * @public static int Main Release Level
	 */
	public static $RELEASE = '19';

	/**
	 * @public static int Sub Release Level
	 */
	public static $DEV_LEVEL = '7';

	/**
	 * @public static string Development Status
	 */
	public static $DEV_STATUS = '';

	// dev < alpha = a < beta = b < RC = rc < # <
	// pl = p
	/**
	 * @public static int build Number
	 */
	public static $BUILD = '334';

	/**
	 * @public static string Codename
	 */
	public static $CODENAME = 'Shine On You Crazy Diamond';

	/**
	 * @public static string Copyright Text
	 */
	public static $COPYRIGHT = 'Copyright &copy; 2010 - 2019 Helios Ciancio <a href="http://www.eshiol.it" title="eshiol.it"><img src="../media/com_j2xml/images/eshiol.png" alt="eshiol.it" /></a>. All rights reserved.';

	/**
	 * @public static string License
	 */
	public static $LICENSE = '<a href="http://www.gnu.org/licenses/gpl-3.0.html">GNU GPL v3</a>';

	/**
	 * @public static string URL
	 */
	public static $URL = '<a href="http://www.eshiol.it/j2xml.html">J2XML</a> is Free Software released under the GNU General Public License.';

	/**
	 * @public static string xml file version
	 */
	public static $DOCVERSION = '19.2.0';

	/**
	 * @public static string dtd
	 */
	public static $DOCTYPE = '<!DOCTYPE j2xml PUBLIC "-//eshiol.it//DTD J2XML data file 12.5.0//EN" "http://www.eshiol.it/j2xml/12500/j2xml-12.5.0.dtd">';

	/**
	 * Method to get the long version information.
	 *
	 * @return string Long format version.
	 */
	public static function getLongVersion ()
	{
		return self::$RELEASE . '.' . self::$DEV_LEVEL . ' ' . (self::$DEV_STATUS ? ' ' . self::$DEV_STATUS : '') . ' build ' . self::$BUILD . ' [ ' .
				 self::$CODENAME . ' ] ';
	}

	/**
	 * Method to get the full version information.
	 *
	 * @return string version.
	 */
	public static function getFullVersion ()
	{
		return self::$RELEASE . '.' . self::$DEV_LEVEL . (self::$DEV_STATUS ? '-' . self::$DEV_STATUS : '') . '.' . self::$BUILD;
	}

	/**
	 * Method to get the short version information.
	 *
	 * @return string Short version format.
	 */
	public static function getShortVersion ()
	{
		return self::$RELEASE . '.' . self::$DEV_LEVEL;
	}

	public static function docversion_compare ($version)
	{
		\JLog::add(new \JLogEntry(__METHOD__, \JLog::DEBUG, 'lib_j2xml'));
		$a = explode(".", rtrim($version, ".0")); // Split version into pieces and
		                                          // remove trailing .0
		$b = explode(".", rtrim(self::$DOCVERSION, ".0")); // Split version into
		                                                   // pieces and remove
		                                                   // trailing .0
		$max_depth = 2;
		foreach ($a as $depth => $aVal)
		{ // Iterate over each piece of A
			if ($depth > $max_depth)
			{
				return 0;
			}
			elseif (isset($b[$depth]))
			{ // If B matches A to this depth, compare the values
				if ($aVal > $b[$depth])
					return 1; // Return A > B
				else if ($aVal < $b[$depth])
					return - 1; // Return B > A
						            // An equal result is
						            // inconclusive at this point
			}
			else
			{ // If B does not match A to this depth, then A comes after B in sort
			  // order
				return 1; // so return A > B
			}
		}
		// At this point, we know that to the depth that A and B extend to, they
		// are equivalent.
		// Either the loop ended because A is shorter than B, or both are equal.
		return (count($a) < count($b)) ? - 1 : 0;
	}
}
