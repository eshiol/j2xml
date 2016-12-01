<?php
/**
 * @version		3.3.156 components/com_j2xml/helpers/log.php
 * 
 * @package		J2XML
 * @subpackage	com_j2xml
 * @since		3.1
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2013, 2016 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

/**
 * Joomla XMLRPC logger class.
 *
 * This class is designed to output logs as xmlrpc message
 */
if (version_compare(JPlatform::RELEASE, '12', 'ge'))
{
	class JLogLoggerXmlrpc extends JLogLogger
	{
		/**
		 * Method to add an entry to the log.
		 *
		 * @param   JLogEntry  $entry  The log entry object to add to the log.
		 *
		 * @return  void
		 *
		 * @since   13.8
		 */
		public function addEntry(JLogEntry $entry)
		{
			switch ($entry->priority)
			{
				case JLog::EMERGENCY:
				case JLog::ALERT:
				case JLog::CRITICAL:
				case JLog::ERROR:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'error');
					break;
				case JLog::WARNING:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'warning');
					break;
				case JLog::NOTICE:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'notice');
					break;
				case JLog::INFO:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'message');
					break;
				default:
					// Ignore other priorities.
					break;
			}
		}
	}
} else {
	jimport('joomla.log.logger');
	
	class JLoggerXmlrpc extends JLogger
	{
		/**
		 * Method to add an entry to the log.
		 *
		 * @param   JLogEntry  $entry  The log entry object to add to the log.
		 *
		 * @return  void
		 *
		 * @since   13.8
		 */
		public function addEntry(JLogEntry $entry)
		{
			switch ($entry->priority)
			{
				case JLog::EMERGENCY:
				case JLog::ALERT:
				case JLog::CRITICAL:
				case JLog::ERROR:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'error');
					break;
				case JLog::WARNING:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'warning');
					break;
				case JLog::NOTICE:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'notice');
					break;
				case JLog::INFO:
					XMLRPCJ2XMLServices::enqueueMessage($entry->message, 'message');
					break;
				default:
					// Ignore other priorities.
					break;
			}
		}
	}
}