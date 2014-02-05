php-sdl 2.0
===========

[![Build Status](https://travis-ci.org/noccylabs/php-sdl.png?branch=sdl2)](https://travis-ci.org/noccylabs/php-sdl)

This is an implementation of Simple Declarative Language (SDL) for PHP. It has
not thing to do with Simple Directmedia Layer.

This is the v2.0 rewrite of php-sdl, and as such some things are not quite
working yet.

 * Not all `LiteralType`s are implemented. This is easily done now however, as
   each type is in its own folder.
 * The parser is broken still. 
 * Queries (SdlSelector) are not implemented yet.
 * Not all unit tests have been created.

## Usage

You shouldn't really use this right now. Here is a brief summary of what is working,
and what is not.

| **Component**                 | **Description**              | **Status** |
|:------------------------------|:-----------------------------|:----------:|
| `Sdl\SdlTag`                  | Creating tag trees           | WORKING    |
| `Sdl\SdlTag`                  | Encoding tags and children   | WORKING    |
| `Sdl\SdlTag`                  | Encoding tags with comments  | WORKING    |
| `Sdl\SdlTag`                  | Tree traversal               |            |
| `Sdl\SdlTag`                  | Tests implemented            | PARTIAL    |
| `Sdl\Parser\SdlParser`        | Parsing tags and nested tags | WORKING    |
| `Sdl\Parser\SdlParser`        | Parsing tags with comments   |            |
| `Sdl\Parser\SdlParser`        | Tests implemented            | PARTIAL    |
| `Sdl\LiteralType\TypeFactory` | LiteralType registering      | PARTIAL    |
| `Sdl\LiteralType\TypeFactory` | Tests implemented            | PARTIAL    |
| `Sdl\LiteralType\*Type`       | All types implemented        |            |
| `Sdl\LiteralType\*Type`       | Tests implemented            | PARTIAL    |
| `Sdl\Selector\SdlSelector`    | Selecting with expressions   |            |
| `Sdl\Selector\SdlSelector`    | Tests implemented            |            |

### Performance

The parser is currently a little slow at reading, but this can probably be
optimized somewhat. Either way it offers a tidy alternative to the established
serialization formats (XML, JSON and YAML). It is important to remember that
the parsers for XML, JSON and YAML are running as native code, while the SDL
parser is written in PHP.

| **Format** | **Parser**              | **10000 its**   | **Its/s** | **100 its**  |
|:----------:|:------------------------|----------------:|----------:|-------------:|
| XML        | DomDocument::loadXml    | 0.35 seconds    | 28984.5   | 0.003s       |
| SDL        | SdlParser::parseFile    | 4.79 seconds    | 2089.9    | 0.048s       |
| SDL        | SdlParser::parseString  | 4.47 seconds    | 2235.4    | 0.045s       |
| JSON       | json_decode             | 0.25 seconds    | 39920.6   | 0.003s       |
| YAML       | yaml_parse_file         | 0.45 seconds    | 22437.3   | 0.004s       |

Some possible improvements and optimizations include:

 * Caching of parsed structures on filesystem or in memcached.
 * Improvements to the pre-parser optimization routines.
 * Rewrite the parser using regular expressions (could be faster, could be slower)

## Examples

### Generating tag trees

Generating is simple. Get a new root tag with `createRoot()` and start 
adding your children. You can use the same fluid programming as you are used
to from Symfony2, where all the `setX()` methods return the current tag, and
a call to `end()` returns the parent node. `createChild()` is available to
create a tag, add it as a child to the current node, and return the newly
created child tag.

        use Sdl\SdlTag;
        $tag = SdlTag::createRoot()
            ->createChild("people")
                ->createChild("person")
                    ->setValue("John Doe")
                    ->setAttribute("sex","male")
                    ->end()
                ->end();
        echo $tag->encode();

Values and attributes can be assigned from PHP values, or directly via any of the
`LiteralType` descendants. 

        use Sdl\SdlTag;
        use Sdl\LiteralType\SdlBinary;
        $tag = SdlTag::createRoot()
            ->createChild("image")
                ->setValue(new SdlBinary($file))
                ->setAttribute("type","image/jpeg")
                ->end();

Remember to match your calls to `end()` to make sure you return the root 
element when you are using the fluid method calls on a new root or non-variable:

        use Sdl\SdlTag;
        $tag = SdlTag::createRoot();
        $tag->createChild("foo")->createChild("bar");
        // $tag will still point to the root even though end() wasn't called.
        $bad = SdlTag::createRoot()->createChild("foo")->createChild("bar");
        // $bad will be pointing to "bar" here, not the root.

### Parsing a file

To parse a file, use the `Sdl\Parser\SdlParser` class. It offers a few different
methods to parse content and return `Sdl\SdlTag` objects.

        use Sdl\Parser\SdlParser;
        // Parse a file
        $tag = SdlParser::parseFile("basic.sdl");
        // Parse a string
        $tag = SdlParser::parseString($sdl_string);

### Encoding tags to SDL

        use Sdl\SdlTag;
        // Create a new root
        $tag = SdlTag::createRoot();
        // Add two children
        $tag->addChild("foo")->setValuesFromArray([0, 1, 2 ]);
        $tag->addChild("bar")->setValuesFromArray([2, 3, 4 ]);
        // Output the final SDL
        echo $tag->encode();

### Navigating children

You can use `getAllChildren()`, `getChildrenByTagName(..)` to navigate the tree.

The `SdlSelector` will provide a more convenient approach to querying the tree
with logical expressions.

        use Sdl\Parser\SdlParser;
        $tag = SdlTag::createRoot();
        $tag->createChild("people")
            ->createChild("person")
                ->setValue("John Doe")
                ->setAttribute("sex","male");
        $people = $tag->getChildrenByTagName("people")[0]->getAllChildren();
        echo "Person name: ".$people[0]->getValue()."\n";

*PARTIALLY IMPLEMENTED in php-sdl 2.0*

        // Enumerate children
        foreach($tag->getAllChildren() as $ctag) {
            printf("Tag: %s\n", $ctag->getTagName());
        }

### Queries

*Not yet implemented in php-sdl 2.0!*

        use Sdl\Parser\SdlParser;
        use Sdl\Selector\SdlSelector;

        // Load the data
        $tag = SdlParser::parseFile("data.sdl");
        $query = new SdlSelector($tag);
        
        // xpath like queries (returns array)
        $tags = $query->query("/colors/color");
        foreach($tags as $tag) {
            // Write out the SDL of the tag
            echo $tag->encode();
        }

        // same, for single tag
        $single_tag = $query->queryOne("/colors/color[@name=red]");
        printf("Color: %s, Value: %s\n", $single_tag->name, $single_tag[0]);

