<?php namespace x;

function mention($content) {
    if (!$content || !\is_string($content)) {
        return $content;
    }
    if (false === \strpos($content, '@')) {
        return $content;
    }
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
    $key = \State::get('x.mention.key') ?? 'user';
    foreach ($parts as $v) {
        if (0 === \strpos($v, '<') && '>' === \substr($v, -1)) {
            $out .= $v; // Is a HTML tag
        } else {
            $out .= false !== \strpos($v, '@') ? \preg_replace_callback('/(?<=\W|^)@[a-z\d]+(-[a-z\d]+)*/i', static function ($m) use ($key) {
                if (\is_file($file = \LOT . \D . 'user' . \D . \substr($m[0], 1) . '.page')) {
                    $user = new \User($file);
                    if ('author' === $key) {
                        return '<a href="' . ($user->link ?? $user->url) . '" title="' . $user->user . '">' . $user . '</a>';
                    }
                    if ('user' === $key) {
                        return '<a href="' . ($user->link ?? $user->url) . '" title="' . $user . '">' . $user->user . '</a>';
                    }
                    if (\is_callable($key)) {
                        // Prioritize `$key` as a property name over as a function name. If `$key` is a function name in
                        // the form of a string, make sure that `$user->{$key}` is `null` before treating `$key` as a
                        // function name to be called later
                        if (\is_string($key) && false === \strpos($key, "\\") && null !== ($v = $user->{$key})) {
                            return '<a href="' . ($user->link ?? $user->url) . '" title="' . $user->user . '">' . $v . '</a>';
                        }
                        return \fire($key, [$m[0]], $user);
                    }
                    return '<a href="' . ($user->link ?? $user->url) . '" title="' . $user->user . '">' . ($user->{$key} ?? $user->user) . '</a>';
                }
                return $m[0];
            }, $v) : $v; // Is a plain text
        }
    }
    return $out;
}

\Hook::set([
    'page.content',
    'page.description'
], __NAMESPACE__ . "\\mention", 2);