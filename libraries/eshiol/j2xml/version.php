<?php
/**
 * @version		16.10.286 libraries/eshiol/j2xml/version.php
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @since		1.5.3
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
 
// no direct access
defined('_JEXEC') or die('Restricted access.');

class J2XMLVersion
{
	/** @public static string Product */
	public static $PRODUCT	= 'J2XML';
	/** @public static int Main Release Level */
	public static $RELEASE	= '16';
	/** @public static int Sub Release Level */
	public static $DEV_LEVEL	= '10';
	/** @public static string Development Status */
	public static $DEV_STATUS	= ''; //dev < alpha = a < beta = b < RC = rc < # < pl = p
	/** @public static int build Number */
	public static $BUILD		= '286';
	/** @public static string Codename */
	public static $CODENAME	= ' ';
	/** @public static string Copyright Text */
	public static $COPYRIGHT	= 'Copyright &copy; 2010, 2016 Helios Ciancio <a href="http://www.eshiol.it" title="eshiol.it"><img src="../media/com_j2xml/images/eshiol.png" alt="eshiol.it" /></a>. All rights reserved.';
	/** @public static string License */
	public static $LICENSE	= '<a href="http://www.gnu.org/licenses/gpl-3.0.html">GNU GPL v3</a>';	
	/** @public static string URL */
	public static $URL		= '<a href="http://www.eshiol.it/j2xml.html">J2XML</a> is Free Software released under the GNU General Public License.';
	/** @public static string xml file version */
	public static $DOCVERSION	= '15.9.0';
	/** @public static string dtd */
	public static $DOCTYPE	= '<!DOCTYPE j2xml PUBLIC "-//eshiol.it//DTD J2XML data file 12.5.0//EN" "http://www.eshiol.it/j2xml/12500/j2xml-12.5.0.dtd">';
	
	/**
	 * Method to get the long version information.
	 *
	 * @return	string	Long format version.
	 */
	public static function getLongVersion()
	{
		return self::$RELEASE .'.' 
			. self::$DEV_LEVEL .' '
			. (self::$DEV_STATUS ? ' '.self::$DEV_STATUS : '')
			. ' build ' . self::$BUILD
			.' [ '.self::$CODENAME .' ] '
			;
	}

	/**
	 * Method to get the full version information.
	 *
	 * @return	string	version.
	 */
	public static function getFullVersion()
	{
		return self::$RELEASE 
			.'.'.self::$DEV_LEVEL
			. (self::$DEV_STATUS ? '-'.self::$DEV_STATUS : '')
			.'.'.self::$BUILD;
	}

	/**
	 * Method to get the short version information.
	 *
	 * @return	string	Short version format.
	 */
	public static function getShortVersion() {
		return self::$RELEASE .'.'. self::$DEV_LEVEL;
	}
}
