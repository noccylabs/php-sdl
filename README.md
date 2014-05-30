php-sdl 2.0
===========

This is an implementation of the Simple Declarative Language (SDL) serialization
language for PHP. It has not thing to do with Simple Directmedia Layer. Think
of it like XML with less typing:

        greetings {
            greeting "Aloha" where="Hawaii"
            greeting "Hej" where="Sweden"
        }

This is the v2.0 rewrite of php-sdl, and as such some things are not quite
working yet. If you are looking for a working but not too extensible parser,
install the v1.x branch with composer, `composer require noccylabs/sdl:1.*`.
The current quirks are:

* Not all `LiteralType`s are implemented. This is easily done now however, as
  each type is in its own folder.
* The parser is broken still. 
* Not all unit tests have been created.
* Comments can be generated, but not necessarily always parsed.
* Numeric types may lose precision.

## Usage

You shouldn't really use this right now. Here is a brief summary of what is working,
and what is not. Contributions and improvements are welcome.

### Components

| **Component**                 | **Description**              | **Status** |
|:------------------------------|:-----------------------------|:----------:|
| `Sdl\SdlTag`                  | Creating tag trees           | WORKING    |
| `Sdl\SdlTag`                  | Encoding tags and children   | WORKING    |
| `Sdl\SdlTag`                  | Encoding tags with comments  | WORKING    |
| `Sdl\SdlTag`                  | Tree traversal               | WORKING    |
| `Sdl\SdlTag`                  | Tests implemented            | PARTIAL    |
| `Sdl\Parser\SdlParser`        | Parsing tags and nested tags | WORKING    |
| `Sdl\Parser\SdlParser`        | Parsing tags with comments   |            |
| `Sdl\Parser\SdlParser`        | Tests implemented            | PARTIAL    |
| `Sdl\LiteralType\TypeFactory` | LiteralType registering      | PARTIAL    |
| `Sdl\LiteralType\TypeFactory` | Tests implemented            | PARTIAL    |
| `Sdl\LiteralType\*Type`       | All types implemented        | PARTIAL    |
| `Sdl\LiteralType\*Type`       | Tests implemented            | PARTIAL    |
| `Sdl\Selector\SdlSelector`    | Selecting with expressions   | PARTIAL    |
| `Sdl\Selector\SdlSelector`    | Tests implemented            |            |

### Functionality

| **Function**                                                 | **Status** |
|:-------------------------------------------------------------|:----------:|
| Creating tag trees                                           | WORKING    |
| Encoding tag trees into SDL                                  | WORKING    |
| Parsing SDL into tag trees                                   | PARTIAL    |
| Navigating the tag tree                                      | WORKING    |
| Selecting tags with expressions                              | PARTIAL    |
| Create LiteralTypes from native PHP variable types           | WORKING    |
| Create LiteralTypes from SDL tokens                          | PARTIAL    |
| Access LiteralTypes as native PHP values                     | PARTIAL    |
| Encode LiteralTypes into SDL tokens                          | PARTIAL    |

## Performance

The parser is currently a little slow at reading, but this can probably be
optimized somewhat. Either way it offers a tidy alternative to the established
serialization formats (XML, JSON and YAML). It is important to remember that
the parsers for XML, JSON and YAML are running as native code, while the SDL
parser is written in PHP.

That being said, it should be noted that php-sdl is best used with configuration
files that are not being requested at an excessive frequency (such as blog posts,
routing tables etc.) but rather for f.ex. job configurations, or immediate files
(like dumping blogposts into sdl for easy editing and import).

Caching is implemented as it was in the 1.x version of the parser, i.e. the
cache file with the parsed tags is placed in the same directory as the SDL-fil
being parsed, with an identical filename except prefixed with a dot (.) and
suffixed with ".cache". For example "foo.sdl" would be cached as ".foo.sdl.cache".
This behaviour might change in the future.

| **Format** | **Parser**                     | **10000x**    | **Calls/s**  |
|:----------:|:-------------------------------|--------------:|-------------:|
| SDL        | SdlParser::parseString         |         8.83s |      1132.50 |
| XML        | DomDocument::loadXml           |         0.43s |     23469.17 |
| JSON       | json_decode                    |         0.26s |     38640.62 |
| YAML       | yaml_parse_file                |         0.44s |     22738.22 |

Some possible improvements and optimizations include:

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

You should however be able to get back to the root using `getParent()` if you
ever needed to:

        function root($tag) {
            while(($parent = $tag->getParent())
                $tag = $parent;
            return $tag;
        }

### Parsing a file

To parse a file, use the `Sdl\Parser\SdlParser` class. It offers a few different
methods to parse content and return `Sdl\SdlTag` objects.

        use Sdl\Parser\SdlParser;
        // Parse a file
        $tag = SdlParser::parseFile("basic.sdl");
        // Parse a string
        $tag = SdlParser::parseString($sdl_string);

### Encoding tags to SDL

Tags are encoded into SDL using the `encode()` method. If you need to write it
out to a file, use `file_put_contents()` or any other appropriate method to
write out the output from `encode()`.

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

        // Enumerate children
        foreach($tag->getAllChildren() as $ctag) {
            printf("Tag: %s\n", $ctag->getTagName());
        }

### Queries

Queries make use of Symfony's ExpressionLanguage component to allow complex queries:

        use Sdl\Parser\SdlParser;
        use Sdl\Selector\SdlSelector;

        // Load the data
        $tag = SdlParser::parseFile(__DIR__."/sdl/products.sdl");
        // Create a new selector for the tag
        $tag_sel = new SdlSelector($tag);

        // Execute the query
        $expr = "/productcatalog/product[tag.attr('itemno')=='101-NAIL']";
        $item = $tag_sel->query($expr);

## Development

You can run the unit tests using **phpunit**:

      $ phpunit --bootstrap tests/bootstrap.php tests/src/

Just remember to create the autoloaders etc first using **composer**:

      $ composer dump-autoload

When contributing code, follow the conventions used elsewhere and send a pull
request with your masterpiece. If you're too lazy to fix something yourself, or
more likely busy saving the world elsewhere, create an issue so someone else
can take care of it.
