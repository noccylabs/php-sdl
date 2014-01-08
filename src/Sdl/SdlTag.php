<?php

namespace Sdl;

/**
 * @brief SDL (Simple Declarative Language) node implementation.
 *
 * This class covers both serializing (encoding) and unserializing (decoding)
 * of data in SDL format.
 *
 * The unserializing is built on top of the PHP tokenizer (token_get_all) and
 * is thus fast and reliable.
 *
 * @todo
 *   - Implement the remaining types.
 *   - Attributes should also support namespaces
 *   - Multiline strings with "\"
 *   - Use ; to separate tags, as per SDL 1.1
 *
 * @author Christopher Vagnetoft <noccylabs-at-gmail>
 * @license GNU GPL v3
 */
class SdlTag implements \ArrayAccess, \IteratorAggregate, \Countable {

    private $name       = null;
    private $values     = [];
    private $attr       = [];
    private $children   = [];
    private $comment    = null;
    private $doccomment = null;
    private $ns         = null;
    private $parent     = null;

    /** Strict parsing according to SDL 1.2 */
    const OPT_STRICT = 0x01;

    const PARSER_TAGNAME = 0;
    const PARSER_TAGVALUE = 1;
    const PARSER_TAGATTR = 2;

    /**
     * @brief Create a new SDL node
     *
     * @param string $name The node name (with optional prefixed namespace followed by :, eg. foo:bar)
     * @param array|string $values The value(s) of the node. Can be null.
     * @param array $attr The attributes to attach to the node.
     */
    public function __construct($name = null, $values = null, array $attr = null) {
        if (!$name) $name = null;
        if (strpos($name,':')!==false) {
            list($this->ns,$this->name) = explode(':',$name,2);
        } else {
            $this->name = $name;
        }
        // Extract the values as typed values
        foreach((array)$values as $val) {
            if (!($val instanceof SdlTypedValue))
                $val = SdlTypedValue::parse($val);
            $this->values[] = $val;
        }
        foreach((array)$attr as $k=>$value) {
            if (!($value instanceof SdlTypedValue))
                $value = SdlTypedValue::parse($value);
            $this->attr[$k] = $value;
        }
    }

    public static function createFromString($string) {
        $tag = new SdlTag("root");
        $tag->loadString($string);
        return $tag;
    }

    public static function createFromFile($filename) {
        $string = file_get_contents($filename);
        return self::createFromString($string);
    }

    public function loadFile($filename) {
        $string = file_get_contents($filename);
        $this->loadString($string);
    }

