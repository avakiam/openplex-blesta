<?php
// Just enough of Blesta's classes to run the module outside Blesta: the
// Module base class, Input's rule engine (the rule forms the module uses),
// Language (strings come from the module's en_us file, and a missing one
// fails), ModuleFields, Loader and Configure.
define('DS', DIRECTORY_SEPARATOR);

class Configure
{
    public static $v = ['Blesta.company_id' => 1];
    public static function set($k, $x) { self::$v[$k] = $x; }
    public static function get($k) { return self::$v[$k] ?? null; }
    public static function load($name, $dir) { require $dir . $name . '.php'; }
}

class Language
{
    public static $lang = [];
    public static $missing = [];
    public static function loadLang($file, $code, $dir)
    {
        $lang = [];
        require $dir . 'en_us' . DS . $file . '.php';
        self::$lang = $lang;
    }
    public static function _($key, $return = false)
    {
        if (!isset(self::$lang[$key])) {
            self::$missing[] = $key;
        }
        $s = self::$lang[$key] ?? $key;
        if ($return) {
            return $s;
        }
        echo $s;
    }
}

class FakeClients
{
    public $emails = [];
    public function get($id, $extra = true)
    {
        return isset($this->emails[$id]) ? (object) ['id' => $id, 'email' => $this->emails[$id]] : false;
    }
}

class FakeModuleManager
{
    public function getByClass($class, $company) { return [(object) ['id' => 3, 'class' => $class]]; }
    public function getGroup($id) { return (object) ['id' => $id, 'add_order' => 'first', 'rows' => [(object) ['id' => 4]]]; }
}

class Loader
{
    public static $clients;
    public static function loadComponents($obj, array $names)
    {
        foreach ($names as $n) {
            if ($n === 'Input') {
                $obj->Input = new Input();
            }
        }
    }
    public static function loadModels($obj, array $names)
    {
        foreach ($names as $n) {
            if ($n === 'Clients') {
                $obj->Clients = self::$clients;
            }
            if ($n === 'ModuleManager') {
                $obj->ModuleManager = new FakeModuleManager();
            }
        }
    }
    public static function loadHelpers($obj, array $names) {}
}

class Input
{
    private $rules = [];
    private $errors = [];

    public function setRules(array $rules) { $this->rules = $rules; }
    public function setErrors(array $e) { $this->errors = $e; }
    public function errors() { return $this->errors ?: false; }

    private static function value(array $vars, $name, &$set)
    {
        $parts = preg_split('/[\[\]]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $v = $vars;
        foreach ($parts as $p) {
            if (!is_array($v) || !array_key_exists($p, $v)) {
                $set = false;
                return null;
            }
            $v = $v[$p];
        }
        $set = true;
        return $v;
    }

    public function validates($vars)
    {
        $vars = (array) $vars;
        $this->errors = [];
        foreach ($this->rules as $field => $checks) {
            $value = self::value($vars, $field, $set);
            foreach ($checks as $name => $check) {
                if (!empty($check['if_set']) && !$set) {
                    continue;
                }
                $rule = $check['rule'];
                if ($rule === 'isEmpty') {
                    $ok = trim((string) $value) === '';
                } elseif (is_array($rule) && is_array($rule[0])) {
                    $ok = call_user_func_array($rule[0], array_merge([$value], array_slice($rule, 1)));
                } elseif (is_array($rule)) {
                    $fn = $rule[0];
                    $args = array_slice($rule, 1);
                    $ok = $fn === 'matches' ? (bool) preg_match($args[0], (string) $value) : call_user_func_array($fn, array_merge([$value], $args));
                } else {
                    throw new LogicException("rule $rule");
                }
                if (!empty($check['negate'])) {
                    $ok = !$ok;
                }
                if (!$ok) {
                    $this->errors[$field][$name] = $check['message'];
                }
            }
        }
        return !$this->errors;
    }
}

class ModuleField
{
    public $options = [];
    public $name;
    public $type;
    public $value;
    public $attached = [];
    public function attach($f) { $this->attached[] = $f; return $this; }
}

class ModuleFields
{
    public $fields = [];
    public $html = '';
    private function f($type, $name, $value = null) { $f = new ModuleField(); $f->type = $type; $f->name = $name; $f->value = $value; return $f; }
    public function label($text, $for = null) { return $this->f('label', $for, $text); }
    public function fieldText($name, $value = null, $attrs = []) { return $this->f('text', $name, $value); }
    public function fieldPassword($name, $attrs = []) { return $this->f('password', $name, $attrs['value'] ?? null); }
    public function fieldSelect($name, $options = [], $selected = null, $attrs = []) { $f = $this->f('select', $name, $selected); $f->options = $options; return $f; }
    public function fieldCheckbox($name, $value, $checked = false, $attrs = []) { return $this->f('checkbox', $name, $checked); }
    public function tooltip($text) { return $this->f('tooltip', null, $text); }
    public function setField($f) { $this->fields[] = $f; }
    public function setHtml($h) { $this->html = $h; }
}

#[\AllowDynamicProperties]
class Module
{
    public $base_uri = '/admin/';
    public $Input;
    public $logs = [];
    public $row;

    protected function loadConfig($file) { json_decode(file_get_contents($file), true) or die('bad config.json'); }
    public function getModuleRow($id = null) { return $this->row; }
    public function getModuleRows($group = null) { return [$this->row]; }
    protected function log($url, $data, $type, $success = false) { $this->logs[] = [$url, $data, $type, $success]; }
    protected function serviceFieldsToObject(array $fields)
    {
        $o = new stdClass();
        foreach ($fields as $f) {
            $o->{$f->key} = $f->value;
        }
        return $o;
    }
}

// Views are rendered for real (with recording helpers), so that a mistake
// in a .pdt fails the tests.
class ViewHelper
{
    public $calls = [];
    public function __call($name, $args) { $this->calls[] = $name; return $this; }
    public function safe($s) { return htmlspecialchars((string) $s, ENT_QUOTES); }
    public function _($s) { echo htmlspecialchars((string) $s, ENT_QUOTES); }
}

class View
{
    public $base_uri;
    public $Html;
    public $Form;
    public $Widget;
    private $file;
    private $dir;
    private $vars = [];

    public function __construct($file, $theme)
    {
        $this->file = $file;
        $this->Html = new ViewHelper();
        $this->Form = new ViewHelper();
        $this->Widget = new ViewHelper();
    }
    public function setDefaultView($dir) { $this->dir = $dir; }
    public function set($k, $v) { $this->vars[$k] = $v; }
    public function _($key, $return = false, ...$args) { return Language::_($key, $return); }
    public function fetch()
    {
        $path = __DIR__ . DS . '..' . DS . $this->dir . 'views' . DS . 'default' . DS . $this->file . '.pdt';
        extract($this->vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
