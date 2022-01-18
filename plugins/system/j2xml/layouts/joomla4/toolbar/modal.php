<?php
/**
 * @package     Joomla.Plugins
 * @subpackage  System.J2xml
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
defined('_JEXEC') or die('Restricted access.');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HtmlHelper;
use Joomla\CMS\Language\Text;

Factory::getDocument()->getWebAssetManager()
	->useScript('webcomponent.toolbar-button');

/**
 * Generic toolbar button layout to open a modal
 * -----------------------------------------------
 * @param   array   $displayData	Button parameters. Default supported parameters:
 *								  - selector  string  Unique DOM identifier for the modal. CSS id without #
 *								  - class	 string  Button class
 *								  - icon	  string  Button icon
 *								  - text	  string  Button text
 */

$tagName = $tagName ?? 'button';

$selector = $displayData['selector'];
$id	   = isset($displayData['id']) ? $displayData['id'] : '';
$class	= isset($displayData['class']) ? $displayData['class'] : 'btn btn-sm btn-primary';
$icon	 = isset($displayData['icon']) ? $displayData['icon'] : 'fas fa-download';
$title	= $displayData['title'];
$text	 = isset($displayData['text']) ? $displayData['text'] : '';
$cancel   = isset($displayData['cancel']) ? $displayData['cancel'] : Text::_('JCANCEL');
$ok	   = isset($displayData['ok']) ? $displayData['ok'] : Text::_('JOK');
$onclick  = isset($displayData['onclick']) ? $displayData['onclick'] : '';
$validate = !empty($formValidation) ? ' form-validation' : '';
?>

<joomla-toolbar-button<?php echo $id; ?> onclick="document.getElementById('<?php echo $selector; ?>Modal').open();
	document.body.appendChild(document.getElementById('<?php echo $selector; ?>Modal'));"
	data-toggle="modal">
<<?php echo $tagName; ?>
	class="<?php echo $class ?? ''; ?>"
	<?php echo $htmlAttributes ?? ''; ?>
	<?php echo $title; ?>
	>
	<span class="<?php echo $icon; ?>" aria-hidden="true"></span>
	<?php echo $text ?? ''; ?>
</<?php echo $tagName; ?>>
</joomla-toolbar-button>

<!-- Render the modal -->
<?php
echo HtmlHelper::_('bootstrap.renderModal',
	$selector . 'Modal',
	array(
		'url'		 => $displayData['doTask'],
		'title'	   => $title,
		'modalWidth'  => '40',
		'height'	  => '310px',
		'closeButton' => true,
		'footer'	  => '<button class="btn btn-secondary" data-dismiss="modal" type="button"'
						. ' onclick="window.parent.Joomla.Modal.getCurrent().close();">'
						. $cancel . '</button>'
						.'<joomla-toolbar-button' . $validate	
						. ' onclick="' . $onclick . 'Joomla.iframeButtonClick({iframeSelector: \'#' . $selector . 'Modal\', buttonSelector: \'#' . $selector . 'OkBtn\'})">'
						. '<button class="btn btn-success" type="button">'
						. $ok . '</button>'
						.'</joomla-toolbar-button>'
	)
);
