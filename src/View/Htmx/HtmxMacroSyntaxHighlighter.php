<?php

namespace Rose\View\Htmx;

class HtmxMacroSyntaxHighlighter
{
	private static $tokenMap = [
		T_OPEN_TAG => 'php-tag',
		T_CLOSE_TAG => 'php-tag',
		T_VARIABLE => 'variable',
		T_STRING => 'string-literal',
		T_CONSTANT_ENCAPSED_STRING => 'string-literal',
		T_LNUMBER => 'number',
		T_DNUMBER => 'number',
		T_COMMENT => 'comment',
		T_DOC_COMMENT => 'comment',
		T_CLASS => 'keyword',
		T_FUNCTION => 'keyword',
		T_PUBLIC => 'keyword',
		T_PRIVATE => 'keyword',
		T_PROTECTED => 'keyword',
		T_STATIC => 'keyword',
		T_RETURN => 'keyword',
		T_IF => 'keyword',
		T_ELSE => 'keyword',
		T_ELSEIF => 'keyword',
		T_WHILE => 'keyword',
		T_FOR => 'keyword',
		T_FOREACH => 'keyword',
		T_NEW => 'keyword',
		T_EXTENDS => 'keyword',
		T_IMPLEMENTS => 'keyword',
		T_NAMESPACE => 'keyword',
		T_USE => 'keyword',
		T_TRY => 'keyword',
		T_CATCH => 'keyword',
		T_FINALLY => 'keyword',
		T_THROW => 'keyword',
		T_INSTANCEOF => 'keyword',
		T_ABSTRACT => 'keyword',
		T_FINAL => 'keyword',
		T_CONST => 'keyword',
		T_ARRAY => 'keyword',
		T_ECHO => 'keyword',
		T_PRINT => 'keyword',
		T_REQUIRE => 'keyword',
		T_REQUIRE_ONCE => 'keyword',
		T_INCLUDE => 'keyword',
		T_INCLUDE_ONCE => 'keyword',
		T_OBJECT_OPERATOR => 'operator',
		T_DOUBLE_COLON => 'operator',
		T_DOUBLE_ARROW => 'operator',
		T_BOOLEAN_AND => 'operator',
		T_BOOLEAN_OR => 'operator',
		T_IS_EQUAL => 'operator',
		T_IS_NOT_EQUAL => 'operator',
		T_IS_IDENTICAL => 'operator',
		T_IS_NOT_IDENTICAL => 'operator',
		T_IS_SMALLER_OR_EQUAL => 'operator',
		T_IS_GREATER_OR_EQUAL => 'operator',
		T_SPACESHIP => 'operator',
		T_NULLSAFE_OBJECT_OPERATOR => 'operator',
		T_WHITESPACE => 'whitespace',
	];

	public static function highlight($code)
	{
		// Ensure code starts with PHP opening tag
		if (!str_starts_with(trim($code), '<?php')) {
			$code = "<?php\n" . $code;
			$removeTag = true;
		}

		$tokens = token_get_all($code);
		$output = '';

		foreach ($tokens as $token) {
			if (is_array($token)) {
				$tokenType = $token[0];
				$tokenValue = $token[1];

				// Skip the opening PHP tag if we added it
				if (isset($removeTag) && $tokenType === T_OPEN_TAG) {
					$removeTag = false;
					continue;
				}

				$cssClass = self::$tokenMap[$tokenType] ?? null;

				if ($cssClass) {
					$output .= '<span class="' . $cssClass . '">' . htmlspecialchars($tokenValue) . '</span>';
				} else {
					$output .= htmlspecialchars($tokenValue);
				}
			} else {
				// Single character tokens (operators, brackets, etc.)
				$cssClass = self::getOperatorClass($token);
				if ($cssClass) {
					$output .= '<span class="' . $cssClass . '">' . htmlspecialchars($token) . '</span>';
				} else {
					$output .= htmlspecialchars($token);
				}
			}
		}

		return $output;
	}

	private static function getOperatorClass($char)
	{
		$operators = ['+', '-', '*', '/', '%', '=', '!', '<', '>', '&', '|', '^', '~', '?', ':'];
		$brackets = ['(', ')', '[', ']', '{', '}'];

		if (in_array($char, $operators)) {
			return 'operator';
		}

		if (in_array($char, $brackets)) {
			return 'bracket';
		}

		if ($char === ';') {
			return 'semicolon';
		}

		return null;
	}
}
