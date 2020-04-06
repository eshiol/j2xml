<?php
/**
 * @package		J2XML
 * @subpackage	lib_phpxmlrpc
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	(C) 2010 - 2020 Helios Ciancio <info (at) eshiol (dot) it> (https://www.eshiol.it). All Rights Reserved.
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

namespace Joomla\CMS\Log\Logger;

// no direct access
defined('_JEXEC') or die('Restricted access.');

jimport('joomla.log.logger');

\JLoader::registerAlias('JLogLoggerXmlrpc', '\\Joomla\\CMS\\Log\\Logger\\XmlrpcLogger');

/**
 * Joomla XMLRPC logger class.
 *
 * This class is designed to output logs as xmlrpc message
 *
 * @version __DEPLOY_VERSION__
 * @since 4.3.1
 */
class XmlrpcLogger extends \JLogLogger
{
	/**
	 * Constructor.
	 *
	 * @param   array  &$options  Log object options.
	 *
	 * @since   18.8.32
	 */
	public function __construct(array &$options)
	{
		// Call the parent constructor.
		parent::__construct($options);

		// Throw an exception if there is not a valid callback
		if (!isset($this->options['service']))
		{
			throw new \RuntimeException(sprintf('%s created without valid service.', get_class($this)));
		}
	}

	/**
	 * Method to add an entry to the log.
	 *
	 * @param JLogEntry $entry
	 *        	The log entry object to add to the log.
	 *
	 * @return void
	 *
	 * @since 13.8
	 */
	public function addEntry (\JLogEntry $entry)
	{
		$service = $this->options['service'];

		switch ($entry->priority)
		{
			case \JLog::EMERGENCY:
			case \JLog::ALERT:
			case \JLog::CRITICAL:
			case \JLog::ERROR:
				$service::enqueueMessage($entry->message, 'error');
				break;
			case \JLog::WARNING:
				$service::enqueueMessage($entry->message, 'warning');
				break;
			case \JLog::NOTICE:
				$service::enqueueMessage($entry->message, 'notice');
				break;
			case \JLog::INFO:
				$service::enqueueMessage($entry->message, 'message');
				break;
			default:
				// Ignore other priorities.
				break;
		}
	}
}