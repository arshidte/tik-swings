<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('old')) {
    /**
     * Framework old(), but returning the raw value by default.
     *
     * Every view escapes at output (`esc(old(...))`), so CodeIgniter's default
     * of HTML-escaping here double-escaped repopulated fields: after a failed
     * save, "Tom & Jerry" came back as "Tom &amp; Jerry" and was saved that way.
     * Always escape the result when echoing it.
     *
     * @param string|null                                $default
     * @param 'attr'|'css'|'html'|'js'|'raw'|'url'|false $escape
     *
     * @return array|string|null
     */
    function old(string $key, $default = null, $escape = false)
    {
        if (session_status() === PHP_SESSION_NONE && ENVIRONMENT !== 'testing') {
            session(); // @codeCoverageIgnore
        }

        $value = service('request')->getOldInput($key);

        if ($value === null) {
            return $default;
        }

        return $escape === false ? $value : esc($value, $escape);
    }
}
