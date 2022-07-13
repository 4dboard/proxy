<?php class Lexy
{
    protected $cachePath = false;
    protected $srcinfo;
    protected $compilers = array('extensions', 'comments', 'echos', 'unless', 'default_structures', 'else', 'unescape_echos', 'php_tags');
    protected $extensions = array();
    protected $allowed_calls = array('true', 'false', 'explode', 'implode', 'strtolower', 'strtoupper', 'substr', 'stristr', 'strpos', 'print', 'print_r', 'number_format', 'htmlentities', 'md5', 'strip_tags', 'htmlspecialchars', 'date', 'time', 'mktime', 'round', 'trunc', 'rand', 'ceil', 'floor', 'srand',);

    public static function render($content, $params = array(), $sandbox = false, $srcinfo = null)
    {
        $obj = new self();
        return $obj->execute($content, $params, $sandbox, $srcinfo);
    }

    public function execute($content, $params = array(), $sandbox = false, $srcinfo = null)
    {
        $obj = $this;
        ob_start();
        lexy_eval_with_params($obj, $content, $params, $sandbox, $srcinfo);
        $output = ob_get_clean();
        return $output;
    }

    public static function render_file($file, $params = array(), $sandbox = false)
    {
        $obj = new self();
        return $obj->file($file, $params, $sandbox);
    }

    public function file($file, $params = array(), $sandbox = false)
    {
        if ($this->cachePath) {
            $cachedfile = $this->get_cached_file($file, $sandbox);
            if ($cachedfile) {
                ob_start();
                lexy_include_with_params($cachedfile, $params, $file);
                $output = ob_get_clean();
                return $output;
            }
        }
        return $this->execute(file_get_contents($file), $params, $sandbox, $file);
    }

    protected function get_cached_file($file, $sandbox)
    {
        $cachedfile = $this->cachePath . '/' . basename($file) . '.' . md5($file) . '.lexy.php';
        if (!file_exists($cachedfile)) {
            $cachedfile = $this->cache_file($file, $cachedfile, null, $sandbox);
        }
        if ($cachedfile) {
            $mtime = filemtime($file);
            if (filemtime($cachedfile) != $mtime) {
                $cachedfile = $this->cache_file($file, $cachedfile, $mtime, $sandbox);
            }
            return $cachedfile;
        }
        return false;
    }

    protected function cache_file($file, $cachedfile, $filemtime = null, $sandbox = false)
    {
        if (!$filemtime) {
            $filemtime = filemtime($file);
        }
        if (file_put_contents($cachedfile, $this->parse(file_get_contents($file), $sandbox, $file))) {
            touch($cachedfile, $filemtime);
            return $cachedfile;
        }
        return false;
    }

    public function parse($text, $sandbox = false, $srcinfo = null)
    {
        $this->srcinfo = $srcinfo;
        return $this->compile($text, $sandbox);
    }

    protected function compile($text, $sandbox = false)
    {
        if ($sandbox) {
            $text = str_replace(array("<?", "?>"), array("&lt;?", "?&gt;"), $text);
        }
        foreach ($this->compilers as $compiler) {
            $method = "compile_{$compiler}";
            $text = $this->{$method}($text);
        }
        if ($sandbox) {
            $lines = explode("\n", $text);
            foreach ($lines as $ln => &$line) {
                if ($errors = $this->check_security($line)) {
                    return 'illegal call(s): ' . implode(", ", $errors) . " - on line " . $ln . ($this->srcinfo ? ' (' . $this->srcinfo . ') ' : '');
                }
            }
        }
        if ($errors = $this->check_syntax($text)) {
            if ($this->srcinfo) $errors[] = '(' . $this->srcinfo . ')';
            return implode("\n", $errors);
        }
        return $text;
    }

    protected function check_security($code)
    {
        $tokens = token_get_all($code);
        $errors = array();
        foreach ($tokens as $index => $toc) {
            if (is_array($toc) && isset($toc[0])) {
                switch ($toc[0]) {
                    case T_STRING:
                        if (!in_array(strtolower($toc[1]), $this->allowed_calls)) {
                            $prevtoc = $tokens[$index - 1];
                            if (!isset($prevtoc[1]) || (isset($prevtoc[1]) && $prevtoc[1] != '->')) {
                                $errors[] = $toc[1];
                            }
                        }
                        break;
                    case T_REQUIRE_ONCE:
                    case T_REQUIRE:
                    case T_NEW:
                    case T_RETURN:
                    case T_BREAK:
                    case T_CATCH:
                    case T_CLONE:
                    case T_EXIT:
                    case T_PRINT:
                    case T_GLOBAL:
                    case T_INCLUDE_ONCE:
                    case T_INCLUDE:
                    case T_EVAL:
                    case T_FUNCTION:
                        if (!in_array(strtolower($toc[1]), $this->allowed_calls)) {
                            $errors[] = 'illegal call: ' . $toc[1];
                        }
                        break;
                }
            }
        }
        return count($errors) ? $errors : false;
    }

    protected function check_syntax($code)
    {
        $errors = array();
        ob_start();
        $check = function_exists('eval') ? eval('?>' . '<?php if(0): ?>' . $code . '<?php endif; ?><?php ') : true;
        if ($check === false) {
            $output = ob_get_clean();
            $output = strip_tags($output);
            if (preg_match_all("/on line (\d+)/m", $output, $matches)) {
                foreach ($matches[1] as $m) {
                    $errors[] = "Parse error on line: " . $m;
                }
            } else {
                $errors[] = 'syntax error';
            }
        } else {
            ob_end_clean();
        }
        return count($errors) ? $errors : false;
    }

    public function setCachePath($path)
    {
        $this->cachePath = is_string($path) ? rtrim($path, "/\\") : $path;
    }

    public function allowCall($call)
    {
        $this->allowed_calls[] = $call;
    }

    public function extend($compiler)
    {
        $this->extensions[] = $compiler;
    }

    protected function compile_comments($value)
    {
        return preg_replace('/\{\{\--((.|\s)*?)--\}\}/', "<?php /* $1 */ ?>", $value);
    }

    protected function compile_unescape_echos($value)
    {
        return preg_replace('/\@@(.+?)@@/', '{{$1}}', $value);
    }

    protected function compile_echos($value)
    {
        $value = preg_replace('/\{\{\{(.+?)\}\}\}/', '<?php echo htmlentities($1, ENT_QUOTES, "UTF-8", false); ?>', $value);
        return preg_replace('/\{\{(.+?)\}\}/', '<?php echo $1; ?>', $value);
    }

    protected function compile_default_structures($value)
    {
        $value = preg_replace('/(?(R)\((?:[^\(\)]|(?R))*\)|(?<!\w)(\s*)@(if|foreach|for|while)(\s*(?R)+))/', '$1<?php $2 $3 { ?>', $value);
        $value = preg_replace('/(\s*)@elseif(\s*\(.*\))/', '$1<?php } elseif$2 { ?>', $value);
        $value = preg_replace('/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/', '$1<?php } ?>$3', $value);
        $value = preg_replace('/(\s*)@(end)(\s*)/', '$1<?php } ?>$3', $value);
        return $value;
    }

    protected function compile_else($value)
    {
        $value = preg_replace('/(\s*)@(else)(\s*)/', '$1<?php } else { ?>$3', $value);
        return $value;
    }

    protected function compile_unless($value)
    {
        $value = preg_replace('/(\s*)@unless(\s*\(.*\))/', '$1<?php if (!$2) { ?>', $value);
        $value = str_replace('@endunless', '<?php } ?>', $value);
        return $value;
    }

    protected function compile_php_tags($value)
    {
        return str_replace(array('{%', '%}'), array('<?php', '?>'), $value);
    }

    protected function compile_extensions($value)
    {
        foreach ($this->extensions as &$compiler) {
            $value = call_user_func($compiler, $value);
        }
        return $value;
    }
}

function lexy_eval_with_params($__lexyobj, $__lexycontent, $__lexyparams, $__lexysandbox, $__lexysrcinfo)
{
    extract($__lexyparams);
    $__FILE = $__lexysrcinfo;
    eval('?>' . $__lexyobj->parse($__lexycontent, $__lexysandbox, $__lexysrcinfo) . '<?php ');
}

function lexy_include_with_params($__incfile, $__lexyparams, $__lexysrcinfo)
{
    extract($__lexyparams);
    $__FILE = $__lexysrcinfo;
    include($__incfile);
}