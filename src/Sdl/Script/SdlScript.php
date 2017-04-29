<?php



namespace Sdl\Script;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Sdl\SdlTag;

class SdlScript
{
    protected $funcs = [];

    protected $lang;

    protected $environment = [];

    public function __construct()
    {
        $this->lang = new ExpressionLanguage();
        $this->configure();
    }

    protected function configure()
    {}

    public function createChildContext()
    {
        $script = new SdlScript();
        $script->environment = $this->environment;
        return $script;
    }

    public function addDefaultFunctions()
    {
        $this->addFunction("set", function ($s,$t,$n,array $v,array $a) {
            foreach ($a as $key=>$val) {
                $s->setVar($key,$val);
            }
        });
        $this->addFunction("eval", function ($s,$t,$n,array $v,array $a) {
            foreach ($a as $key=>$val) {
                $s->setVar($key,$s->test($val));
            }
        });
        $this->addFunction("echo", function ($s,$t,$n,array $v,array $a) {
            foreach ($v as $line) {
                printf("%s\n", $line);
            }
        });
        $this->addFunction("printf", function ($s,$t,$n,array $v,array $a) {
            @call_user_func("printf", ...$v);
        });
        $this->addFunction("if", function ($s,$t,$n,array $v,array $a) {
            if (count($v)!=1) {
                throw new \Exception("if expects 1 argument");
            }
            if ($s->test($v[0])) {
                $s->evaluate($t);
            }
        });
        $this->addFunction("while", function ($s,$t,$n,array $v,array $a) {
            if (count($v)!=1) {
                throw new \Exception("if expects 1 argument");
            }
            while ($s->test($v[0])) {
                $s->evaluate($t);
            }
        });
        $this->addFunction("each", function ($s,$t,$n,array $v,array $a) {
            if (array_key_exists("as",$a)) {
                $var = $a['as'];
            } else {
                $var = 'value';
            }
            foreach ($v as $value) {
                $s->setVar($var,$value);
                $s->evaluate($t);
            }
        });
    }

    public function addFunction($name, callable $handler)
    {
        $this->funcs[$name] = $handler;
    }

    public function evaluate(SdlTag $root)
    {
        foreach ($root->getChildren() as $child) {
            $name = $child->getTagName();
            if (array_key_exists($name, $this->funcs)) {
                $func = $this->funcs[$name];
                $vals = $this->mapValues($child->getAllValues());
                $attrs = $this->mapAttrs($child->getAttributeStrings());
                $ret = call_user_func($func, $this, $child, $name, $vals, $attrs);
                if (count($child->getChildren()) && $ret) {
                    $this->evaluate($child);
                }
            } else {
                fprintf(STDERR, "Warning: Undefined function %s\n", $name);
            }
        }
    }

    public function test($expr)
    {
        $expr = $this->mapString($expr);
        return $this->lang->evaluate($expr, $this->environment);
    }

    public function setVar($name,$value)
    {
        $this->environment[$name] = $value;
    }

    protected function mapValues(array $values)
    {
        return array_map([$this,"mapString"], $values);
    }

    protected function mapAttrs(array $attrs)
    {
        foreach ($attrs as $key=>$value) {
            $attrs[$key] = $this->mapString($value);
        }
        return $attrs;
    }

    protected function mapString($string)
    {
        return preg_replace_callback('/%\{(.+?)\}/', [ $this,"substMatch" ], $string);
    }

    private function substMatch($match) {
        $val = $match[1];
        if (array_key_exists($val, $this->environment)) {
            return $this->environment[$val];
        }
        return "";
    }

}