    /**
     * Parse a string recursively
     *
     *
     */
    public function loadString($str,$opts=null) {

        static $level = 0;

        if (!is_array($str)) {
            $toks = token_get_all("<?php ".$str);
            // Get rid of the opening tag
            array_shift($toks);
            $level = 0;
        } else {
            $toks = $str;
            $level++;
        }

        // Helpers

        $pstate = self::PARSER_TAGNAME; // parser state, what we are expecting
        $buf = null; // Holding the current buffer
        $lasttok = null; // Holding the last token for attr assignment
        $tagname = null; // The parsed tag name
        $tagvals = []; // The tag values
        $tagattr = []; // The tag attributes
        $tagcmt = null;
        $tagdcmt = null;
        $break = false; // flag to indicate end of tag
        $lline = "n/a";
        $toktyp = null;

        while(count($toks)>0) {
            $thistok = array_shift($toks);
            // Get the string representation of the token
            if (is_array($thistok)) {
                list($toktyp,$thisstr,$lline) = $thistok;
                switch($toktyp) {
                    case T_COMMENT:
                        $thiscmt = trim(substr($thisstr,2));
                        if ($tagcmt) {
                            if (!$thiscmt)
                                $tagcmt.="\n";
                            else
                                $tagcmt.=" ".$thiscmt;
                        } else {
                            $tagcmt = $thiscmt;
                        }
                        $thisstr = null;
                        break;
                    case T_DOC_COMMENT:
                        $tagdcmt = $thisstr;
                        $thisstr = null;
                        break;
                }
                //echo token_name($toktyp)."\n";
            } else {
                $thisstr = $thistok; $toktyp = null;
            }
            // we do this to only detect newlines, we don't care about the
            // padding around it.
            if (strpos($thisstr,"\n")!==false) {
                if (!trim($thisstr,"\n\r ")) $thisstr = "\n";
            }
            // Replace tabs
            if (strpos($thisstr,"\t")!==false)
                $thisstr = str_replace("\t"," ",$thisstr);

            // Parse the tokens
            $break = false;
            //echo "\033[1m{$level}\033[0m\033[7m{$thisstr}\033[0m\n";
            $thisstr = trim($thisstr," ");
            switch($thisstr) {
                case "}":
                    if ($level <= 0)
                        throw new SdlParserException("Recursion level mismatch on '}'", SdlParserException::ERR_RECURSION_MISMATCH);
                    //echo "Leaving child...\n";
                    $pstate = self::PARSER_TAGNAME;
                    $level--;
                    return $toks;
                    //echo "Ascend: {$buf}\n";
                    $buf = null;
                    // ascend
                    break;
                case ";":
                case "\n":
                case "\r":
                case "{":
                    $break = true;

                case "":
                    if (!$break) {
                        // is this part of a date?
                        $next = $toks[0]; if (is_array($next)) $next = $next[1];
                        $next2 = $toks[1]; if (is_array($next2)) $next2 = $next2[1];
                        $next.= $next2;
                        if ((preg_match("/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/", $buf)
                         && (preg_match("/^[0-9]{2}:/", $next)))) {
                            $buf.=" ";
                            break;
                        }
                    }
                    // If this is a binary chunk we want to keep reading til "]"
                    if (substr(trim($buf),0,1)=="[") {
                        if (substr(trim($buf),-1,1)!="]") {
                            $break = false;
                            break;
                        }
                        // If we make it here we have a full binary blob
                    }
                    if (trim($buf)) {
                        if ($pstate == self::PARSER_TAGATTR) {
                            // Found a tag attribute
                            $lt = SdlTypedValue::parse($buf,true,$lasttok);
                            if (!$lt)
                                throw new SdlParserException("Unparsed attribute value: {$buf}");
                            $tagattr[$lasttok] = $lt;
                            //echo "  Attr: {$lasttok} = {$buf}\n";
                            $pstate = self::PARSER_TAGVALUE;
                        } elseif ($pstate == self::PARSER_TAGVALUE) {
                            // Found a tag value
                            $tv = SdlTypedValue::parse($buf,true);
                            if (!$tv)
                                throw new SdlParserException("Unparsed value: {$buf}");
                            $tagvals[] = $tv;
                            //echo "  Value: {$buf} parsed as {$tv}\n";
                        } elseif ($pstate == self::PARSER_TAGNAME) {
                            // Found a tag name, inspect and see if it is a valid
                            // tag name, and if not create an anonymous tag.
                            if ($this->isValidIdentifier(trim($buf))) {
                                $tagname = trim($buf);
                                //echo "Tag: {$buf}\n";
                            } else {
                                $tagname = null;
                                $tagvals[] = SdlTypedValue::parse($buf,true);
                                //echo "(anon)\n  Value: {$buf}\n";
                            }
                            $pstate = self::PARSER_TAGVALUE;
                        }
                    }
                    if ($thisstr == "{") {
                        //echo "Got { ... \n"; var_dump($tagname);
                        //echo "Entering child...\n";
                        //var_dump($tagvals);
                        if (!empty($tagname) || !empty($tagvals)) {
                            $tag = new SdlTag($tagname,$tagvals,$tagattr);
                            $tag->setComment($tagcmt);
                            $tag->setDocComment($tagdcmt);
                            $toks = $tag->loadString($toks,$opts);
                        }
                        $break = true;
                    } elseif ($break) {
                        //echo "Got ; ... \n"; var_dump($tagname);
                        //var_dump($tagvals);
                        if (!empty($tagname) || !empty($tagvals)) {
                            $tag = new SdlTag($tagname,$tagvals,$tagattr);
                        }
                    } else {
                        $tag = null;
                    }
                    // If we are at the end of the tag, reset the state
                    if ($break) {
                        if (!empty($tag))
                            $this->children[] = $tag;
                        $tag = null;
                        $tagname = null;
                        $tagvals = []; $tagattr = [];
                        $tagcmt = null; $tagdcmt = null;
                        $pstate = self::PARSER_TAGNAME;
                        $break = false;
                    }
                    $buf = null;
                    // new state
                    break;
                case "=":
                    // Remember the last token and set the parser state to
                    // expect an attribute value.
                    if (substr($buf,0,1) == "[") {
                        // If we are in a binary chunk, just stash and break out
                        $buf.=$thisstr;
                        break;
                    }
                    if ($this->isValidIdentifier($buf)) {
                        $lasttok = $buf;
                        $pstate = self::PARSER_TAGATTR;
                    } else {
                        throw new SdlParserException("Invalid identifier '{$buf}' used as attribute near line {$lline}", SdlParserException::ERR_INVALID_IDENTIFIER);
                    }
                    $buf = null;
                    // attribute value asign
                    break;
                default:
                    //echo "Pushing to buffer: {$thisstr}\n";
                    $buf.= $thisstr;

            }
        }

    }

