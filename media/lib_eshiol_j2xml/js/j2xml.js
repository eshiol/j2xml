/**
 * @version		17.1.292 media/lib_eshiol_j2xml/js/j2xml.js
 * 
 * @package		eshiol Library
 * @subpackage	lib_eshiol
 * @since		16.11.288
 *
 * @author		Helios Ciancio <info@eshiol.it>
 * @link		http://www.eshiol.it
 * @copyright	Copyright (C) 2012, 2017 Helios Ciancio. All Rights Reserved
 * @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * eshiol Library is free software. This version may have been modified 
 * pursuant to the GNU General Public License, and as distributed it includes 
 * or is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// Avoid `console` errors in browsers that lack a console.
(function () {
	var methods = [
		'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
		'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
		'profile', 'profileEnd', 'table', 'time', 'timeEnd', 'timeStamp',
		'trace', 'warn'
	];
	console = window.console = window.console || {};
	methods.forEach(function (method) {
		if (!console[method]) {
			console[method] = function () {};
		}
	});
}());
  
console.log('j2xml Library version 17.1.292');

if (typeof(eshiol) === 'undefined') {
	eshiol = {};
}

if (typeof(eshiol.j2xml) === 'undefined') {
	eshiol.j2xml = {};
}

if (typeof(eshiol.j2xml.convert) === 'undefined') {
	eshiol.j2xml.convert = [];
}

/**
 * process item
 *
 * @param {string} nodes the array of item
 * @param {int}	n
 * @param {int}	tot
 * 
 * @return  {boolean}  true on success.
 */ 
eshiol.j2xml.send = function($nodes, n, tot) 
{   
	console.log('eshiol.j2xml.send');

	if ($nodes.length > 0)
	{
//		item = $nodes.pop();
		item = $nodes.shift();
		item = '<?xml version="1.0" encoding="UTF-8" ?>'+item;
		console.log(item);
		jQuery.post(
			'index.php?option=com_j2xml&task=cpanel.import&format=json',
			{
			'j2xml_data': escape(item)
		    },
		    function(response, textStatus, jqXHR){
			    // Callback handler that will be called on success
		        console.log('done');
		        console.log(response);
				if (!response.success && response.message)
				{
					eshiol.renderMessages({
						'error': [response.message]
					});
				}
				else if (response.messages)
				{
					eshiol.renderMessages(response.messages);
				}
		    },'json'
		)
		.fail(function(jqXHR, textStatus, error) {
			console.log('fail');
			console.log('jqXHR: '+eshiol.dump(jqXHR));
			console.log('textStatus: '+textStatus);
			console.log('error: '+error);
			Joomla.ajaxErrorsMessages(jqXHR, textStatus, error);
		})
		.always(function() {
			console.log('always');
			n++;
			if (n == tot)
			{
				button.innerHTML = button_text+'ed... 100%';
		        setTimeout(function(){
		        	button.innerHTML = button_text;			        	
		        },2000);
			}
			else
			{
				x = Math.floor(n/tot*100);
		        button.innerHTML = button_text+'ing... '+x+'%';
			}
			document.getElementById('j-main-container').scrollIntoView();
			eshiol.j2xml.send($nodes, n, tot);
		});
	}
}

/**
 * Upload a file
 *
 * @param {string} name The name of the button
 * @param {string} url	The url to process the file
 * @return  {boolean}  true on success.
 */ 
eshiol.j2xml.import = function(name, url) 
{   
	console.log('eshiol.j2xml.import');
	console.log(name);
	console.log(url);
	
	console.log('filetype: '+jQuery('#'+name+'_filetype').val());

	switch(jQuery('#'+name+'_filetype').val()) {
	case '2':
		if (jQuery('#'+name+'_url').val())
		{
			return false;
		}
		else
		{
			alert('Please select a file');
			return true;
		}
		break;
	case '3':
		if (jQuery('#'+name+'_server').val())
		{
			return false;
		}
		else
		{
			alert('Please select a file');
			return true;
		}
		break;
	default:
		if (!jQuery('#'+name+'_local').val())
		{
			alert('Please select a file');
			return true;
		}
		break;
	}

	if (!window.File || !window.FileReader || !window.FileList || !window.Blob)
	{
		alert('The File APIs are not fully supported in this browser.');
		return false;
	}   
	
	Joomla.removeMessages();

	var AJAX_QUEUE = [];

	input = jQuery('#'+name+'_local')
	if (!input) 
	{
    	alert("Um, couldn't find the fileinput element.");
    	return false;
	}
	
	input = input[0];
	if (!input.files)
	{
		alert("This browser doesn't seem to support the `files` property of file inputs.");
		return false;
	}
	
	if (!input.files[0])
	{
		alert("Please select a file before clicking 'Load'");
		return false;
	}

	output_default = jQuery('#'+name+'_upload').find('span')[0].html;
	
	file = input.files[0];
	 
	jQuery('#'+name+'_local').val('');
	jQuery('#'+name+'_local1').val('');
	
    fr = new FileReader();
    fr.onload = function() {
    	var xml = fr.result;

    	eshiol.j2xml.convert.each(function(fn) {
			xml = fn(xml);
        });
    	
    	xmlDoc = jQuery.parseXML(xml);
		$xml = jQuery(xmlDoc);
		root = $xml.find(":root")[0];
		if (root.nodeName != "j2xml")
		{
			return true;
		}
		console.log(jQuery(root).attr('version'));
		
		var $nodes = Array();
		$root = jQuery(root);

		$root.children("user").each(function(index) {
            $nodes.push('<j2xml version="'+$root.attr("version")+'">'+"\n"+eshiol.XMLToString(this)+"\n</j2xml>");
        });

        $root.children("tag").each(function(index) {
            $nodes.push('<j2xml version="'+$root.attr("version")+'">'+"\n"+eshiol.XMLToString(this)+"\n</j2xml>");
        });

        $root.children("category").each(function(index) {
            $nodes.push('<j2xml version="'+$root.attr("version")+'">'+"\n"+eshiol.XMLToString(this)+"\n</j2xml>");
        });

        $root.children("content").each(function(index){
            $nodes.push('<j2xml version="'+$root.attr("version")+'">'+"\n"+eshiol.XMLToString(this)+"\n</j2xml>");
        });

        $root.children().each(function(index) {
        	if (["user","tag","category","content"].indexOf(this.nodeName) == -1)
        	{
        		$nodes.push('<j2xml version="'+$root.attr("version")+'">'+"\n"+eshiol.XMLToString(this)+"\n</j2xml>");
        	}
        });

        tot = $nodes.length;
        if (tot == 0)
        {
        	return true;
        }
        n = 0;
        
        button = jQuery('#'+name+'_upload').find('span')[0];
    	button_text = button.innerHTML;
        button.innerHTML = button_text+'ing... 0%';

		eshiol.j2xml.send($nodes, n, tot);
    };
	fr.readAsText(file);
	return true;
}
