php-sdl
=======

This is an implementation of Simple Declarative Language (SDL) for PHP.

## Usage

Install with composer; `noccylabs/sdl`

## Examples

    <?php
    
        $tag = SdlTag::createFromFile("foo.sdl");
        
        // Enumerate children
        foreach($tag->children() as $ctag) {
            printf("Tag: %s\n", $ctag->getNameNs());
        }
        
        // xpath like queries (returns array)
        $tags = $tag->query("/colors/color");
        foreach($tags as $tag) {
            // Write out the SDL of the tag
            echo $tag->encode();
        }
        
        // same, for single tag
        $single_tag = $tag->queryOne("/colors/color[@name=red]");
        printf("Color: %s, Value: %s\n", $single_tag->name, $single_tag[0]);

        // This should produce something like:
        // hello { 
        //   world 
        // }
        $new_tag = new SdlTag("hello");
        $child_tag = new SdlTag("world");
        $new_tag->addChild($child_tag);
        echo $new_tag->encode();
        
