<?php

/* ======================================================================================
   @author     Carlos Doral Pérez (http://webartesanal.com)
   @version    0.11
   @copyright  Copyright &copy; 2013 Carlos Doral Pérez, All Rights Reserved
               License: GPLv2 or later
   ====================================================================================== */

/**
 *
 */
class cdp_cookies
{
	/**
	 *
	 */
	static function ejecutar()
	{
		//
		// Plugin no puede ser ejecutado directamente
		//
		if( !( function_exists( 'add_action' ) && defined( 'ABSPATH' ) ) )
			throw new cdp_cookies_error( 'Este plugin no puede ser llamado directamente' );
		
		//
		// Registro eventos front
		//
		add_action( 'wp_ajax_traer_aviso', array( __CLASS__, 'ajax_traer_aviso' ) );
		add_action( 'wp_ajax_nopriv_traer_aviso', array( __CLASS__, 'ajax_traer_aviso' ) );
		add_action( 'wp_ajax_traer_aviso_get', array( __CLASS__, 'ajax_traer_aviso_get' ) );
		add_action( 'wp_ajax_nopriv_traer_aviso_get', array( __CLASS__, 'ajax_traer_aviso_get' ) );
		
		//
		// Ejecutando Admin
		//
		if( is_admin() )
		{
			add_action( 'admin_menu', array( __CLASS__, 'crear_menu_admin' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'cargar_archivos_admin' ) );
			add_action( 'wp_ajax_guardar_config', array( __CLASS__, 'ajax_guardar_config' ) );			
			add_action( 'wp_ajax_crear_paginas', array( __CLASS__, 'ajax_crear_paginas' ) );			
			return;
		}
		
		//
		// Ejecutando front
		//
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'cargar_archivos_front' ) );
	}
	
	/**
	 *
	 */
	static function cargar_archivos_front()
	{
		wp_enqueue_style( 'front/estilos', CDP_COOKIES_URL_HTML . 'front/estilos.css', false );
		wp_enqueue_script( 'front/cookie', CDP_COOKIES_URL_HTML . 'front/_jquery.kookie.js', array( 'jquery' ) );
		wp_enqueue_script( 'front/lib', CDP_COOKIES_URL_HTML . 'front/lib.js', array( 'jquery' ) );
		wp_enqueue_script( 'front/principal', CDP_COOKIES_URL_HTML . 'front/principal.js', array( 'jquery' ) );
		wp_localize_script
		( 
			'front/principal', 
			'info',
			array
			(
				'url_plugin' => CDP_COOKIES_URL_RAIZ . 'plugin.php',
				'url_admin_ajax' => admin_url() . 'admin-ajax.php'
			) 
		);
	}

	/**
	 *
	 */
	static function ajax_traer_aviso()
	{
		//
		// Posicionamiento en ventana o página
		//
		$class = '';
		if( self::parametro( 'layout' ) == 'ventana' )
			$class .= ' cdp-cookies-layout-ventana';
		else
			$class .= ' cdp-cookies-layout-pagina';
		
		//
		// Posición: superior, inferior
		//
		$class .= ' cdp-cookies-pos-' . self::parametro( 'posicion' );

		//
		// Alineación de los textos
		//
		if( self::parametro( 'alineacion' ) == 'izq' )
			$class .= ' cdp-cookies-textos-izq';

		//
		// Tema de color
		//
		$class .= ' cdp-cookies-tema-' . self::parametro( 'tema' );

		//
		// Obtengo el archivo con el texto
		//
		$html = file_get_contents( CDP_COOKIES_DIR_HTML . 'front/aviso.html' );
		$html = str_replace( '{class}', $class, $html );
		$html = str_replace( '{enlace_politica}', self::parametro( 'enlace_politica' ), $html );
		echo 
			json_encode
			( 
				array
				( 
					'html' => $html, 
					'posicion' => self::parametro( 'posicion' ),
					'layout' => self::parametro( 'layout' )
				)
			);
		exit;
	}
	
	/**
	 *
	 */
	static function ajax_crear_paginas()
	{
		try
		{
			//
			self::comprobar_usuario_admin();
				
			//
			if( !wp_verify_nonce( cdp_cookies_input::post( 'nonce_crear_paginas' ), 'crear_paginas' ) )
				throw new cdp_cookies_error_nonce();

			// Pág. mas info
			$pag_info = new cdp_cookies_pagina();
			$pag_info->titulo = 'Más información sobre las cookies';
			$pag_info->html = file_get_contents( CDP_COOKIES_DIR_HTML . 'front/mas-informacion.html' );
			if( !$pag_info->crear() )
				throw new cdp_cookies_error( $pag_info->mensaje );
			
			// importante! Guardo la url de la página info que será usada por la política
			self::parametro( 'enlace_mas_informacion', $pag_info->url );
			
			// Pág. política
			$pag_pol = new cdp_cookies_pagina();
			$pag_pol->titulo = 'Política de cookies';
			$pag_pol->html =
				str_replace
				(
					'{enlace_mas_informacion}',
					self::parametro( 'enlace_mas_informacion' ),
					file_get_contents( CDP_COOKIES_DIR_HTML . 'front/politica.html' )
				);
			if( !$pag_pol->crear() )
				throw new cdp_cookies_error( $pag_pol->mensaje );

			// Todo ok!
			$resul = array( 'ok' => true, 'url_info' => $pag_info->url, 'url_politica' => $pag_pol->url );
			if( $pag_pol->ya_existia || $pag_info->ya_existia )
				$resul['txt'] = 'Alguna de las página ya existía y no ha sido necesario crearla';
			else
				$resul['txt'] = 'Páginas creadas correctamente';
			echo json_encode( $resul );
		}
		catch( Exception $e )
		{
			cdp_cookies_log::pon( $e );
			echo json_encode( array( 'ok' => false, 'txt' => $e->getMessage() ) );
		}
		exit;
	}

	/**
	 *
	 */
	static function ajax_guardar_config()
	{
		cdp_cookies_log::pon( "paso1" );
		try
		{
		cdp_cookies_log::pon( "paso2" );
			//
			self::comprobar_usuario_admin();
			cdp_cookies_log::pon( "paso3" );
				
			//
			if( !wp_verify_nonce( cdp_cookies_input::post( 'nonce_guardar' ), 'guardar' ) )
				throw new cdp_cookies_error_nonce();

			//
			cdp_cookies_input::validar_array( 'layout', array( 'ventana', 'pagina' ) );
			cdp_cookies_input::validar_array( 'posicion', array( 'superior', 'inferior' ) );
			cdp_cookies_input::validar_array( 'alineacion', array( 'izq', 'cen' ) );
			cdp_cookies_input::validar_array( 'tema', array( 'gris', 'blanco', 'azul', 'verde', 'rojo' ) );
			cdp_cookies_input::validar_url( 'enlace_politica' );
			cdp_cookies_input::validar_url( 'enlace_mas_informacion' );
								
			//
			self::parametro( 'layout', cdp_cookies_input::post( 'layout' ) );
			self::parametro( 'posicion', cdp_cookies_input::post( 'posicion' ) );
			self::parametro( 'alineacion', cdp_cookies_input::post( 'alineacion' ) );
			self::parametro( 'tema', cdp_cookies_input::post( 'tema' ) );
			self::parametro( 'enlace_politica', cdp_cookies_input::post( 'enlace_politica' ) );
			self::parametro( 'enlace_mas_informacion', cdp_cookies_input::post( 'enlace_mas_informacion' ) );
	
			//
			echo json_encode( array( 'ok' => true, 'txt' => 'Configuración guardada correctamente' ) );
		}
		catch( Exception $e )
		{
			cdp_cookies_log::pon( $e );
			echo json_encode( array( 'ok' => false, 'txt' => $e->getMessage() ) );
		}
		exit;
	}

	/**
	 *
	 */
	static function parametro( $nombre, $valor = null )
	{
		//
		$vdef =
			array
			(
				'layout' => 'ventana',
				'posicion' => 'superior',
				'alineacion' => 'izq',
				'tema' => 'gris',
				'enlace_politica' => '#',
				'enlace_mas_informacion' => '#'
			);
		if( !key_exists( $nombre, $vdef ) )
			throw new cdp_cookies_error( sprintf( "Parámetro desconocido: %s", $nombre ) );
	
		// Devuelvo valor
		if( $valor === null )
		{
			// Hago una excepción si estoy mostrando el aviso en vista previa
			if( cdp_cookies_input::post( 'cdp_cookies_vista_previa' ) )
				if( ( $v = cdp_cookies_input::post( $nombre ) ) )
				{
					// Antes de devolver el valor me aseguro que soy el usuario administrador
					try
					{
						self::comprobar_usuario_admin();
						return $v;
					}
					catch( cdp_cookies_error $e )
					{
					}
				}
			return get_option( 'cdp_cookies_' . $nombre, $vdef[$nombre] );
		}
	
		// Lo almaceno
		update_option( 'cdp_cookies_' . $nombre, $valor );
	}
	
	/**
	 *
	 */
	static function cargar_archivos_admin()
	{
		wp_enqueue_style( 'admin/estilos', CDP_COOKIES_URL_HTML . 'admin/estilos.css', false );
		wp_register_script( 'admin/principal', CDP_COOKIES_URL_HTML . 'admin/principal.js', array( 'jquery' ) );
		wp_enqueue_script( 'admin/principal' );
		wp_localize_script(
			'admin/principal',
			'info',
			array
			(
				'nonce_guardar' => wp_create_nonce( 'guardar' ),
				'nonce_crear_paginas' => wp_create_nonce( 'crear_paginas' ),
				'siteurl' => site_url()
			) 
		);
	}
	
	/**
	 *
	 */
	static function comprobar_usuario_admin()
	{
		if( !current_user_can( 'manage_options' ) )
			throw new cdp_cookies_error( 'No tiene privilegios para acceder a esta página' );
	}
	
	/**
	 *
	 */
	static function crear_menu_admin()
	{
		//
		self::comprobar_usuario_admin();
	
		//
		// Página configuración que cuelgue de Herramientas
		//
		add_submenu_page
		(
			'tools.php',
			'Asesor de cookies',
			'Asesor de cookies',
			'manage_options',
			'cdp_cookies',
			array( __CLASS__, 'pag_configuracion' )
		);
	}	

	/**
	 *
	 */
	static function pag_configuracion()
	{
		require_once CDP_COOKIES_DIR_HTML . 'admin/principal.html';
	}
}