    /**
     * Check if the name is a valid identifier according to SDL 1.2
     *
     */
    private function isValidIdentifier($name) {
        // From the SDL language guide: An SDL identifier starts with a unicode
        // letter or underscore (_) followed by zero or more unicode letters,
        // numbers, underscores (_), dashes (-), periods (.) and dollar signs
        // ($).
        return (preg_match("/^[_a-zA-Z]{1}[_\-\.\$a-zA-Z0-9]*/", $name));

    }

    /**
     * @brief Encode the node and all child nodes into serialized SDL
     *
     * @param $indent The level of indenting
     * @return string The SDL string
     */
    public function encode($indent=0) {
        $ind = str_repeat(" ",$indent*4);
        $node = "";
        if ($this->comment) {
            $lines = explode("\n",$this->comment);
            foreach($lines as $line)
                $node.= $ind."// ".$line."\n";
        }
        $node.= $ind;
        if ($this->ns)
            $node.= $this->ns.':';
        $node.= $this->name;
        if (count($this->values)>0) {
            foreach($this->values as $value) {
                $node.=" ".$value->encode();
            }
        }
        if (count($this->attr)>0) {
            foreach($this->attr as $k=>$v) {
                $v = $v->encode();
                $node.=" {$k}={$v}";
            }
        }
        if ((count($this->children)>0) || (count($this->values)==0) ) {
            if (count($this->children)==0) {
                //$node.= " { }";
            } else {
                $node.=" {\n";
                foreach($this->children as $child) {
                    $node.=$child->encode($indent+1)."\n";
                }
                $node.=$ind."}";
            }
        }
        if ($indent==0) $node.="\n";
        return $node;
    }

    public function encodeChildren() {
        $nodes = null;
        foreach($this->children as $child)
            $nodes .= $child->encode();
        return $nodes;
    }



    //// BASE GETTERS AND SETTERS //////////////////////////////////////////////

        /**
     * @brief Return the name of the node.
     *
     * @return string The node name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @brief Return the name with the namespace prepended.
     *
     * @return string The name with the namespace prepended.
     */
    public function getNameNs() {
        if (!empty($this->ns))
            return $this->ns.':'.$this->name;
        else
            return ':'.$this->name;
    }

    /**
     * @brief Set the name of the node.
     *
     * @param string $name The name to set
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @brief Return the namespace of the node
     *
     * @return string The namespace (or null)
     */
    public function getNamespace() {
        return $this->ns;
    }

    /**
     * @brief Set the namespace of the node
     *
     * @param string $ns The namespace to set
     */
    public function setNamespace($ns) {
        $this->ns = $ns;
    }

    /**
     * @brief Set the node comment.
     *
     * You can set the comment to null to remove it.
     *
     * @param string $str The comment
     */
    public function setComment($str) {
        $this->comment = $str;
    }

    /**
     * @brief Get the node comment
     *
     * @return string The comment (or null)
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * @brief Set the node doc comment.
     *
     * You can set the comment to null to remove it.
     *
     * @param string $str The doc comment
     */
    public function setDocComment($str) {
        if (substr(ltrim($str),0,3) == "/**") {
            $cout = [];
            $cmt = trim($str,"/*\n");
            $cmtl = explode("\n",$cmt);
            foreach($cmtl as $cmtr) $cout[] = ltrim($cmtr,"* ");
            $str = trim(join("\n",$cout));
        }
        $this->doccomment = $str;
    }

    /**
     * @brief Get the node doccomment
     *
     * @return string The doccomment (or null)
     */
    public function getDocComment() {
        return $this->doccomment;
    }



    //// PARENT CONTROL ////////////////////////////////////////////////////////

    public function getParent() {
        return $this->parent;
    }

    public function setParent($parent) {
        $this->parent = $parent;
    }



    //// CHILDREN AND ENUMERATION //////////////////////////////////////////////

    /**
     * Get an iterator for the children
     */
    public function getIterator() {
        return new \ArrayIterator($this->getChildren());
    }

