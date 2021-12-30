<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2021 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die();

jimport('joomla.application.component.controlleradmin');

/**
 * Website list controller class.
 *
 * @version __DEPLOY_VERSION__
 * @since 1.5.3
 */
class J2XMLControllerWebsites extends JControllerAdmin
{

	/**
	 *
	 * @var string The prefix to use with controller messages.
	 * @since 1.6
	 */
	protected $text_prefix = 'COM_J2XML_WEBSITES';

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param string $name
	 *        	The model name. Optional.
	 * @param string $prefix
	 *        	The class prefix. Optional.
	 * @param array $config
	 *        	Configuration array for model. Optional.
	 *
	 * @return object The model.
	 *
	 * @since 1.6
	 */
	public function getModel ($name = 'Website', $prefix = 'J2XMLModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
}
