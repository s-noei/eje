<?php

namespace App\Game\Support;

/**
 * PHP Input Filter 1.2.2 (Daniel Morris, GPL) — the HTML/XSS sanitiser the
 * legacy security.php applied to every request value, ported to PHP 8.
 */
class InputFilter
{
    protected array $tagBlacklist = ['applet', 'body', 'bgsound', 'base', 'basefont', 'embed', 'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name', 'object', 'script', 'style', 'title', 'xml'];

    protected array $attrBlacklist = ['action', 'background', 'codebase', 'dynsrc', 'lowsrc'];

    public const DEFAULT_TAGS = ['a', 'b', 'blink', 'blockquote', 'br', 'caption', 'center', 'col', 'colgroup', 'comment',
        'em', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'img', 'li', 'marquee', 'ol', 'p', 'pre', 's',
        'small', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th',
        'thead', 'tr', 'tt', 'u', 'ul'];

    public const DEFAULT_ATTRS = ['abbr', 'align', 'alt', 'axis', 'background', 'behavior', 'bgcolor', 'border', 'bordercolor',
        'bordercolordark', 'bordercolorlight', 'bottompadding', 'cellpadding', 'cellspacing', 'char',
        'charoff', 'cite', 'clear', 'color', 'cols', 'direction', 'face', 'font-family', 'font-size', 'font-weight', 'headers',
        'height', 'href', 'hspace', 'leftpadding', 'loop', 'noshade', 'nowrap', 'point-size', 'rel',
        'rev', 'rightpadding', 'rowspan', 'rules', 'scope', 'scrollamount', 'scrolldelay', 'size',
        'span', 'src', 'start', 'style', 'summary', 'target', 'title', 'toppadding', 'type', 'valign',
        'value', 'vspace', 'width', 'wrap'];

    public function __construct(
        protected array $tagsArray = [],
        protected array $attrArray = [],
        protected int $tagsMethod = 0,
        protected int $attrMethod = 0,
        protected int $xssAuto = 1,
    ) {
        $this->tagsArray = array_map('strtolower', $tagsArray);
        $this->attrArray = array_map('strtolower', $attrArray);
    }

    public static function defaults(): static
    {
        return new static(self::DEFAULT_TAGS, self::DEFAULT_ATTRS, 0, 0, 1);
    }

    public function process(mixed $source): mixed
    {
        if (is_array($source)) {
            foreach ($source as $key => $value) {
                $source[$key] = is_string($value) ? $this->remove($this->decode($value)) : $this->process($value);
            }

            return $source;
        }
        if (is_string($source)) {
            return $this->remove($this->decode($source));
        }

        return $source;
    }

    protected function remove(string $source): string
    {
        $guard = 0;
        while ($source !== ($filtered = $this->filterTags($source)) && $guard++ < 50) {
            $source = $filtered;
        }

        return $source;
    }