    /**
     * @brief Return all children whose node name match the string.
     *
     * @param string $name Tag name or null for all.
     * @return array The matchind nodes or null.
     */
    public function getChildren($name=null) {
        // If $name is null, return all children
        if (!$name)
            return $this->children;
        // Return all nodes of type $name
        $ret = [];
        foreach($this->children as $nod) {
            if ($nod->getName() == $name) $ret[] = $nod;
        }
        return $ret;
    }

    /**
     * @brief Check if a node has child nodes
     *
     * @return bool True if the node has child nodes
     */
    public function hasChildren() {
        return (count($this->children)>0);
    }

    /**
     * @brief Return the first child whose node ame match the string.
     *
     * @param string $name The node name to match
     * @param string $withvalue The node value to match (or null)
     * @return SdlTag The first matching node or null
     */
    public function getChild($name,$withvalue=null) {
        if (is_integer($name)) {
            if (!empty($this->children[$name]))
                return $this->children[$name];
            return null;
        }
        foreach($this->children as $nod) {
            if ($nod->getName() == $name) {
                if (!$withvalue) return $nod;
                if ($withvalue == $nod->getValue()) return $nod;
            }
        }
        return null;
    }

    /**
     * @brief Add a child node to the node.
     *
     * @param SdlTag $node The node to append
     */
    public function addChild(SdlTag $node) {
        $node->setParent($this);
        $this->children[] = $node;
    }

    /**
     * @brief Remove a child; node must match exact (===)
     *
     * @param SdlTag $node The node to delete.
     */
    public function removeChild(SdlTag $node) {
        $this->children = array_filter(
            $this->children,
            function($nv) use ($node) {
                return (!($nv === $node));
            }
        );
    }



    //// VALUE ACCESS VIA ARRAYACCESS //////////////////////////////////////////

    /**
     * Return the number of values
     */
    public function count() {
        return count($this->values);
    }

    /**
     * Get a value
     */
    public function offsetGet($index) {
        if (isset($this->values[(int)$index])) {
            return $this->values[(int)$index]->getValue();
        }
        return null;
    }

    /**
     * Set a value
     */
    public function offsetSet($index,$value) {
        if (is_array($value))
            throw new SdlParseException("Invalid value type for set: <array> is not allowed");
        if ($index === null) {
            $this->addValue($value);
        } else {
            $this->setValue($value,$index);
        }
    }

    /**
     * Unset a value
     */
    public function offsetUnset($index) {
        if (isset($this->values[(int)$index]))
            unset($this->values[(int)$index]);
    }

    /**
     * Check if a value is set
     */
    public function offsetExists($index) {
        return (isset($this->values[(int)$index]));
    }

    /**
     * @brief Return all the values of the node
     *
     * @return array The values
     */
    public function getValues() {
        $vo = [];
        foreach($this->values as $vl) $vo[] = $vl->getValue();
        return $vo;
        //return $this->values;
    }

    /**
     * @brief Set the value at a index.
     *
     * @param Mixed $value The value to set
     * @param int $index The index (default 0)
     * @param int $type The type to assign (null=detect)
     */
    public function setValue($value,$index=0,$type=null) {
        if (!($value instanceof SdlTypedValue))
            $value = SdlTypedValue::parse($value);
        $this->values[$index] = $value;
    }

    public function setBinaryValue($value,$index=0) {
        $value = new SdlTypedValue($value, SdlTypedValue::LT_BINARY);
        $this->values[$index] = $value;
    }

    /**
     * @brief Add the value to an attribute.
     *
     * This function will not overwrite anything.
     *
     * @param Mixed $value The value to set
     */
    public function addValue($value) {
        if (!($value instanceof SdlTypedValue))
            $value = SdlTypedValue::parse($value);
        $this->values[] = $value;
    }

    /**
     * @brief Return a value from the node.
     *
     * @param int $index The index to retrieve.
     * @return mixed The first value of the node
     */
    public function getValue($index=0) {
        return $this->values[$index]->getValue();
    }

    /**
     * Get value as from a matrix [row][col]
     *
     */
    public function getValueMatrix($row,$column) {

    }

    /**
     * Return direct child values as a value map, discarding attributes.
     *
     * $map = [
     *     'foo' => 'Bar',
     *     'bar' => 'Baz'
     * ];
     *
     */
    public function getValueMap() {
        $map = [];
        foreach($this->children as $child) {
            $map[$child->getName()] = $child->getValue();
        }
        return $map;
    }

    public function getValueArray() {
        $map = [];
        foreach($this->children as $child) {
            $map[] = $child->getValue();
        }
        return $map;
    }
    
    public function value($index=0) {
        return $this->values[$index];
    }