/**
 *
 */
class __cdp_cookies
{
	/**
	 *
	 */
	static function ejecutar()
	{
		//
		// Plugin no puede ser ejecutado directamente
		//
		if( !( function_exists( 'add_action' ) && defined( 'ABSPATH' ) ) )
			throw new cdp_cookies_error( 'Este plugin no puede ser llamado directamente' );
		
		//
		// Para que funcionen correctamente las cookies
		//
		add_action( 'init', array( __CLASS__, 'iniciar_sesion' ) );
		
		//
		// Compruebo si estoy en el admin o en el front
		//
		if( is_admin() )
		{
			//
			// Estoy en el área administración
			//
			add_action( 'admin_menu', array( __CLASS__, 'crear_menu_admin' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'cargar_archivos_admin' ) );
			add_action( 'wp_ajax_guardar_config', array( __CLASS__, 'ajax_guardar_config' ) );			
			return;
		}
		
		//
		// Estoy en el front de la web
		//
		
		//
		// ojo! para debug
		//
		if( 0 )
			self::eliminar_cookie();

		//
		// Shortcode para mostrar la política de privacidad completa
		//
		add_shortcode( 'mostrar_politica_cookies', array( __CLASS__, 'dibujar_politica' ) );
		
		//
		// Shortcode para mostrar información adicional sobre las cookies
		//
		add_shortcode( 'mostrar_mas_info_cookies', array( __CLASS__, 'dibujar_mas_informacion' ) );

		//
		// Vista previa de cookies
		//
		if( cdp_cookies_input::get( 'cdp_cookies_vista_previa' ) )
		{
			add_action( 'wp_footer', array( __CLASS__, 'dibujar_aviso' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'cargar_archivos_front' ) );
			return;
		}
		
		//
		// Si ya se ha mostrado el aviso
		//
		if( self::hay_cookie_guardada() )
			return;
		
		//
		// Activo eventos front
		//
		add_action( 'init', array( __CLASS__, 'guardar_cookie' ) );
		add_action( 'wp_footer', array( __CLASS__, 'dibujar_aviso' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'cargar_archivos_front' ) );
	}

