<?php

declare(strict_types=1);

namespace Paysera\Bundle\ApiBundle\Service\RoutingLoader;

use Doctrine\Common\Annotations\DocLexer;

/**
 * @internal
 */
class DocblockTagReader
{
    private const CLASS_IDENTIFIERS = [
        DocLexer::T_IDENTIFIER,
        DocLexer::T_TRUE,
        DocLexer::T_FALSE,
        DocLexer::T_NULL,
    ];
    private const VALUE_STARTS = [
        DocLexer::T_OPEN_PARENTHESIS,
        DocLexer::T_COMMA,
        DocLexer::T_EQUALS,
        DocLexer::T_OPEN_CURLY_BRACES,
        DocLexer::T_COLON,
    ];

    /**
     * @var DocLexer|null
     */
    private $lexer;

    /**
     * Tags as Doctrine's DocParser finds them, read with its own DocLexer: from the first tag on, an @ that does not
     * touch the token before it, or that starts a value inside the parentheses of an annotation whose values the
     * parser reads, followed by a name joined across an adjacent backslash; a name followed directly by a dash is not
     * a tag.
     *
     * @param callable(string, string): bool $readsValues whether Doctrine's parser reads the values of a tag, given its
     *     first name and the rest of its name with single backslashes
     * @return array<array{string, string, string, bool, string}> for each tag: its first name, the rest of its name
     *     with single backslashes, that rest as written, whether it is fully qualified, and the rest of a name that
     *     whitespace splits before a backslash
     */
    public function read(string $docComment, callable $readsValues): array
    {
        if (preg_match('/(?<![^ \t*])@/', $docComment, $first, PREG_OFFSET_CAPTURE) !== 1) {
            return [];
        }
        if ($this->lexer === null) {
            $this->lexer = new DocLexer();
        }
        $lexer = $this->lexer;
        $lexer->setInput(trim(substr($docComment, $first[0][1]), '* /'));
        $lexer->moveNext();

        $tags = [];
        $previous = null;
        $depth = 0;
        while ($lexer->lookahead !== null) {
            $token = $lexer->lookahead;
            $lexer->moveNext();
            $type = $this->getType($token);
            if ($type === DocLexer::T_CLOSE_PARENTHESIS && $depth > 0) {
                $depth--;
            } elseif ($type === DocLexer::T_AT && $this->isTagStart($previous, $token, $lexer->lookahead, $depth > 0)) {
                $tag = $this->readTag($lexer);
                $token = $lexer->token;
                if ($tag !== null) {
                    $tags[] = $tag;
                }
                if (
                    $tag !== null
                    && $lexer->lookahead !== null
                    && $this->getType($lexer->lookahead) === DocLexer::T_OPEN_PARENTHESIS
                    && $readsValues($tag[0], $tag[1])
                ) {
                    $depth++;
                    $token = $lexer->lookahead;
                    $lexer->moveNext();
                }
            }
            $previous = $token;
        }

        return $tags;
    }

    /**
     * @param array<string, mixed>|object|null $previous
     * @param array<string, mixed>|object $at
     * @param array<string, mixed>|object|null $next
     */
    private function isTagStart($previous, $at, $next, bool $inValues): bool
    {
        if ($next === null || !in_array($this->getType($next), self::CLASS_IDENTIFIERS, true)) {
            return false;
        }
        if ($inValues && $previous !== null && in_array($this->getType($previous), self::VALUE_STARTS, true)) {
            return true;
        }

        return ($previous === null || !$this->isAdjacent($previous, $at)) && $this->isAdjacent($at, $next);
    }

    /**
     * @return array{string, string, string, bool, string}|null the tag whose name the lexer is at, as read() returns
     *     it, or null when Doctrine's parser discards it
     */
    private function readTag(DocLexer $lexer): ?array
    {
        $lexer->moveNext();
        $name = $this->getValue($lexer->token);
        while (
            $lexer->lookahead !== null
            && $this->getType($lexer->lookahead) === DocLexer::T_NAMESPACE_SEPARATOR
            && $this->isAdjacent($lexer->token, $lexer->lookahead)
        ) {
            $lexer->moveNext();
            if (
                $lexer->lookahead === null
                || !in_array($this->getType($lexer->lookahead), self::CLASS_IDENTIFIERS, true)
            ) {
                break;
            }
            $lexer->moveNext();
            $name .= '\\' . $this->getValue($lexer->token);
        }

        $next = $lexer->lookahead;
        if ($next !== null && $this->getType($next) === DocLexer::T_MINUS && $this->isAdjacent($lexer->token, $next)) {
            return null;
        }
        $continuation = '';
        if ($next !== null && !$this->isAdjacent($lexer->token, $next)) {
            $continuation = $this->readContinuation($next, $lexer->glimpse());
        }

        $rest = ltrim($name, '\\');
        $cut = strpos($rest, '\\');
        $first = $cut === false ? $rest : substr($rest, 0, $cut);
        $written = (string)substr($rest, strlen($first));

        return [
            $first,
            (string)preg_replace('/\\\\+/', '\\\\', $written),
            $written,
            $rest !== $name,
            $continuation,
        ];
    }

    /**
     * @param array<string, mixed>|object $next the token after a tag's name, separated from it by whitespace
     * @param array<string, mixed>|object|null $after the token after that
     * @return string the rest of a name that whitespace splits before a backslash, or an empty string
     */
    private function readContinuation($next, $after): string
    {
        $value = $this->getValue($next);
        if ($this->getType($next) === DocLexer::T_IDENTIFIER && strpos($value, '\\') === 0) {
            return $value;
        }
        if (
            $this->getType($next) === DocLexer::T_NAMESPACE_SEPARATOR
            && $after !== null
            && $after !== []
            && in_array($this->getType($after), self::CLASS_IDENTIFIERS, true)
        ) {
            return '\\' . $this->getValue($after);
        }

        return '';
    }

    /**
     * @param array<string, mixed>|object $token
     * @param array<string, mixed>|object $next
     */
    private function isAdjacent($token, $next): bool
    {
        return $this->getPosition($next) === $this->getPosition($token) + strlen($this->getValue($token));
    }

    /**
     * @param array<string, mixed>|object $token a token of doctrine/lexer 1 (an array) or of 2 and 3 (an object)
     */
    private function getType($token): int
    {
        return (int)(is_array($token) ? $token['type'] : $token->type);
    }

    /**
     * @param array<string, mixed>|object $token a token of doctrine/lexer 1 (an array) or of 2 and 3 (an object)
     */
    private function getValue($token): string
    {
        return (string)(is_array($token) ? $token['value'] : $token->value);
    }

    /**
     * @param array<string, mixed>|object $token a token of doctrine/lexer 1 (an array) or of 2 and 3 (an object)
     */
    private function getPosition($token): int
    {
        return (int)(is_array($token) ? $token['position'] : $token->position);
    }
}
