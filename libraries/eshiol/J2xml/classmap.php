<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 *
 * @since		__DEPLOY_VERSION__
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

// No direct access.
defined('_JEXEC') or die();

if (class_exists('\\eshiol\\J2xml\\Exporter'))
	class_alias('\\eshiol\\J2xml\\Exporter', 'J2XMLExporter');
if (class_exists('\\eshiol\\J2xml\\Importer'))
	class_alias('\\eshiol\\J2xml\\Importer', 'J2XMLImporter');
if (class_exists('\\eshiol\\J2xml\\Sender'))
	class_alias('\\eshiol\\J2xml\\Sender', 'J2XMLSender');
if (class_exists('\\eshiol\\J2xml\\Version'))
	class_alias('\\eshiol\\J2xml\\Version', 'J2XMLVersion');
class_alias('JDatabase', 'JDatabaseDriver');
class_alias('JDispatcher', 'JEventDispatcher');
