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

JHtml::_('bootstrap.tooltip');

$version = new JVersion();
if ($version->isCompatible('3.8'))
{
	JHtml::_('jquery.token');
}

JText::script('COM_J2XML_IMPORTING');
JText::script('COM_J2XML_PACKAGEIMPORTER_UPLOAD_ERROR_UNKNOWN');
JText::script('COM_J2XML_PACKAGEIMPORTER_UPLOAD_ERROR_EMPTY');
JText::script('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN');
JText::script('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED');

JFactory::getDocument()->addScriptDeclaration('
	Joomla.submitbuttonpackage = function()
	{
		var form = document.getElementById("adminForm");

		// do field validation 
		if (form.install_package.value == "")
		{
			alert("' . JText::_('COM_J2XML_PACKAGEIMPORTER_NO_PACKAGE', true) . '");
		}
		else
		{
			JoomlaInstaller.showLoading();
			form.installtype.value = "upload"
			form.submit();
		}
	};
');

// Drag and Drop installation scripts
$token = JSession::getFormToken();
$return = JFactory::getApplication()->input->getBase64('return');

// Drag-drop installation
JFactory::getDocument()->addScriptDeclaration(
<<<JS
	jQuery(document).ready(function($) {
		if (typeof FormData === 'undefined') {
			$('#legacy-uploader').show();
			$('#uploader-wrapper').hide();
			return;
		}

		var uploading = false;
		var dragZone  = $('#dragarea');
		var fileInput = $('#install_package');
		var button    = $('#select-file-button');
		var url       = 'index.php?option=com_installer&task=install.ajax_upload';
		var returnUrl = $('#installer-return').val();
		var actions   = $('.upload-actions');
		var progress  = $('.upload-progress');
		var progressBar = progress.find('.bar');
		var percentage = progress.find('.uploading-number');

		if (returnUrl) {
			url += '&return=' + returnUrl;
		}

		button.on('click', function(e) {
			fileInput.click();
		});

		fileInput.on('change', function (e) {
			e.preventDefault();
			e.stopPropagation();

			if (uploading) {
				return;
			}

			var files = e.originalEvent.target.files || e.originalEvent.dataTransfer.files;

			if (!files.length) {
				return;
			}

			var file = files[0];

			var reader = new FileReader();
			reader.onload = function(event) {
				console.log('reader.onload');
				try {
					var data = pako.ungzip(this.result, {"to": "string"});
					console.log('gzip');
				} catch (err) {
					console.log('xml');
					var data = this.result;
				}
				data = strstr(data, '<?xml version="1.0" ');
				console.log(data);

				eshiol.j2xml.convert.forEach(function(fn) {
					data = fn(data);
				});

				var xmlDoc;
				var nodes = Array();
				try {
				   	xmlDoc = $.parseXML(data);
					xml = $(xmlDoc);
					root = xml.find(":root")[0];

					if (root.nodeName != "j2xml") {
						console.log('file not supported');
						Joomla.renderMessages({'error': [Joomla.JText._('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN')]});
					} else if (versionCompare($(root).attr('version'), '15.9.0') < 0) {
						console.log('j2xml file version ' + $(root).attr('version') + ' not supported');
						Joomla.renderMessages({'error': [Joomla.JText._('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED').replace('%s', $(root).attr('version'))]});
						return false;
					} else {
						console.log('j2xml file version ' + $(root).attr('version'));

						$('#j2xml_filename').val(file.name);
						$('#j2xml_data').val(btoa(data));

						var j2xmlOptions  = Joomla.getOptions('J2XML'),
					    	JoomlaVersion = j2xmlOptions && j2xmlOptions.Joomla ? j2xmlOptions.Joomla : '3';

					    if  (JoomlaVersion == '4') {
							var el = document.getElementById('j2xmlImportModal')
							var modal = bootstrap.Modal.getInstance(el) // Returns a Bootstrap modal instance
							modal.show();
	    				}
	    				else {
							$('#j2xmlImportModal').modal();
						}

						fileInput.val('');
						return false;
					}
				} catch(e) {
					console.log(e);
				}
			};
			reader.readAsText(file, 'UTF-8');
		});

		dragZone.on('dragenter', function(e) {
			e.preventDefault();
			e.stopPropagation();

			dragZone.addClass('hover');

			return false;
		});

		// Notify user when file is over the drop area
		dragZone.on('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();

			dragZone.addClass('hover');

			return false;
		});

		dragZone.on('dragleave', function(e) {
			e.preventDefault();
			e.stopPropagation();
			dragZone.removeClass('hover');

			return false;
		});

		dragZone.on('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();

			dragZone.removeClass('hover');

			if (uploading) {
				return;
			}

			var files = e.originalEvent.target.files || e.originalEvent.dataTransfer.files;

			if (!files.length) {
				return;
			}

			var file = files[0];

			var reader = new FileReader();
			reader.onload = function(event) {
				console.log('reader.onload');
				try {
					var data = pako.ungzip(this.result, {"to": "string"});
					console.log('gzip');
				} catch (err) {
					console.log('xml');
					var data = this.result;
				}
				data = strstr(data, '<?xml version="1.0" ');
				console.log(data);

				eshiol.j2xml.convert.forEach(function(fn) {
					data = fn(data);
				});

				var xmlDoc;
				var nodes = Array();
				try {
				   	xmlDoc = $.parseXML(data);
					xml = $(xmlDoc);
					root = xml.find(":root")[0];

					if (root.nodeName != "j2xml") {
						console.log(Joomla.JText._('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN'));
						Joomla.renderMessages({'error': [Joomla.JText._('LIB_J2XML_MSG_FILE_FORMAT_UNKNOWN')]});
					} else if (versionCompare($(root).attr('version'), '15.9.0') < 0) {
						console.log(Joomla.JText._('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED').replace('%s', $(root).attr('version')));
						Joomla.renderMessages({'error': [Joomla.JText._('LIB_J2XML_MSG_FILE_FORMAT_NOT_SUPPORTED').replace('%s', $(root).attr('version'))]});
						return false;
					} else {
						console.log('j2xml file version ' + $(root).attr('version'));

						$('#j2xml_filename').val(file.name);
						$('#j2xml_data').val(btoa(data));

						var j2xmlOptions  = Joomla.getOptions('J2XML'),
					    	JoomlaVersion = j2xmlOptions && j2xmlOptions.Joomla ? j2xmlOptions.Joomla : '3';

					    if  (JoomlaVersion == '4') {
							var el = document.getElementById('j2xmlImportModal')
							var modal = bootstrap.Modal.getInstance(el) // Returns a Bootstrap modal instance
							modal.show();
	    				}
	    				else {
							$('#j2xmlImportModal').modal();
						}

						fileInput.val('');
						return false;
					}
				} catch(e) {
					console.log(e);
				}
			};
			reader.readAsText(file, 'UTF-8');
		});
	});