	/**
	 * 
	 */
	static function iniciar_sesion()
	{
		if( !session_id() )
			session_start();
	}
	
	/**
	 *
	 */
	static function hay_cookie_guardada()
	{
		return 
			isset( $_COOKIE ) && 
			isset( $_COOKIE['cdp_cookies_wordpress_plugin'] ) &&
			$_COOKIE['cdp_cookies_wordpress_plugin'] == 1;
	}
	
	/**
	 *
	 */
	static function guardar_cookie()
	{
		setcookie
		( 
			'cdp_cookies_wordpress_plugin', 
			'1', 
			time() + 60 * 60 * 24 * 30 * 12 * 100
		);
	}

	/**
	 *
	 */
	private static function eliminar_cookie()
	{
		setcookie
		(
			'cdp_cookies_wordpress_plugin',
			'0',
			time() - 10000
		);
	}
	
	/**
	 * 
	 */
	static function parametro( $nombre, $valor = null )
	{
		//
		$vdef = 
			array( 
				'layout' => 'ventana',
				'posicion' => 'superior',
				'alineacion' => 'izq',
				'tema' => 'gris',
				'enlace_politica' => '#',
				'enlace_mas_informacion' => '#'
			);
		if( !key_exists( $nombre, $vdef ) )
			throw new cdp_cookies_error( sprintf( "Parámetro desconocido: %s", $nombre ) );
				
		// Devuelvo valor
		if( $valor === null )
		{
			// Hago una excepción si estoy mostrando el aviso en vista previa
			if( cdp_cookies_input::get( 'cdp_cookies_vista_previa' ) )
				if( ( $v = cdp_cookies_input::get( $nombre ) ) )
				{
					// Antes de devolver el valor me aseguro que soy el usuario administrador
					try
					{
						self::comprobar_usuario_admin();
						return $v;
					}
					catch( cdp_cookies_error $e )
					{					
					}
				}
			return get_option( 'cdp_cookies_' . $nombre, $vdef[$nombre] );
		}

		// Lo almaceno
		update_option( 'cdp_cookies_' . $nombre, $valor );
	}
	
