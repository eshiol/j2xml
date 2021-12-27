<?php
/**
 * @package		Joomla.Plugins
 * @subpackage	System.J2xml
 *
 * @author		Helios Ciancio <info (at) eshiol (dot) it>
 * @link		https://www.eshiol.it
 * @copyright	Copyright (C) 2010 - 2021 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die('Restricted access.');

\JLog::add(new \JLogEntry(__FILE__, \JLog::DEBUG, 'plg_system_j2xml'));

JHtml::_('behavior.core');

/**
 * Generic toolbar button layout to open a modal
 * -----------------------------------------------
 * @param   array   $displayData	Button parameters. Default supported parameters:
 *								  - selector  string  Unique DOM identifier for the modal. CSS id without #
 *								  - class	 string  Button class
 *								  - icon	  string  Button icon
 *								  - text	  string  Button text
 */

$selector = $displayData['selector'];
$class	= isset($displayData['class']) ? $displayData['class'] : 'btn btn-small';
$icon	 = isset($displayData['icon']) ? $displayData['icon'] : 'out-3';
$title	= $displayData['title'];
$text	 = isset($displayData['text']) ? $displayData['text'] : '';
$onclick  = isset($displayData['onclick']) ? $displayData['onclick'] : '';
$cancel   = isset($displayData['cancel']) ? $displayData['cancel'] : JText::_('JCANCEL');
$ok	   = isset($displayData['ok']) ? $displayData['ok'] : JText::_('JOK');

JText::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
$message = "alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));";
?>
<button type="button" class="<?php echo $class; ?>" data-toggle="modal" onclick="if (document.adminForm.boxchecked.value==0){<?php echo $message; ?>}else{jQuery('#<?php echo $selector; ?>Modal').modal('show');return true;}">
	<span class="icon-<?php echo $icon; ?>" aria-hidden="true"></span>
	<?php echo $text; ?>
</button>

<!-- Render the modal -->
<?php
$version = new \JVersion();
if ($version->isCompatible('3.4'))
{
	echo JHtml::_('bootstrap.renderModal', $selector . 'Modal', array(
		'url'         => $displayData['doTask'],
		'title'	      => $title,
		'modalWidth'  => '40',
		'height'	  => '310px',
		'footer'	  => '<button class="btn" data-dismiss="modal" type="button"'
		. ' onclick="jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#' . $selector . 'CancelBtn\').click();">' . $cancel . '</button>'
		. '<button class="btn btn-success" type="button"'
		. ' onclick="' . $onclick . 'jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#' . $selector . 'OkBtn\').click();">'
		. $ok . '</button>'));
}
else
{
	echo JHtml::_(
		'bootstrap.renderModal',
		$selector . 'Modal',
		array(
			'url'         => $displayData['doTask'],
			'title'       => $title,
			'modalWidth'  => '40',
			'height'      => '310px'),
		'<button class="btn" data-dismiss="modal" type="button"'
			. ' onclick="jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#' . $selector . 'CancelBtn\').click();">' . $cancel . '</button>'
			. '<button class="btn btn-success" type="button"'
			. ' onclick="' . $onclick . 'jQuery(\'#' . $selector . 'Modal iframe\').contents().find(\'#' . $selector . 'OkBtn\').click();">'
			. $ok . '</button>');
}