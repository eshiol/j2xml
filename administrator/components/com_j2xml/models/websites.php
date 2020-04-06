<?php
/**
 * @package		J2XML
 * @subpackage	com_j2xml
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
defined('_JEXEC') or die();

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of website records.
 *
 * @version __DEPLOY_VERSION__
 * @since 2.5.85
 */
class J2XMLModelWebsites extends JModelList
{

	/**
	 * Constructor.
	 *
	 * @param
	 *        	array An optional associative array of configuration settings.
	 * @see JController
	 * @since 1.6
	 */
	public function __construct ($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
					'id',
					'a.id',
					'title',
					'a.title',
					'alias',
					'a.alias',
					'state',
					'a.state',
					'remote_url',
					'a.remote_url',
					'username',
					'a.username',
					'checked_out',
					'a.checked_out',
					'checked_out_time',
					'a.checked_out_time'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState ($ordering = null, $direction = null)
	{
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
		$this->setState('filter.state', $state);

		// Load the parameters.
		$params = JComponentHelper::getParams('com_j2xml');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.title', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param string $id
	 *        	A prefix for the store id.
	 *
	 * @return string A store id.
	 */
	protected function getStoreId ($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.access');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return JDatabaseQuery
	 */
	protected function getListQuery ()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
				$this->getState('list.select',
						'a.id AS id,' . 'a.title AS title,' . 'a.alias AS alias,' . 'a.remote_url AS remote_url,' . 'a.username AS username,' .
								 'a.state AS state,' . 'a.checked_out AS checked_out,' . 'a.checked_out_time AS checked_out_time,' .
								// 'a.access_token, a.refresh_token,
								// a.expire_time,'.
								'a.type'));

		$query->from($db->quoteName('#__j2xml_websites') . ' AS a');

		// Join over the users for the checked out user.
		$query->select('uc.name AS editor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');

		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state IN (0, 1))');
		}

		$params = $this->getState('params');
		if ($params->get('oauth2', 0) == 0)
		{
			$query->where('a.type = 0');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (! empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('a.title LIKE ' . $search . ' OR a.alias LIKE ' . $search);
			}
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}
}