	/**
	 *
	 */
	static function comprobar_usuario_admin()
	{
		if( !current_user_can( 'manage_options' ) )
			throw new cdp_cookies_error( 'No tiene privilegios para acceder a esta página' );
	}

	/**
	 *
	 */
	static function crear_menu_admin()
	{
		//
		self::comprobar_usuario_admin();
		
		//
		// Página configuración que cuelgue de Herramientas
		//
		add_submenu_page
		( 
			'tools.php', 
			'Asesor de cookies',
			'Asesor de cookies', 
			'manage_options', 
			'cdp_cookies', 
			array( __CLASS__, 'pag_configuracion' )
		);
	}
		
	/**
	 *
	 */
	static function cargar_archivos_front()
	{
		wp_enqueue_style( 'estilos', CDP_COOKIES_URL_HTML . 'estilos.css', false );
		wp_enqueue_script( 'main', CDP_COOKIES_URL_HTML . 'main.js', array( 'jquery' ) );
	}

	/**
	 *
	 */
	static function cargar_archivos_admin()
	{
		wp_enqueue_style( 'estilos-admin', CDP_COOKIES_URL_HTML . 'estilos-admin.css', false );
		wp_register_script( 'main-admin', CDP_COOKIES_URL_HTML . 'main-admin.js', array( 'jquery' ) );
		wp_enqueue_script( 'main-admin' );
		wp_localize_script( 
			'main-admin', 
			'info', 
			array( 
				'url_admin' => CDP_COOKIES_URL_RAIZ . 'plugin.php', 
				'siteurl' => site_url(),
				'nonce_guardar' => wp_create_nonce( 'guardar' )
			) );
	}
	
	/**
	 * 
	 */
	static function dibujar_aviso()
	{		
		//
		// Posicionamiento en ventana o página
		//
		$class = '';
		if( self::parametro( 'layout' ) == 'ventana' )
			$class .= ' cdp-cookies-layout-ventana';
		else
			$class .= ' cdp-cookies-layout-pagina';
		
		//
		// Posición: superior, inferior
		//
		$class .= ' cdp-cookies-pos-' . self::parametro( 'posicion' );

		//
		// Alineación de los textos
		//
		if( self::parametro( 'alineacion' ) == 'izq' )
			$class .= ' cdp-cookies-textos-izq';

		//
		// Tema de color
		//
		$class .= ' cdp-cookies-tema-' . self::parametro( 'tema' );
		
		//
		// Obtengo el archivo con el texto
		//
		$html = file_get_contents( CDP_COOKIES_DIR_HTML . 'aviso.html' );
		$html = str_replace( '{class}', $class, $html );
		$html = str_replace( '{enlace_politica}', self::parametro( 'enlace_politica' ), $html );
		echo $html;
	}
	