    protected function filterTags(string $source): string
    {
        $preTag = '';
        $postTag = $source;
        $tagOpen_start = strpos($source, '<');
        while ($tagOpen_start !== false) {
            $preTag .= substr($postTag, 0, $tagOpen_start);
            $postTag = substr($postTag, $tagOpen_start);
            $fromTagOpen = substr($postTag, 1);
            $tagOpen_end = strpos($fromTagOpen, '>');
            if ($tagOpen_end === false) {
                break;
            }
            $tagOpen_nested = strpos($fromTagOpen, '<');
            if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag .= substr($postTag, 0, ($tagOpen_nested + 1));
                $postTag = substr($postTag, ($tagOpen_nested + 1));
                $tagOpen_start = strpos($postTag, '<');

                continue;
            }
            $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength = strlen($currentTag);
            $tagLeft = $currentTag;
            $attrSet = [];
            $currentSpace = strpos($tagLeft, ' ');
            if (substr($currentTag, 0, 1) === '/') {
                $isCloseTag = true;
                [$tagName] = explode(' ', $currentTag);
                $tagName = substr($tagName, 1);
            } else {
                $isCloseTag = false;
                [$tagName] = explode(' ', $currentTag);
            }
            if ((! preg_match('/^[a-z][a-z0-9]*$/i', $tagName)) || (! $tagName) || ((in_array(strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto))) {
                $postTag = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');

                continue;
            }
            while ($currentSpace !== false) {
                $fromSpace = substr($tagLeft, ($currentSpace + 1));
                $nextSpace = strpos($fromSpace, ' ');
                $openQuotes = strpos($fromSpace, '"');
                $closeQuotes = $openQuotes !== false ? (strpos(substr($fromSpace, ($openQuotes + 1)), '"') + $openQuotes + 1) : false;
                if (strpos($fromSpace, '=') !== false) {
                    if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes + 1)), '"') !== false)) {
                        $attr = substr($fromSpace, 0, ($closeQuotes + 1));
                    } else {
                        $attr = $nextSpace === false ? $fromSpace : substr($fromSpace, 0, $nextSpace);
                    }
                } else {
                    $attr = $nextSpace === false ? $fromSpace : substr($fromSpace, 0, $nextSpace);
                }
                if (! $attr) {
                    $attr = $fromSpace;
                }
                $attrSet[] = $attr;
                $tagLeft = substr($fromSpace, strlen($attr));
                $currentSpace = strpos($tagLeft, ' ');
            }
            $tagFound = in_array(strtolower($tagName), $this->tagsArray);
            if ((! $tagFound && $this->tagsMethod) || ($tagFound && ! $this->tagsMethod)) {
                if (! $isCloseTag) {
                    $attrSet = $this->filterAttr($attrSet);
                    $preTag .= '<'.$tagName;
                    foreach ($attrSet as $a) {
                        $preTag .= ' '.$a;
                    }
                    $preTag .= strpos($fromTagOpen, '</'.$tagName) ? '>' : ' />';
                } else {
                    $preTag .= '</'.$tagName.'>';
                }
            }
            $postTag = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');
        }

        return $preTag.$postTag;
    }

    protected function filterAttr(array $attrSet): array
    {
        $newSet = [];
        foreach ($attrSet as $raw) {
            if (! $raw) {
                continue;
            }
            $attrSubSet = explode('=', trim($raw), 2);
            [$attrSubSet[0]] = explode(' ', $attrSubSet[0]);
            $attrSubSet[1] = $attrSubSet[1] ?? '';
            if ((! preg_match('/^[a-z]*$/i', $attrSubSet[0])) || (($this->xssAuto) && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist)) || (substr($attrSubSet[0], 0, 2) === 'on')))) {
                continue;
            }
            if ($attrSubSet[1]) {
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                $attrSubSet[1] = preg_replace('/\s+/', '', $attrSubSet[1]);
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
                if ((substr($attrSubSet[1], 0, 1) === "'") && (substr($attrSubSet[1], -1) === "'")) {
                    $attrSubSet[1] = substr($attrSubSet[1], 1, -1);
                }
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }
            $lv = strtolower($attrSubSet[1]);
            if ((str_contains($lv, 'expression') && strtolower($attrSubSet[0]) === 'style')
                || str_contains($lv, 'javascript:') || str_contains($lv, 'behaviour:') || str_contains($lv, 'vbscript:')
                || str_contains($lv, 'mocha:') || str_contains($lv, 'livescript:')) {
                continue;
            }
            $attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);
            if ((! $attrFound && $this->attrMethod) || ($attrFound && ! $this->attrMethod)) {
                if ($attrSubSet[1]) {
                    $newSet[] = $attrSubSet[0].'="'.$attrSubSet[1].'"';
                } elseif ($attrSubSet[1] === '0') {
                    $newSet[] = $attrSubSet[0].'="0"';
                } else {
                    $newSet[] = $attrSubSet[0].'="'.$attrSubSet[0].'"';
                }
            }
        }

        return $newSet;
    }

    protected function decode(string $source): string
    {
        $source = preg_replace_callback('/&#(\d+);/m', fn ($m) => mb_chr((int) $m[1], 'UTF-8') ?: '', $source);

        return preg_replace_callback('/&#x([a-f0-9]+);/mi', fn ($m) => mb_chr(hexdec($m[1]), 'UTF-8') ?: '', $source);
    }
}
