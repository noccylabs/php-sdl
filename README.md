php-sdl 2.0
===========

This is an implementation of Simple Declarative Language (SDL) for PHP.

## Usage

Install with composer; `noccylabs/sdl:2.0.*`

## Examples

### Parsing a file

To parse a file, use the `Sdl\Parser\SdlParser` class. It offers a few different
methods to parse content and return `Sdl\SdlTag` objects.

        use Sdl\Parser\SdlParser;
        $tag = SdlParser::parseFile("basic.sdl");
        $tag = SdlParser::parseString($sdl_string);

### Enumeration
        
        // Enumerate children
        foreach($tag->children() as $ctag) {
            printf("Tag: %s\n", $ctag->getTagName());
        }

### Queries
        
        // xpath like queries (returns array)
        $tags = $tag->query("/colors/color");
        foreach($tags as $tag) {
            // Write out the SDL of the tag
            echo $tag->encode();
        }

        // same, for single tag
        $single_tag = $tag->queryOne("/colors/color[@name=red]");
        printf("Color: %s, Value: %s\n", $single_tag->name, $single_tag[0]);

### Generating

Generating is as simple. Get a new root tag with `createRoot()` and start 
adding your children.

        $tag = SdlTag::createRoot()
            ->createChild("people")
                ->createChild("person")
                    ->setValue("John Doe")
                    ->setAttribute("sex","male")
                    ->end()
                ->end();
        echo $tag->encode();

More examples: [examples/people.php](examples/people.php) [examples/fluid.php](examples/fluid.php)