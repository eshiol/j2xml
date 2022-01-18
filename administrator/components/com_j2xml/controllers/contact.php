<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
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

// no direct access
defined('_JEXEC') or die();

/**
 * Content controller class.
 *
 * @since 3.6.161
 */
class J2xmlControllerContact extends JControllerLegacy
{

	/**
	 * The _context for persistent state.
	 *
	 * @var string
	 * @since 3.6.161
	 */
	protected $_context = 'j2xml.contact';

	/**
	 * The params object
	 *
	 * @var JRegistry
	 * @since 3.6.161
	 */
	protected $params;

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param string $name
	 *			The name of the model.
	 * @param string $prefix
	 *			The prefix for the model class name.
	 * @param array $config
	 *			Configuration array for model. Optional.
	 *			
	 * @return JModelLegacy
	 *
	 * @since 3.9.0
	 */
	public function getModel($name = 'Export', $prefix = 'J2xmlModel', $config = array())
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		return parent::getModel($name, $prefix, array(
			'ignore_request' => true
		));
	}

	/**
	 * Display method for the raw contacts data.
	 *
	 * @param boolean $cachable
	 *			If true, the view output will be cached
	 * @param array $urlparams
	 *			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *			
	 * @return J2xmlControllerUser This object to support chaining.
	 *		
	 * @since 3.6.161
	 * @todo This should be done as a view, not here!
	 */
	function display($cachable = false, $urlparams = false)
	{
		JLog::add(new JLogEntry(__METHOD__, JLog::DEBUG, 'com_j2xml'));

		$app = JFactory::getApplication();
		$jform  = $app->input->post->get('jform', array(), 'array');
		$data = array();
		foreach($jform as $k => $v)
		{
			if (substr($k, 0, 7) == 'export_')
			{
				$data[substr($k, 7)] = $v;
			}
		}
		// Save the posted data in the session.
		$app->setUserState('com_j2xml.export.data', $data);
		JLog::add(new JLogEntry('setUserState(\'com_j2xml.export.data\'): ' . print_r($data, true), JLog::DEBUG, 'com_j2xml'));

		$this->input->set('view', 'contact');
		parent::display();
	}
}
