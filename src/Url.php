<?php
namespace Verba;

class Url
{
    public $proto;
    public $user;
    public $password;
    public $host;
    public $port;
    public $path;
    public $file;
    public $anchor;
    public $params = array();
    public $trimEmptyPath = false;

    static public function curProto()
    {
        return SYS_REQUEST_PROTO;
    }

    static public function curHost()
    {
        if (isset($_SERVER['HTTP_HOST'])) return $_SERVER['HTTP_HOST'];
        return 'localhost';
    }

    static public function curPath()
    {
        if (isset($_SERVER['PHP_SELF']) and preg_match('<^([/\w\.-]*)/>', $_SERVER['PHP_SELF'], $_))
            return $_[1];
        return '';
    }

    static public function curFile()
    {
        if (isset($_SERVER['PHP_SELF']) and preg_match('<[^/]*$>', $_SERVER['PHP_SELF'], $_))
            return $_[0];
        return '';
    }

    static public function unWWW($host)
    {
        if (!empty($host) and preg_match('<^(?:www\.)(.*)$>', $host, $_)) return $_[1];
        return $host;
    }

    public function __construct($url = null, $full = null)
    {
        if (isset($url)) {
            $this->parse($url, $full);
        }
    }

    public function parse($url, $full = null)
    {
        $this->proto = $this->user = $this->password = $this->host = $this->port
            = $this->path = $this->file = $this->anchor = null;
        $this->params = array();
        $url = (string)$url;
        if (!isset($full)) $full = $url[0] !== '/';
        if ($full) {
            if (preg_match(<<<__END__
<^
  (?:(\w+)://)?      # proto[1]
  (?:
    ([\w\.-]+)      # user[2]
    (?::([^@]*))?    # password[3]
  @)?
  ([\p{L}\d\.-]+)        # host[4]
  (?::(\d+))?        # port[5]
  (.*)              # etc[6]
>ux
__END__
                , $url, $_)) {
                list($this->proto, $this->user, $this->password, $this->host, $this->port, $url) = array_matches2list($_, 6);
            } else {
                return false;
            }
        }
        if (!isset($url)) return true;
        preg_match(<<<__END__
<^
  ((?:[^/]*/)+)      # path[1]
  ([^\\#\\?]*)      # file[2]
  (?:\\?([^\\#]*))?  # params[4]
  (?:\\#(.+))?      # anchor[3]
>xu
__END__
            , $url, $_);
        if ($full && (!isset($_[1][0]) || $_[1][0] !== '/')) return false;
        list($path, $this->file, $params, $this->anchor) = array_matches2list($_, 4);
        $this->setFullPath($path);
        $this->setParams($params);
        return true;
    }

    public function setParams($params = null)
    {
        if ((!is_array($params) && !is_array($params = $this->stringToParams($params))) || !count($params)) {
            return false;
        }

        foreach ($params as $name => $value) {
            if (!$name) continue;
            $this->params[urldecode($name)] = $value ? urldecode($value) : null;
        }
        return true;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function removeParams($params = false)
    {
        if ($params === false) {
            $this->params = array();
        }
        if (!count($this->params) || !settype($params, 'array') || !count($params)) {
            return;
        }

        foreach ($params as $name) {
            if (!array_key_exists($name, $this->params)) {
                continue;
            }
            unset($this->params[$name]);
        }
    }

    public function paramsToString($qmark = true)
    {
        if (!(is_array($this->params) and count($this->params))) return '';
        foreach ($this->params as $name => $value) {
            if (isset($r)) $r .= '&'; else $r = $qmark ? '?' : '';
            $r .= urlencode($name);
            if (!is_null($value)) $r .= '=' . urlencode($value);
        }
        return $r;
    }

    public function stringToParams($params)
    {
        if (!is_string($params) || !$params) return false;

        if ($params[0] === '?') $params = substr($params, 1);
        $_params = explode('&', $params);
        $params = array();
        foreach ($_params as $param) {
            $param = explode('=', $param);
            $params[$param[0]] = $param[1];
        }
        return $params;
    }

    public function get($full = null, $trimEmptyPath = null)
    {
        if (!isset($full)) $full = isset($this->host);
        if ($full) {
            $r = (isset($this->proto) ? $this->proto : self::curProto()) . '://';
            if (isset($this->host)) {
                if (isset($this->user)) {
                    $r .= $this->user;
                    if (isset($this->password)) $r .= ":{$this->password}";
                    $r .= '@';
                }
                $r .= $this->getFullHost();
            } else $r .= self::curHost();
            $path = $this->getFullPath();
            if (!($trimEmptyPath && $path == '/' && empty($this->file))) {
                $r .= $path;
            }
        } else $r = isset($this->path) ? $this->path : '';
        if (isset($this->file)) $r .= $this->file;
        $r .= $this->paramsToString();
        if (isset($this->anchor)) $r .= "#{$this->anchor}";
        return $r;
    }

    public function getFullHost()
    {
        if (!isset($this->host)) return self::curHost();
        $r = $this->host;
        if (isset($this->port)) $r .= ":{$this->port}";
        return $r;
    }

    public function getFullPath()
    {
        if (!isset($this->path)) {
            return '/';
        }
        if (isset($this->path[0]) and $this->path[0] === '/') {
            return $this->path;
        }

        return self::curPath() . $this->path;
    }

    public function setFullPath($val)
    {
        if (!is_string($val) || !$val) {
            $val = '/';
        }
        $this->path = $val;
    }

    public function shiftPath($fragment)
    {
        $this->path .= $this->file;
        $this->file = '/' . $fragment;
        return $this;
    }

    public function getFilepath()
    {
        $p = $this->getFullPath();
        $f = $this->getFile();

        return !empty($f) ? $p . $f : $p;
    }

    public function setFile($val)
    {

        if ($val === false || $val === null) {
            $this->file = null;
        } else {
            $this->file = (string)$val;
        }

        return $this->file;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function isParentOf($url)
    {
        if (is_string($url)) $url = new Url($url);
        if (!($url instanceof Url)) return false;
        $h1 = self::unWWW($this->getHost());
        $h2 = self::unWWW($url->getHost());
        if (strpos($h2, $h1) !== strlen($h2) - strlen($h1)) return false;
        if (strpos($url->getPath(), $this->getPath()) !== 0) return false;
        return true;
    }

    static function formatUrl($url, $title, $limit = 0)
    {
        $titleShort = mb_truncate($title, $limit);
        return '<a href="' . $url . '" title="' . htmlspecialchars($title) . '">' . $titleShort . '</a>';
    }

    function format($title, $limit = 0)
    {
        return self::formatUrl($this->get(), $title, $limit);
    }
}
