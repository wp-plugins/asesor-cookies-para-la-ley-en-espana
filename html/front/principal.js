/* ======================================================================================
   @author     Carlos Doral Pérez (http://webartesanal.com)
   @version    0.11
   @copyright  Copyright &copy; 2013 Carlos Doral Pérez, All Rights Reserved
               License: GPLv2 or later
   ====================================================================================== */

//
//
//
jQuery( document ).ready( function( $ )
{
   // Funcionamiento para depuración desde el admin de Wordpress
   if( cdp_cookie.hay_vista_previa() )
   {
      cdp_cookie.mostrar_aviso_vista_previa();
      return;
   }

   // Funcionamiento normal del plugin
   if( cdp_cookie.ya_existe_cookie() )
      return;
   cdp_cookie.poner_cookie();
   cdp_cookie.mostrar_aviso();
} );
