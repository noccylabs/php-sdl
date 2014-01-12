# SDL Overview

*(From http://107.20.201.134/display/SDL/Language+Guide)*

SDL (Simple Declarative Language) was designed to provide a terse and perspicuous format for
describing common data structures and data types. Although XML is an excellent format for marking up
documents, embedding in free form text, and creating graphs it can be a cumbersome language for
expressing basic datastructures. SDL is particularly well suited for this purpose. Lists, maps,
trees, tables, and matrixes can be easily expressed in SDL. Following is a list of examples
demonstrating construction of various datastructures using typed data.

Example 1: Creating a List
 
    numbers 12 53 2 635
    
Example 2: Creating a Map
 
    pets chihuahua="small" dalmation="hyper" mastiff="big"

Example 3: Creating a Tree
 
    plants {
        trees {
            deciduous {
                elm
                oak
            }
        }
    }
    
Example 4: Creating a Matrix
 
    myMatrix {
       4  2  5
       2  8  2
       4  2  1
    }
    
Example 5: A Tree of Nodes with Values and Attributes

    folder "myFiles" color="yellow" protection=on {
        folder "my images" {
            file "myHouse.jpg" color=true date=2005/11/05
            file "myCar.jpg" color=false date=2002/01/05
        }
        folder "my documents" {
            document "resume.pdf"
        }
    }

Because of its terse syntax and type inference capabilities, SDL is ideally suited to applications
such as

 * Configuration Files
 * Build Files
 * Property Files
 * Simple Object Serialization
 * Log files (formatting and parsing)

SDL was designed to be language agnostic. Currently APIs exist for Java and .NET (written in C#.)
C++, Python and Ruby ports are planned.

## Tags

A tag can contain a namespace, a name, a value list, attributes (with namespaces), and children. All
components are optional. If the name portion is ommited the tag name defaults to "content".
Namespaces default to the empty space (""). Names and namespaces are identifiers.

Tags are written using the form:

    namespace:name values attributes {
        children
    }

Tags are terminated with a new line (\n) or the ending bracket of a child list (}). Lines can be
continued by escaping the new line like so:

    values 3.5 true false "hello" \
        "more" "values" 345 12:23:41

Values are space separated literals and attributes are space separated key value pairs using the
format:

    namespace:key=value

The namespace portion is optional. The namespace and key are SDL identifiers and the value is an SDL
literal.

Children are SDL tags and may be nested to an arbitrary depth. They are indented by convention but
tabs are not significant in the language.

As of SDL 1.1 tags can be listed separated by semicolons on the same line:

    tag1; tag2 "a value"; tag3 name="foo"

## The Tag Data Structure

Tag values and children are modelled as lists. Order is significant and duplicates are allowed.

### Equality Test (Java)

    Tag tag1 = new Tag("root").read("nums 7 3");
    Tag tag2 = new Tag("root").read("nums 3 7");
    System.out.println(tag1.equals(tag2));
    // Will print "false".  Value order is significant.
    
Tag attributes are modelled as a sorted map. Order is not significant and duplicates are not
allowed.

### Equality Test (C#)

    Tag tag1 = new Tag("root").ReadString("lights kitchen=on bathroom=off");
    Tag tag2 = new Tag("root").ReadString("lights bathroom=off kitchen=on");
    Console.WrintLine(tag1.Equals(tag2));
    // Will print "true".  Attribute order is not significant.
    
## Anonymous Tags

Tags with no name are known as anonymous tags. They are automatically assigned the name "content".

Example: An Anonymous Tag

    greetings {
       "hello" language="English"
    }

    # If we have a handle on the "greetings" tag we can access the
    # anonymous child tag by calling
    #    Tag child1 = greetingTag.getChild("content");

Note: Anonymous tags must have at least one value

Anonymous tags must have one or more values. They cannot contain only attributes. This design
decision was taken to avoid confusion between a tag with a single value and an anonymous tag
containing only one attribute.

    # Not allowed: An anonymous tag with a single attribute (and no values)...
    size=5

    # ...because it could easily be confused with a tag having a single value
    size 5

## Identifiers

An SDL identifier starts with a unicode letter or underscore (_) followed by zero or more unicode
letters, numbers, underscores (_), dashes (-), periods (.) and dollar signs ($). Examples of valid
identifiers are:

    myName
    myName123
    my-name
    my_name
    _my-name
    my_name_
    com.ikayzo.foo

## Literals

SDL supports 13 literal types. They are (parenthesis indicate optional components):

 * unicode string - examples: "hello" or `aloha`
 * unicode character - example: '/' - Note: \uXXXX style unicode escapes are not supported (or
needed because sdl files are UTF8)
 * integer (32 bits signed) - example: 123
 * long integer (64 bits signed) - examples: 123L or 123l
 * float (32 bits signed) - examples 123.43F or 123.43f
 * double float (64 bits signed) - example: 123.43 or 123.43d or 123.43D
 * decimal (128+ bits signed*) - example: 123.44BD or 123.44bd
 * boolean - examples: true or false or on or off
 * date yyyy/mm/dd - example 2005/12/05
 * date time yyyy/mm/dd hh:mm(:ss)(.xxx)(-ZONE)
 * time span using the format (d:)hh:mm:ss(.xxx)
 * binary [standard Base64] example - [sdf789GSfsb2+3324sf2]
 * null A literal for a null value (must be lower case) example - null

Notes: For platforms that do not support this level of precision, decimal should resolve to the most
accurate decimal representation possible.

## String Literals

There are two ways to write a string literal. Double quoted literals begin and end with a double
quote ("). They cannot span lines unless the new line is escaped. If the new line is escaped, all
white space to the left of the first non-white space character in the next line is ignored. For
example, if we write

    test "john \
        doe"
        
The test tag's value will be "john doe". The space before the escape is preserved, but the space
before the "d" in "doe" is ignored. White space characters (\n\r\t ), backslashes () and double
quotes (") must be escaped in double quote literals.

Examples: Double Quote String Literals

    name "hello"
    line "he said \"hello there\""
    whitespace "item1\titem2\nitem3\titem4"
    continued "this is a long line \
        of text"

The second type of string literal is the backquote (`) literal. It functions much like Python's
triple quote (""") or C#'s at quote (@""). All characters including whitespace between backquotes
are preserved. It is not necessary (or possible) to escape any type of character in a backquote
literal.

Examples: Backquote String Literals

    winfile `c:\directory\myfile.xls`
    talk `I said "something"`
    xml `
    <product>
       <shoes color="blue"/>
    </product>
    `
    regex `\w+\.suite\(\)`

Note: SDL interprets new lines in backquote String literals as a single new line character (\n)
regarless of the platform.

## Binary Literals

Binary literals use base64 characters enclosed in square brackets ([]). The binary literal type can
also span lines. White space is ignored.

Examples: Binary Literals

    key [sdf789GSfsb2+3324sf2] name="my key"
    image [
        R3df789GSfsb2edfSFSDF
        uikuikk2349GSfsb2edfS
        vFSDFR3df789GSfsb2edf
    ]
    upload from="ikayzo.org" data=[
        R3df789GSfsb2edfSFSDF
        uikuikk2349GSfsb2edfS
        vFSDFR3df789GSfsb2edf
    ]

## Date and Date/Time Literals

SDL supports date and date/time literals. Date and date/time literals use a 24 hour clock (0-23). If
a timezone is not specified, the default locale's timezone will be used.

Examples: Date and Date/Time Literals

    # create a tag called "date" with a date value of Dec 5, 2005
    date 2005/12/05
     
    # a date time literal without a timezone
    here 2005/12/05 14:12:23.345

    # a date time literal with a timezone
    in_japan 2005/12/05 14:12:23.345-JST

Note: Timezones must be specified using a valid timezone ID (ex. America/Los_Angeles), three letter
abbreviation (ex. HST), or GMT(+/-)hh(:mm) formatted custom timezone (ex. GMT+02 or GMT+02:30)

## Time Span Literals

SDL Time Span literals represent a length of time (which may be negative.) TimeSpan literals are
useful for expressing the duration of an event, intervals, or chronological distances from a
reference point.

Examples: Time Span Literals

    hours 03:00:00
    minutes 00:12:00
    seconds 00:00:42
    short_time 00:12:32.423 # 12 minutes, 32 seconds, 423 milliseconds
    long_time 30d:15:23:04.023 # 30 days, 15 hours, 23 mins, 4 secs, 23 millis 
    before -00:02:30 # 2 hours and 30 minutes ago
    about_two_days_ago -2d:00:04:00 


note 1: hours, minutes, and seconds are required - days and milliseconds are optional

note 2: if the day component is included it must be suffixed with a lower case 'd'


## Comments

SDL supports four comment types.

The first three are line comment types. Line comments can start with a #, //, or --. Everything
between the beginning of the single line comment and the new line is ignored. The – style
separator comment is often used to visually separate sections like so:

    ints 1 2 3
    doubles 5.0 3.1 6.4

    ------------------

    lists {
        6 3 5 1
        'a' 'r' 'q'
        "bag" "of" "tricks"
    }

The fourth type of comment is the /* */ style multiline comment used in Java and C family languages.
Everything between the /* and */ is ignored. /* */ comments may span lines and occur in the middle
of lines.

Examples: Multiline Comments

    myInts 1 2 /* 3 */ 4 // note: this list will contain 1, 2 and 4

    tag1 "fee"
    /*
    tag2 "fi"
    tag3 "fo"
    */
    tag4 "fum"

## SDL Files

SDL files (any file ending with the extension .sdl) should always be encoded using UTF-8. The use of
unicode escaping (such as the \uxxxx format used by Java and C#) is not supported or required. Non
ASCII characters should be entered directly using a UTF-8 capable editor.

Note: ASCII is transparently encoded in UTF8, so ASCII files can be used if only ASCII characters
are required.

Example SDL File

    # a tag having only a name
    my_tag

    # three tags acting as name value pairs
    first_name "Akiko"
    last_name "Johnson"
    height 68

    # a tag with a value list
    person "Akiko" "Johnson" 68

    # a tag with attributes
    person first_name="Akiko" last_name="Johnson" height=68

    # a tag with values and attributes
    person "Akiko" "Johnson" height=60

    # a tag with attributes using namespaces
    person name:first-name="Akiko" name:last-name="Johnson"

    # a tag with values, attributes, namespaces, and children
    my_namespace:person "Akiko" "Johnson" dimensions:height=68 {
        son "Nouhiro" "Johnson"
        daughter "Sabrina" "Johnson" location="Italy" {
            hobbies "swimming" "surfing"
            languages "English" "Italian"
            smoker false
        }
    }   

    ------------------------------------------------------------------
    // (notice the separator style comment above...)

    # a log entry
    #     note - this tag has two values (date_time and string) and an 
    #            attribute (error)
    entry 2005/11/23 10:14:23.253-GMT "Something bad happened" error=true

    # a long line
    mylist "something" "another" true "shoe" 2002/12/13 "rock" \
        "morestuff" "sink" "penny" 12:15:23.425

    # a long string
    text "this is a long rambling line of text with a continuation \
       and it keeps going and going..."
       
    # anonymous tag examples

    files {
        "/folder1/file.txt"
        "/file2.txt"
    }
        
    # To retrieve the files as a list of strings
    #
    #     List files = tag.getChild("files").getChildrenValues("content");
    # 
    # We us the name "content" because the files tag has two children, each of 
    # which are anonymous tags (values with no name.)  These tags are assigned
    # the name "content"
        
    matrix {
        1 2 3
        4 5 6
    }

    # To retrieve the values from the matrix (as a list of lists)
    #
    #     List rows = tag.getChild("matrix").getChildrenValues("content");

