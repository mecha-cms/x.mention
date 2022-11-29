<?php namespace x;

function mention($content) {
    if ($content && \is_string($content) && false !== \strpos($content, '@')) {
        $out = "";
        $parts = \preg_split('/(<!--[\s\S]*?-->|' . (static function ($tags) {
            foreach ($tags as &$tag) {
                $tag = '<' . $tag . '(?:\s(?:"[^"]*"|\'[^\']*\'|[^\/>])*)?>[\s\S]*?<\/' . $tag . '>';
            }
            unset($tag);
            return \implode('|', $tags);
        })([
            'pre',
            'code', // Must come after `pre`
            'kbd',
            'script',
            'style',
            'textarea'
        ]) . '|<[^>]+>)/i', $content, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $v) {
            if (0 === \strpos($v, '<') && '>' === \substr($v, -1)) {
                $out .= $v; // Is a HTML tag
            } else {
                $out .= false !== \strpos($v, '@') ? \preg_replace_callback('/(?<=\W|^)@[a-z\d-]+/', static function ($m) {
                    if (\is_file($file = \LOT . \D . 'user' . \D . \substr($m[0], 1) . '.page')) {
                        $user = new \User($file);
                        return '<a href="' . $user->url . '" target="_blank" title="' . $user->user . '">' . $user . '</a>';
                    }
                    return $m[0];
                }, $v) : $v; // Is a plain text
            }
        }
        return $out;
    }
    return $content;
}

\Hook::set([
    'page.content',
    'page.description',
    'page.title'
], __NAMESPACE__ . "\\mention", 2);