JS
);

JFactory::getDocument()->addStyleDeclaration(
<<<CSS
	#dragarea {
		background-color: #fafbfc;
		border: 1px dashed #999;
		box-sizing: border-box;
		padding: 5% 0;
		transition: all 0.2s ease 0s;
		width: 100%;
	}

	#dragarea p.lead {
		color: #999;
	}

	#upload-icon {
		font-size: 48px;
		width: auto;
		height: auto;
		margin: 0;
		line-height: 175%;
		color: #999;
		transition: all .2s;
	}

	#dragarea.hover {
		border-color: #666;
		background-color: #eee;
	}

	#dragarea.hover #upload-icon,
	#dragarea p.lead {
		color: #666;
	}

	 .upload-progress, .install-progress {
		width: 50%;
		margin: 5px auto;
	 }

	/* Default transition (.3s) is too slow, progress will not run to 100% */
	.upload-progress .progress .bar {
		-webkit-transition: width .1s;
		-moz-transition: width .1s;
		-o-transition: width .1s;
		transition: width .1s;
	}

	#dragarea[data-state=pending] .upload-progress {
		display: none;
	}

	#dragarea[data-state=pending] .install-progress {
		display: none;
	}

	#dragarea[data-state=uploading] .install-progress {
		display: none;
	}

	#dragarea[data-state=uploading] .upload-actions {
		display: none;
	}

	#dragarea[data-state=installing] .upload-progress {
		display: none;
	}

	#dragarea[data-state=installing] .upload-actions {
		display: none;
	}
