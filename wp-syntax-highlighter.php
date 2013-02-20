<?php
/*
 * Plugin Name: WP Syntax Highlighter
 * Description: Basic server-side syntax highlighting plugin
 * Author: Will Anderson
 * Author URI: http://www.itsananderson.com/
 * Version: 0.1
 */

class WP_Syntax_Highlighter {

	const VERSION = '0.1';

	public static function start() {
		add_shortcode('code', array( __CLASS__, 'code_callback' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	public static function enqueue_styles() {
		wp_enqueue_style( 'wp-syntax-highlighter', plugins_url( 'css/highlight.css', __FILE__ ), self::VERSION );
	}

	// [code lang="php" lines="1"]content[/code]
	public static function code_callback( $atts, $content) {
		// If content is empty, do nothing
		if ( empty($content) ) {
			return $content;
		}
		$content = str_replace('<br />' , '', preg_replace("/\r\n|\r/", '\n', $content));
		$content = str_replace( '<p></p>', '\n\n', $content );
		$content = str_replace( '</p>' , '' , str_replace('<p>', '', $content));
		// Unify and filter content
		if ( $content[0] == "\n" ) {
			$content = substr($content, 1);
		}
		if ( $content[strlen($content) -1] == "\n") {
			$content = substr($content, 0, strlen($content) - 1);
		}
		$atts = shortcode_atts( array(
			'lang' => 'php',
			'show_lines' => '1'
		), $atts );
		$lang = $atts['lang'];
		$show_lines = intval( $atts['show_lines'] );
		$lines = substr_count( $content , "\n" );

		$highlighted = self::syntax_highlight( $content, $lang );

		ob_start();
		include plugin_dir_path( __FILE__ ) . 'views/highlight.php';
		return ob_get_clean();
	}

	public static function syntax_highlight( $content, $language ) {

		require_once plugin_dir_path( __FILE__ ) . 'highlighters/plaintext-highlighter.php';

		$highlighter_path = plugin_dir_path( __FILE__ ) . "highlighters/$language-highlighter.php";

		if ( file_exists( $highlighter_path ) ) {
			require_once( $highlighter_path );

			if ( has_filter( "syntax_highlight_$language" ) ) {
				return apply_filters( "syntax_highlight_$language", $content, $language );
			}
		}

		// Fall back to plain-text highlighter if nothing else is found
		return apply_filters( 'syntax_highlight_plaintext', $content, $language );
	}
}

WP_Syntax_Highlighter::start();