	/**
	 *
	 */
	static function dibujar_politica()
	{
		$html = file_get_contents( CDP_COOKIES_DIR_HTML . 'politica.html' );
		$html = str_replace( '{enlace_mas_informacion}', self::parametro( 'enlace_mas_informacion' ), $html );
		echo $html;
	}

	/**
	 *
	 */
	static function dibujar_mas_informacion()
	{
		$html = file_get_contents( CDP_COOKIES_DIR_HTML . 'mas-informacion.html' );
		echo $html;
	}
	
	/**
	 *
	 */
	static function pag_configuracion()
	{
		require_once CDP_COOKIES_DIR_HTML . 'admin.html';
	}
	
	/**
	 *
	 */
	static function ajax_guardar_config()
	{
		self::comprobar_usuario_admin();
		try
		{
			//
			if( !wp_verify_nonce( cdp_cookies_input::post( 'nonce_guardar' ), 'guardar' ) )
				throw new cdp_cookies_error( "Se ha producido un error de seguridad en este plugin" );
			
			//
			cdp_cookies_input::validar_array( 'layout', array( 'ventana', 'pagina' ) );
			cdp_cookies_input::validar_array( 'posicion', array( 'superior', 'inferior' ) );
			cdp_cookies_input::validar_array( 'alineacion', array( 'izq', 'cen' ) );
			cdp_cookies_input::validar_array( 'tema', array( 'gris', 'blanco', 'azul', 'verde', 'rojo' ) );
			cdp_cookies_input::validar_url( 'enlace_politica' );
			cdp_cookies_input::validar_url( 'enlace_mas_informacion' );
			
			//
			self::parametro( 'layout', cdp_cookies_input::post( 'layout' ) );
			self::parametro( 'posicion', cdp_cookies_input::post( 'posicion' ) );
			self::parametro( 'alineacion', cdp_cookies_input::post( 'alineacion' ) );
			self::parametro( 'tema', cdp_cookies_input::post( 'tema' ) );
			self::parametro( 'enlace_politica', cdp_cookies_input::post( 'enlace_politica' ) );
			self::parametro( 'enlace_mas_informacion', cdp_cookies_input::post( 'enlace_mas_informacion' ) );
				
			//
			echo json_encode( array( 'ok' => true, 'txt' => 'Configuración guardada correctamente' ) );
		}
		catch( Exception $e )
		{
			cdp_cookies_log::pon( $e );
			echo json_encode( array( 'ok' => false, 'txt' => $e->getMessage() ) );
		}
		exit;
	}
	
}

/**
 *
 */
class cdp_cookies_pagina
{
	/**
	 * entrada
	 */
	public $titulo, $html;

	/**
	 * salida
	 */
	public $ya_existia, $url, $ok, $mensaje;
	
	/**
	 * 
	 */
	function crear()
	{
		// Validación del título
		if( !$this->titulo )
		{
			$this->ok = false;
			$this->mensaje = 'Falta el título de la página';
			return false;
		}
		
		// Compruebo si ya existe
		if( $pag = get_page_by_title( $this->titulo ) )
		{
			// Si está en la papelera...
			if( $pag->post_status == 'trash' )
			{
				$this->ok = false;
				$this->mensaje = 'Alguna de las páginas está en la papelera, debe eliminarla primero';
				return false;
			}

			// Todo bien...
			$this->ok = true;
			$this->ya_existia = true;
			$this->url = get_permalink( $pag );
			return true;
		}

		// Validación del html
		if( !$this->html )
		{
			$this->ok = false;
			$this->mensaje = 'Falta el html de la página';
			return false;
		}
		
		// Me dispongo a crear la página insertando el post en BD
		$p = array();
		$p['post_title'] = $this->titulo;
		$p['post_content'] = $this->html;
		$p['post_status'] = 'publish';
		$p['post_type'] = 'page';
		$p['comment_status'] = 'closed';
		$p['ping_status'] = 'closed';
		$p['post_category'] = array( 1 );
		if( !( $id = wp_insert_post( $p ) ) )
		{
			$this->ok = false;
			$this->mensaje = "No es posible crear la página";
			return false;
		}
		
		// Se ha creado la página correctamente
		$this->ok = true;
		$this->ya_existia = false;
		$this->url = get_permalink( get_post( $id ) );
		return true;
	}	
}

?>