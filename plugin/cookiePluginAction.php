<?php

/* Importing the actionWrapper class from the yxorP\http namespace. */

use yxorP\action\actionWrapper;
use yxorP\inc\constants;

/* Extending the actionWrapper class. */

class cookiePluginAction extends actionWrapper
{
    /* A constant that is used to prefix the cookie name. */
    public const COOKIE_PREFIX = 'pc';

    /* A method that is called before the request is sent to the server. */

    public static function beforeRequest($match)
    {
        /* Replacing the `_` with `.` in the cookie domain. */
        $_cookieDomain = str_replace(CHAR_UNDER, ".", $match[1]);
        /* Creating an array with the cookie domain, name and value. */
        $cookie = ["cookie_domain" => $_cookieDomain, "cookie_name" => $match[2], "cookie_value" => $match[3]];
        /* Getting the host from the request url. */
        $host = parse_url(constants::get(YXORP_REQUEST)->getUri(), PHP_URL_HOST);
        /* Checking if the host contains the cookie domain. */
        if (str_contains($host, $cookie->cookie_domain)) return $cookie->cookie_name . '=' . $cookie->cookie_value;
    }

    /* Parsing the cookie header and extracting the cookies that are prefixed with `pc_`. */

    public static function headersReceived($response, $set_cookie): void
    {
        /* It removes the `set-cookie` header from the response. */
        $response->headers->remove('set-cookie');
        /* Converting the `$set_cookie` variable to an array and then it is looping through it and calling the `Received`
        method. */
        foreach ((array)$set_cookie as $line) self::Received($line);
    }

    /* Removing the `set-cookie` header from the response and adding a new one with the cookie name prefixed with `pc_`. */

    public static function Received($line): void
    {
        /* Parsing the cookie and then it is setting the cookie header. */
        $cookie = self::parse_cookie($line, constants::get(YXORP_REQUEST)->getUri());
        /* Creating the cookie name. */
        $cookie_name = sprintf("%s_%s__%s", self::COOKIE_PREFIX, str_replace(CHAR_PERIOD, CHAR_UNDER, $cookie[YXORP_DOMAIN]), $cookie['name']);
        /* Setting the cookie header. */
        constants::get(YXORP_RESPONSE)->headers->set('set-cookie', $cookie_name . '=' . $cookie['value'], false);
    }

    /* Removing the `set-cookie` header from the response and adding a new one with the cookie name prefixed with `pc_`. */

    private static function parse_cookie($line, $url): array
    {
        /* Creating an array with the cookie data. */
        $data = array('name' => CHAR_EMPTY_STRING, 'value' => CHAR_EMPTY_STRING, YXORP_DOMAIN => parse_url($url, PHP_URL_HOST), 'path' => CHAR_SLASH, 'expires' => 0, 'secure' => false, 'httpOnly' => true);
        /* Removing the `Set-Cookie2: ` from the cookie header and then it is exploding the cookie header by `;` and then
        it is trimming the cookie header and then it is filtering the cookie header. */
        $pairs = array_filter(array_map('trim', explode(';', preg_replace('/^Set-Cookie2?: /i', CHAR_EMPTY_STRING, trim($line)))));
        /* Looping through the cookie header and then it is exploding the cookie header by `=` and then it is trimming the
        cookie header. */
        foreach ($pairs as $index => $comp) {
            /* Exploding the cookie header by `=` and then it is trimming the cookie header. */
            $parts = explode('=', $comp, 2);
            /* Trimming the cookie header. */
            $key = trim($parts[0]);
            /* Checking if the cookie header is empty and if it is, it is setting the cookie header to `true`. */
            if (count($parts) === 1) $data[$key] = true; else {
                /* Trimming the cookie header. */
                $value = trim($parts[1]);
                /* Checking if the cookie header is the first one and if it is, it is setting the cookie name and value. */
                if ($index === 0) {
                    /* Setting the cookie name. */
                    $data['name'] = $key;
                    /* Setting the cookie value. */
                    $data['value'] = $value;
                    /* Setting the cookie data. */
                } else    $data[$key] = $value;
            }
        }
        /* Returning the cookie data. */
        return $data;
    }

    /* Parsing the cookie and then it is setting the cookie header. */

    public function onBeforeRequest(): void
    {
        /* Creating an empty array. */
        $send_cookies = [];
        /* Parsing the cookie header and extracting the cookies that are prefixed with `pc_`. */
        if (preg_match_all('@pc_(.+?)__(.+?)=([^;]+)@', constants::get(YXORP_REQUEST)->headers->get('cookie'), $matches, PREG_SET_ORDER)) foreach ($matches as $match) $send_cookies[] = self::beforeRequest($match);
        /* Setting the cookie header. */
        if (!is_empty($send_cookies)) constants::get(YXORP_REQUEST)->headers->set('cookie', implode("; ", $send_cookies));
    }

    /* Parsing the cookie and then it is returning an array with the cookie data. */

    public function onHeadersReceived(): void
    {
        /* Getting the response object from the constants class. */
        $response = constants::get(YXORP_RESPONSE);
        /* Getting the `set-cookie` header from the response. */
        $set_cookie = $response->headers->get('set-cookie');
        /* Checking if the `set-cookie` header is set and if it is, it calls the `headersReceived` method. */
        if ($set_cookie) self::headersReceived($response, $set_cookie);
    }
}