    //// ATTRIBUTE ACCESS VIA PROPERTIES ///////////////////////////////////////

    /**
     * Get an attribute
     */
    public function __get($key) {
        return $this->getAttribute($key);
    }

    /**
     * Set an attribute
     */
    public function __set($key,$value) {
        if (is_array($value))
            throw new SdlParseException("Invalid value type for attribute set: <array> is not allowed");
        if (array_key_exists($key,$this->attr)) {
            $this->attr[$key]->setValue($value);
        } else {
            $this->attr[$key] = SdlTypedValue::parse($value);
        }
    }

    /**
     * Unset an attribute
     */
    public function __unset($key) {
        $this->removeAttribute($key);
    }

    /**
     * Check if attribute is set
     */
    public function __isset($key) {
        return $this->hasAttribute($key);
    }


    /**
     * @brief Return all the attributes of the node.
     *
     * @return array The attributes
     */
    public function getAttributes() {
        $out = [];
        foreach($this->attr as $k=>$v) {
            $out[$k] = $v->getValue();
        }
        return $out;
    }

    /**
     * @brief Return a single attribute of the node.
     *
     * This can also be accessed via the properties:
     *
     * @code
     * $attr = $node->getAttribute("foo");
     * // ...is the same as...
     * $attr = $node->foo;
     * @endcode
     *
     * @param string $name The attribute to return
     * @return mixed The attribute value
     */
    public function getAttribute($name) {
        if (array_key_exists($name,$this->attr))
            return $this->attr[$name]->getValue();
        return null;
    }

    /**
     *
     */
    public function hasAttribute($name) {
        return array_key_exists($name,$this->attr);
    }

    /**
     *
     *
     */
    public function setAttribute($name,$value) {
        if (!($value instanceof SdlTypedValue))
            $value = SdlTypedValue::parse($value);
        $this->attr[$name] = $value;
    }

    /**
     *
     */
    public function removeAttribute($name) {
        $this->attr[$name] = null;
        unset($this->attr[$name]);
    }


    //// XPATH-LIKE QUERIES ON TAGS ////////////////////////////////////////////

    /**
     * Perform a query on the node, or if the expression starts with / from the
     * root node.
     *
     */
    public function query($expr) {
        // \debug("Evaluating spath: {$expr}");
        if ($expr == "") {
            return $this;
        } elseif ($expr[0] == "/") {
            // Find the root of the document and pass the query on
            $root = $this;
            while(($newroot = $root->getParent())) $root = $newroot;
            return $root->query(substr($expr,1));
        } else {
            // Grab the first part of the expression
            // TODO: Make this a regex, until then any / will break the expression
            list($parse,$expr) = explode("/",$expr.'/',2);
            $expr = rtrim($expr,"/");
            if (strpos($parse,"[")!==false) {
                $match = explode("[",$parse);
                $tagname = array_shift($match);
                foreach($match as $k=>$v) $match[$k] = rtrim($v,"]");
            } else {
                $tagname = $parse;
                $match = [];
            }
            // Enumerate the children for matching tagnames
            $ret = [];
            foreach($this->children as $node) {
                $matched = true;
                if (fnmatch($tagname,((strpos($tagname,":")!==false)?$node->getNameNs():$node->getName()))) {
                    if (count($match)>0) {
                        foreach($match as $m) {
                            if ($m[0] == '@') {
                                $m = substr($m,1);
                                if (strpos($m,"=")===false) {
                                    $attr = $m;
                                    if (!$node->hasAttribute($attr)) $matched = false;
                                } else {
                                    list($attr,$val) = explode("=",$m,2);
                                    if ($val == "true") $val = true;
                                    elseif ($val == "false") $val = false;
                                    elseif ($val == "null") $val = null;
                                    if ($node->{$attr} !== $val) $matched = false;
                                }
                                // Test attribute
                            } else {
                                if (!in_array($m,$node->getValues())) {
                                    $matched = false;
                                    break;
                                }
                            }
                        }
                    }
                    if ($matched) {
                        $cn = $node->query($expr);
                        if (is_array($cn))
                            $ret = array_merge($ret,$cn);
                        elseif ($cn)
                            $ret[] = $cn;
                    }
                }
            }
            return $ret;
        }
    }
    
    public function queryOne($expr) {
        $ret = $this->query($expr);
        if (empty($ret))
            return null;
        return $ret[0];
    }

    public function queryValue($expr) {
        $ret = $this->query($expr);
        if (empty($ret))
            return null;
        return $ret[0]->getValue();
    }


}
