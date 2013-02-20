<?php

class PHP_Syntax_Highlighter extends Plaintext_Syntax_Highlighter {

	public static function start() {
		add_filter( 'syntax_highlight_php', array( __CLASS__, 'syntax_highlight' ) );
	}

	public static function syntax_highlight($content) {
		if ( empty($content) )
			return $content;
		$keywords = array('abstract', 'and', 'array', 'as', 'break', 'case',
			'catch', 'cfunction', 'class', 'clone', 'const', 'continue', 'declare',
			'default', 'do', 'else', 'elseif', 'enddeclare', 'endfor', 'endforeach',
			'endif', 'endswitch', 'endwhile', 'extends', 'final', 'for', 'foreach',
			'function', 'global', 'goto', 'if', 'implements', 'interface',
			'instanceof', 'namespace', 'new	', 'old_function', 'or', 'private',
			'protected', 'public', 'static', 'switch', 'throw', 'try', 'use',
			'var', 'while', 'xor', '__CLASS__', '__DIR__', '__FILE__', '__LINE__',
			'__FUNCTION__', '__METHOD__', '__NAMESPACE__', 'die', 'echo', 'empty',
			'exit', 'eval', 'include', 'include_once', 'isset', 'list', 'require',
			'require_once', 'return', 'print', 'unset');
		$result = '<table class="code-table"><tr class="code-row code-row-odd"><td class="code-row-cell">';
		$buffer = '';
		$state = 'normal';
		$continue = true;
		$row = 1;
		do {
			if ( $continue ) {
				$current = $content[0];
				$content = substr($content, 1);
			}
			$continue = true;

			switch( $state ) {
				case 'normal':
					if ( '/' == $current ) {
						$state = 'slash';
						$buffer = '/';
					} elseif ( "\n" == $current ) {
						$result .= "\n" . self::code_row( ++$row );
					} elseif ( '$' == $current ) {
						$state = 'variable-start';
						$buffer = '$';
					} elseif( preg_match( '/[a-zA-Z]/', $current ) ) {
						$state = 'keyword';
						$buffer = $current;
					} elseif ( '"' == $current ) {
						$state = 'double-string';
						$buffer = $current;
					} elseif ( "'" == $current ) {
						$state = 'single-string';
						$buffer = $current;
					} elseif ( preg_match( '/[0-9]/', $current) ) {
						$state = 'number'; // TODO: What about numbers that begin with a period?
						$buffer = $current;
					} else {
						$result .= $current;
					}
					break;
				case 'slash':
					if ( '/' == $current ) {
						$state = 'comment-line';
						$buffer .= '/';
					} elseif ( '*' == $current ) {
						$state = 'comment-block';
						$buffer .= '*';
					} elseif ( preg_match( '/[0-9\.]/', $current) ) {
						$state = 'number';
						$result .= $buffer;
						$buffer = $current;
					} else {
						$state = 'normal';
						$result .= $buffer;
						$buffer = '';
						$continue = false;
					}
					break;
				case 'comment-line':
					if ( "\n" == $current ) {
						$result .= '<span class="code-comment">' . $buffer . '</span>' . self::code_row( ++$row );
						$buffer = '';
						$state = 'normal';
					} else {
						$buffer .= $current;
					}
					break;
				case 'comment-block':
					if ( '*' == $current ) {
						$state = 'comment-block-star';
					} elseif ( "\n" == $current ) {
						$result .= "<span class=\"code-comment\">{$buffer}</span>" . self::code_row( ++$row );
						$buffer = '';
					} else {
						$buffer .= $current;
					}
					break;
				case 'comment-block-star':
					if ( '/' == $current ) {
						$result .= "<span class=\"code-comment\">{$buffer}*{$current}</span>";
						$buffer = '';
						$state = 'normal';
					} elseif ( '*' == $current ) {
						$buffer .= '*';
					} elseif ("\n" == $current ) {
						$state = 'comment-block';
						$result .= "<span class=\"code-comment\">{$buffer}*</span>" . self::code_row( ++$row );
						$buffer = '';
					} else {
						$state = 'comment-block';
						$buffer .= '*' . $current;
					}
					break;
				case 'number':
					if ( preg_match( '/[0-9\.]/', $current) ) {
						$buffer .= $current;
					} else {
						$result .= "<span class=\"code-number\">{$buffer}</span>{$current}";
						$buffer = '';
						$state = normal;
					}
					break;
				case 'variable-start':
					if ( preg_match( '/[a-zA-Z_]/', $current ) ) {
						$state = 'variable';
						$buffer .= $current;
					} else {
						$state = 'normal';
					}
					break;
				case 'variable':
					if ( preg_match( '/[a-zA-Z0-9_]/', $current ) ) {
						$buffer .= $current;
					} else {
						$result .= '<span class="code-variable">' . $buffer . '</span>';
						$buffer = '';
						$continue = false;
						$state = 'normal';
					}
					break;
				case 'keyword':
					if ( preg_match( '/[a-zA-Z]/', $current) ) {
						$buffer .= $current;
					} else {
						if ( in_array($buffer, $keywords) ) {
							$result .= '<span class="code-keyword">' . $buffer . '</span>';
						} else {
							$result .= $buffer;
						}
						$buffer = '';
						$state = 'normal';
						$continue = false;
					}
					break;
				case 'double-string':
					if ( '"' == $current ) {
						$result .= '<span class="code-string code-double-string">' . $buffer . $current . '</span>';
						$state = 'normal';
					} elseif ( "\n" == $current ) {
						$result .= '<span class="code-string code-double-string">' . $buffer . '</span>' . self::code_row( ++$row );
						$buffer = '';
					} elseif ( "\\" == $current ) {
						$buffer .= "\\";
						$state = 'double-string-slash';
					} else {
						$buffer .= $current;
					}
					break;
				case 'double-string-slash':
					$buffer .= $current;
					$state = 'double-string';
					break;
				case 'single-string':
					if ( "'" == $current ) {
						$result .= '<span class="code-string code-single-string">' . $buffer . $current . '</span>';
						$state = 'normal';
					} elseif ( "\\" == $current ) {
						$buffer .= "\\";
						$state = 'single-string-slash';
					} else {
						$buffer .= $current;
					}
					break;
				case 'single-string-slash':
					$buffer .= $current;
					$state = 'single-string';
					break;
			}
		} while ( !empty($content) || !$continue );
		if ( "\n" == $current ) {
			$result .= "\n" . self::code_row( ++$row );
		}
		return $result . '</td></tr></table>';
	}
}

PHP_Syntax_Highlighter::start();