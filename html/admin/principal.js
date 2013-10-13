
/* ======================================================================================
   @author     Carlos Doral Pérez (http://webartesanal.com)
   @version    0.11
   @copyright  Copyright &copy; 2013 Carlos Doral Pérez, All Rights Reserved
               License: GPLv2 or later
   ====================================================================================== */

//
function cdp_cookies_mensaje( texto, clase )
{
	jQuery( '.cdp-cookies-mensajes' ).removeClass( 'error' ).addClass( clase );
	jQuery( '.cdp-cookies-mensajes' ).html( texto ).fadeIn( 500 ).delay( 2000 ).fadeOut( 500 );
}

//
function cdp_cookies_mensaje_error( texto )
{
	cdp_cookies_mensaje( texto, 'error' );
}

//
function guardar()
{
	//
	var datos = {
		action: 'guardar_config',
		layout: jQuery( '#layout' ).val(),
		posicion: jQuery( '#posicion' ).val(),
		alineacion: jQuery( '#alineacion' ).val(),
		tema: jQuery( '#tema:checked' ).val(),
		enlace_politica: jQuery( '#enlace_politica' ).val(),
		enlace_mas_informacion: jQuery( '#enlace_mas_informacion' ).val(),
		nonce_guardar: info.nonce_guardar
	};

	//
	jQuery.post( ajaxurl, datos, function( resul ) {
		if( resul.ok )
			cdp_cookies_mensaje( resul.txt );
		else
			cdp_cookies_mensaje_error( resul.txt );
	}, 'json' );
}

//
function crear_paginas()
{
	//
	var datos = {
		action: 'crear_paginas',
		nonce_crear_paginas : info.nonce_crear_paginas
	};

	//
	jQuery.post( ajaxurl, datos, function( resul ) {
		if( resul.ok )
		{
			cdp_cookies_mensaje( resul.txt );
			jQuery( '#enlace_mas_informacion' ).val( resul.url_info );
			jQuery( '#enlace_politica' ).val( resul.url_politica );
		}
		else
		{
			cdp_cookies_mensaje_error( resul.txt );
		}
	}, 'json' );
}

//
jQuery( document ).ready( function( $ ) {

	// Ocultar/mostrar instrucciones
	$( '.cdp-cookies-bot-instrucciones' ).click( function() {
		$( '.cdp-cookies-instrucciones' ).toggle();
	} );

	// Radios más fáciles de pinchar
	$( 'form .cdp-cookies-radio' ).click( function() {
		$( this ).find( 'input' ).attr( 'checked', true );
	} );

	// Guardar config
	$( 'a.cdp-cookies-guardar' ).click( function() {
		guardar();
	} );

	// Crear pág. política
	$( 'a.cdp-cookies-crear-politica' ).click( function() {
		crear_paginas();
	} );

	// Ver pág. más info
	$( 'a.cdp-cookies-ver-mas-info' ).click( function() {
		window.open( $( '#enlace_mas_informacion' ).val() );
	} );

	// Ver pág. politica
	$( 'a.cdp-cookies-ver-politica' ).click( function() {
		window.open( $( '#enlace_politica' ).val() );
	} );

	// Vista previa del aviso
	$( 'a.cdp-cookies-vista-previa' ).click( function() {
		window.open( 
			info.siteurl + 
			'?cdp_cookies_vista_previa=1' +
			'&layout=' + $( '#layout' ).val() +
			'&posicion=' + $( '#posicion' ).val() +
			'&alineacion=' + $( '#alineacion' ).val() +
			'&tema=' + $( '#tema:checked' ).val()
		);
	} );

} );