CSS
);

$version = new \JVersion();
if ($version->isCompatible('3.7'))
{
	$maxSize = JFilesystemHelper::fileUploadMaxSize();
}
?>
<legend><?php echo JText::_('COM_J2XML_PACKAGEIMPORTER_UPLOAD_IMPORT_DATA'); ?></legend>

<div id="uploader-wrapper">
	<div id="dragarea" data-state="pending">
		<div id="dragarea-content" class="text-center">
			<p>
				<span id="upload-icon" class="icon-upload" aria-hidden="true"></span>
			</p>
			<div class="upload-progress">
				<div class="progress progress-striped active">
					<div class="bar bar-success"
						style="width: 0;"
						role="progressbar"
						aria-valuenow="0"
						aria-valuemin="0"
						aria-valuemax="100"
					></div>
				</div>
				<p class="lead">
					<span class="uploading-text">
						<?php echo JText::_('COM_J2XML_PACKAGEIMPORTER_UPLOADING'); ?>
					</span>
					<span class="uploading-number">0</span><span class="uploading-symbol">%</span>
				</p>
			</div>
			<div class="install-progress">
				<div class="progress progress-striped active">
					<div class="bar" style="width: 100%;"></div>
				</div>
				<p class="lead">
					<span class="installing-text">
						<?php echo JText::_('COM_J2XML_PACKAGEIMPORTER_IMPORTING'); ?>
					</span>
				</p>
			</div>
			<div class="upload-actions">
				<p class="lead">
					<?php echo JText::_('COM_J2XML_PACKAGEIMPORTER_DRAG_FILE_HERE'); ?>
				</p>
				<p>
					<button id="select-file-button" type="button" class="btn btn-success">
						<span class="icon-copy" aria-hidden="true"></span>
						<?php echo JText::_('COM_J2XML_PACKAGEIMPORTER_SELECT_FILE'); ?>
					</button>
				</p>
				<?php if ($version->isCompatible('3.7')) : ?>
					<p>
						<?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $maxSize); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<div id="legacy-uploader" style="display: none;">
	<div class="control-group">
		<label for="install_package" class="control-label"><?php echo JText::_('COM_J2XML_PACKAGEIMPORTER_DATA_FILE'); ?></label>
		<div class="controls">
			<input class="input_box" id="install_package" name="install_package" type="file" size="57" /><br>
			<?php echo JText::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $maxSize); ?>
		</div>
	</div>
	<div class="form-actions">
<!-- <button class="btn btn-primary" type="button" id="installbutton_package" onclick="Joomla.submitbuttonpackage()"> -->
		<button class="btn btn-primary" type="button" id="installbutton_package">
			<?php echo JText::_('COM_J2XML_PACKAGEIMPORTER_UPLOAD_AND_INSTALL'); ?>
		</button>
	</div>

	<input id="installer-return" name="return" type="hidden" value="<?php echo $return; ?>" />
	<input id="installer-token" name="token" type="hidden" value="<?php echo $token; ?>" />
</div>

<input id="j2xml_filename" name="j2xml_filename" type="hidden" value="" />
<input id="j2xml_data" name="j2xml_data" type="hidden" value="" />
