<?php
/**
 * @package		J2XML
 * @subpackage	lib_j2xml
 * @version		18.8.32
 * @since		18.8.32
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2010, 2018 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

namespace Joomla\CMS\Log\Logger;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\Log\Logger;
use Joomla\CMS\Log\Log;

/**
 * Joomla XMLRPC logger class.
 *
 * This class is designed to output logs as xmlrpc message
 */
class XmlrpcLogger extends Logger
{
	/**
	 * The service
	 *
	 * @var    string
	 * @since  18.8.32
	 */
	protected $service;

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

		$this->service = $this->options['service'];
	}
	
	/**
	 * Method to add an entry to the log.
	 *
	 * @param   LogEntry  $entry  The log entry object to add to the log.
	 *
	 * @return  void
	 *
	 * @since   18.8.
	 */
	public function addEntry(LogEntry $entry)
	{
		switch ($entry->priority)
		{
			case Log::EMERGENCY:
			case Log::ALERT:
			case Log::CRITICAL:
			case Log::ERROR:
				$this->service::enqueueMessage($entry->message, 'error');
				break;
			case Log::WARNING:
				$this->service::enqueueMessage($entry->message, 'warning');
				break;
			case Log::NOTICE:
				$this->service::enqueueMessage($entry->message, 'notice');
				break;
			case Log::INFO:
				$this->service::enqueueMessage($entry->message, 'message');
				break;
			default:
				// Ignore other priorities.
				break;
		}
	}
}
