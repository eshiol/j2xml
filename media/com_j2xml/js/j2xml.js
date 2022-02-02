/**
 * @package     Joomla.Site
 * @subpackage  com_j2xml
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

// Avoid `console` errors in browsers that lack a console.
(function (){
	var methods = [
		'assert', 'clear', 'count', 'debug', 'dir', 'dirxml', 'error',
		'exception', 'group', 'groupCollapsed', 'groupEnd', 'info', 'log',
		'profile', 'profileEnd', 'table', 'time', 'timeEnd', 'timeStamp',
		'trace', 'warn'
	];
	console = window.console = window.console || {};
	methods.forEach( function( method ){
		if ( ! console[method] ){
			console[method] = function (){};
		}
	} );

	console.dump = function( object ){
		if( window.JSON && window.JSON.stringify ){
			console.log( JSON.stringify( object ) );
		}
		else {
			console.log( object );
		}
	};
}() );

if ( typeof( eshiol ) === 'undefined' ){
	eshiol = {};
}

if ( typeof( eshiol.j2xml ) === 'undefined' ){
	eshiol.j2xml = {};
}

if ( typeof( eshiol.j2xml.com_j2xml ) === 'undefined' ){
	eshiol.j2xml.com_j2xml = {};
}

eshiol.j2xml.com_j2xml.version = '__DEPLOY_VERSION__';

console.log( 'J2XML v' + eshiol.j2xml.com_j2xml.version );

eshiol.j2xml.importerModal = function(){
	console.log( 'eshiol.j2xml.importerModal' );

	jQuery( '#j2xmlImportModal iframe' ).contents().find( '#adminForm input[name^=jform], #adminForm select[name^=jform]' ).each( function( index ){
		var input = jQuery( this );
		jQuery( '<input>' ).attr( {
			type: 'hidden',
			id: input.attr( 'id' ),
			name: input.attr( 'name' ),
			value: input.val()
		} ).appendTo( '#adminForm' );
	} );
	window.top.setTimeout( 'window.parent.jQuery(\'#j2xmlImportModal\').modal(\'hide\')', 700 );

   	xmlDoc = jQuery.parseXML( atob( jQuery( '#j2xml_data' ).val() ) );
	$xml = jQuery( xmlDoc );
	root = $xml.find( ':root' )[0];

	$root = jQuery( root );
	header =
		'<?xml version="1.0" encoding="UTF-8" ?>'+"\n"+
		'<j2xml version="' + $root.attr( 'version') + '">' + "\n";
	footer = "\n</j2xml>";

	var nodes = [];

	$root.children( 'user' ).each( function( index ){
		console.log( 'user: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'tag' ).each( function( index ){
		console.log( 'tag: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'category' ).each( function( index ){
		console.log( 'category: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'fieldgroup' ).each( function( index ){
		console.log( 'fieldgroup: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );
	$root.children( 'field' ).each( function( index ){
		console.log( 'field: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children( 'content' ).each( function( index ){
		console.log( 'content: ' + header + eshiol.XMLToString( this ) + footer );
		nodes.push( header + eshiol.XMLToString( this ) + footer );
	} );

	$root.children(  ).each( function( index ){
		if ( ['base', 'user', 'tag', 'category', 'content', 'fieldgroup', 'field'].indexOf( this.nodeName ) == -1 ){
			console.log( 'other: ' + header + eshiol.XMLToString( this ) + footer );
			nodes.push( header + eshiol.XMLToString( this ) + footer );
		}
	} );

	eshiol.removeMessages();

	var options = {
		tot: nodes.length,
		n: 0
	}

	if ( typeof Joomla.getOptions !== 'undefined' ){
		var progressBarContainerClass = Joomla.getOptions( 'progressBarContainerClass', 'progress progress-striped active' );
		var progressBarClass = Joomla.getOptions( 'progressBarClass', 'bar bar-success' );
	}
	else {
		var progressBarContainerClass = 'progress progress-striped active';
		var progressBarClass = 'bar bar-success'
	}

	jQuery( '#system-message-container' ).prepend(
		'<div id="import-progress" class="import-progress">'
		+ '<div class="' + progressBarContainerClass + '">'
		+ '<div id="import-progress-bar" class="' + progressBarClass + '" aria-valuenow="0" aria-valuemin="0" aria-valuemax="' + options.tot + '"></div>'
		+ '</div>'
		+ '<p class="lead">'
		+ '<span id="import-progress-text" class="import-text">'
		+ Joomla.JText._( 'COM_J2XML_IMPORTING' ).replace( '%s', '0%' )
		+ '</span>'
		+ '</p>'
		+ '</div>');

	eshiol.j2xml.importer( nodes, options );
}

/**
 * import items
 *
 * @param {array} nodes the array of item
 * @param {object} options
 *
 * @return  {boolean}  true on success.
 */
eshiol.j2xml.importer = function( nodes, options ){
	console.log('eshiol.j2xml.importer');

	if (nodes.length > 0){
		var progress = Math.floor( 100 * options.n / options.tot );
		jQuery( '#import-progress-bar' ).css( 'width', progress + '%' ).attr( 'aria-valuenow', options.n );
		jQuery( '#import-progress-text' ).html( Joomla.JText._( 'COM_J2XML_IMPORTING' ).replace( '%s', progress + '%' ) );
		options.n++;

		item = nodes.shift();
		// Find the token so that it can be sent in the Ajax request as well
		//var token = Joomla.getOptions('csrf.token', '');
		var token = jQuery( '#installer-token' ).val();

		// Find the action url associated with the form - we need to add the token to this
		var url = 'index.php?option=com_j2xml&task=import.ajax_upload';

		var data = new FormData();
		var blob = new Blob( [new TextEncoder().encode(item)], { type: 'text/xml' } );
		data.append( 'install_package', blob, jQuery( '#j2xml_filename' ).val() );
		data.append( 'installtype', 'upload' );
		data.append( token, 1 );
		jQuery( '#j2xmlImportModal iframe' ).contents()
			.find( '#adminForm input:not(:radio)[name^=jform], #adminForm input:radio[name^=jform]:checked, #adminForm select[name^=jform]' ).each(
			function( index ){
				var input = jQuery( this );
				data.append( input.attr( 'name' ), input.val() );
			}
		);

		document.getElementById( 'system-message-container' ).scrollIntoView();
		JoomlaInstaller.showLoading();

console.log(url);
console.log(data);

		jQuery.ajax( {
			url: url,
			data: data,
			type: 'post',
			processData: false,
			cache: false,
			contentType: false,
			error: function( error ){
				console.log( 'error' );
				console.log( error );
				JoomlaInstaller.hideLoading();
				eshiol.renderMessages( {'error': [error.responseText]} );
				jQuery( '#import-progress' ).remove();
			}
		} ).done( function( res ){
			console.log( 'done' );
			if( res.messages ){
				eshiol.renderMessages( res.messages );
			} else if( res.message ){
				if( res.success ){
					eshiol.renderMessages( {'message': [res.message]} );
				}
				else {
					eshiol.renderMessages( {'error': [res.message]} );
				}
			}

			// Clean message queue and run import again
			jQuery.ajax( {
				url: 'index.php'
			} ).done( function( res ){
				if( nodes.length == 0 ){
					JoomlaInstaller.hideLoading();
				}

				eshiol.j2xml.importer( nodes, options );
			} );
		} );
	}
	else{
		jQuery( '#import-progress' ).remove();
	}
}
