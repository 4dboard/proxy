<?php namespace yxorP\app\lib\http;
/* Importing the namespace `yxorP` into the current namespace. */

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use yxorP;
use yxorP\app\lib\minify\minify;

/* A class that is used to cache data. */

class cache
{
    /* Checking if the cache file is valid and if it is, it is including the cache file. */
    public static function get(?string $key = null): void
    {
        /* Checking if the cache file is valid and if it is, it is including the cache file. */
        if (self::isValid(self::gen($key)['path'])) @include self::gen($key)['path'];
    }

    /* Used to check if the cache file exists. */
    #[Pure] public static function isValid(?string $key = null): bool
    {
        /* Used to check if the cache file exists. */
        return file_exists(self::gen($key)['path']);
    }

    /* A PHPDoc annotation that is used to tell the IDE that the function returns an array with the keys `key` and `path`. */

    #[ArrayShape(['key' => "null|\yxorP\app\lib\http\string", 'path' => "string"])] private static function gen(?string $key): array
    {

        /* Returning an array with the keys `key` and `path`. */
        return ['key' => $key ?: CACHE_KEY, 'path' => ($key) ? PATH_TMP_DIR . $key . FILE_TMP : PATH_TMP_FILE];
    }

    /* Used to get the data from the cache file. */

    public static function fetch(?string $key = null): array
    {
        $GLOB = [];
        /* Checking if the cache file is valid and if it is, it is getting the data from the cache file. */
        include self::gen($key)['path'];
        return $GLOB;
    }

    public static function set($mime, $content, ?string $key = null): void
    {
        self::store($GLOBALS[YXORP_HTTP_HOST], CACHE_KEY_CONTEXT);

        file_put_contents(self::gen($key)['path'], '<?php header("Content-type: ' . $mime . '");' . str_replace([' ', "\n"], '', <<<'EOF'
$f = fopen(__FILE__, 'r');fseek($f, __COMPILER_HALT_OFFSET__);$t = tmpfile();$u = stream_get_meta_data($t)['uri'];fwrite($t, gzinflate(stream_get_contents($f)));include($u);fclose($t); __halt_compiler(); 
EOF
            ) . gzdeflate((new minify())->process($content)) . ';exit(die());');
        exit(die($content));
    }

    public static function store($val, ?string $key = null): void
    {
        /* Used to write the data to the cache file. */
        file_put_contents(self::gen($key)['path'], '<?php $GLOB=' . str_replace(CACHE_FIX, '(object)', var_export($val, true)) . '?>');
    }
}