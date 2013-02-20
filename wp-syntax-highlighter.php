<?php
/*
 * Plugin Name: Syntax Highlighter
 * Description: Awesome syntax highlighting for the masses
 * Author: Will Anderson
 * Author URI: http://www.itsananderson.com/
 * Version: 1000.0
 */

// [code lang="php" lines="1"]content[/code]
function code_callback( $atts, $content) {
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
		$content = substr($content, 0,  strlen($content) - 1);
	}
	extract( shortcode_atts( array(
		'lang' => 'php',
		'lines' => '1'
	), $atts ) );
	$lines = substr_count( $content , "\n" );
	$count = 0;
	$result = "
	<style type=\"text/css\">

	.code {
		border: 1px solid #ccc;
		font-family: 'Courier New', monospace;
		font-size: 10pt;
	}

	.code .lines {
		float: left;
		background: #eee;
		width: 50px;
		border-right: 1px solid gray;
	}

	.code table {
		border-spacing: 0;
		padding: 0;
		margin: 0;
		width: 100%;
	}

	.code table tr {
		padding: 0;
		margin: 0;
	}

	.code table tr td {
		border-spacing: 0;
		margin: 0;
		padding: 0;
	}

	.code .lines tr {
		width: 100%;
	}

	.code .lines .line {
		text-align: right;
		padding: 0px 3px;
	}

	.code .highlighted-code {
		overflow: auto;
	}

	.code-table {
		overflow: auto;
	}

	.code-row-even {
		background-color: #f9f9f9;
	}

	.code-row-odd {
		background-color: #fff;
	}

	.code .code-row-cell {
		padding-left: 10px;
		white-space: pre;
	}

	.code-comment {
		color: green;
	}

	.code-variable {
		color: blue;
	}

	.code-keyword {
		color: purple;
		font-weight: bold;
	}

	.code-string {
		color: orange;
	}

	.code-number {
		color: red;
	}
	</style>
	<div class=\"code code-$lang\"><div style=\"\" class=\"lines\"><table>";

	while ( $count++ <= $lines ) {
		$result .= "<tr><td style=\"\" class=\"line\">$count</td></tr>";
	}
	$result .= "</table></div><div class=\"highlighted-code highlighted-code-$lang\">" . syntax_highlight($content, $lang) . "</div>";

	$result .= '</div>'; //<br style="clear: both; height: 0;" />
	return $result;
}
add_shortcode('code', 'code_callback');

function syntax_highlight( $content, $language ) {
	$new_content = apply_filters( 'syntax_highlight_' . $language, $content);

	// Use plain text styler
	if ( $new_content == $content ) {
		$new_content = syntax_highlight_plain( $content );
	}
	return $new_content;
}

add_filter( 'syntax_highlight_php', 'syntax_highlight_php' );
function syntax_highlight_php($content) {
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
					$result .= "\n" . code_row( ++$row );
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
					$result .= '<span class="code-comment">' . $buffer . '</span>' . code_row( ++$row );
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
					$result .= "<span class=\"code-comment\">{$buffer}</span>" . code_row( ++$row );
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
					$result .= "<span class=\"code-comment\">{$buffer}*</span>" . code_row( ++$row );
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
					$result .= '<span class="code-string code-double-string">' . $buffer . '</span>' . code_row( ++$row );
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
		$result .= "\n" . code_row( ++$row );
	}
	return $result . '</td></tr></table>';
}

function syntax_highlight_plain( $content ) {
	return $content;
}

function code_row( $row ) {
	if ( $row % 2 == 0 ) {
		return '</td></tr><tr class="code-row code-row-even"><td class="code-row-cell">';
	} else {
		return '</td></tr><tr class="code-row code-row-odd"><td class="code-row-cell">';
	}
}
