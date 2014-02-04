php-sdl 2.0
===========

This is an implementation of Simple Declarative Language (SDL) for PHP. It has
not thing to do with Simple Directmedia Layer.

This is the v2.0 rewrite of php-sdl, and as such some things are not quite
working yet.

 * Not all `LiteralType`s are implemented. This is easily done now however, as
   each type is in its own folder.
 * The parser is broken still. 
 * Queries (SdlSelector) are not implemented yet.

## Usage

Install with composer; `noccylabs/sdl:2.0.*`

## Examples

### Generating

*IMPLEMENTED in php-sdl 2.0*

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

### Children

*Not yet implemented in php-sdl 2.0!*

        $people = $tag->getChildByTagName("people")->getAllChildren();
        echo "Person name: ".$people[0]->getValue()."\n";

### Enumeration

*IMPLEMENTED in php-sdl 2.0*

        // Enumerate children
        foreach($tag->getAllChildren() as $ctag) {
            printf("Tag: %s\n", $ctag->getTagName());
        }

### Parsing a file

To parse a file, use the `Sdl\Parser\SdlParser` class. It offers a few different
methods to parse content and return `Sdl\SdlTag` objects.

*Not yet implemented in php-sdl 2.0!*

        use Sdl\Parser\SdlParser;
        $tag = SdlParser::parseFile("basic.sdl");
        $tag = SdlParser::parseString($sdl_string);

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

