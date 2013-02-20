<?php

class Plaintext_Syntax_Highlighter {
	public static function start() {
		add_filter( 'syntax_highlight_plaintext', array( __CLASS__, 'syntax_highlight' ) );
	}

	public static function syntax_highlight( $content ) {
		return $content;
	}

	public static function code_row( $row ) {
		if ( $row % 2 == 0 ) {
			return '</td></tr><tr class="code-row code-row-even"><td class="code-row-cell">';
		} else {
			return '</td></tr><tr class="code-row code-row-odd"><td class="code-row-cell">';
		}
	}
}

Plaintext_Syntax_Highlighter::start();