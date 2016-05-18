<?php
/*
 * This example demonstrates directly using Sdl\LiteralType\Sdl* types to 
 * represent dates, booleans, integer, floats etc. The constructor for the
 * literaltype always takes the "logical" php value as a parameter; to
 * parse the value from a literal SDL string, use the ::fromLiteral() static
 * method on the LiteralType.
 *
 * -----
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once __DIR__."/../vendor/autoload.php";

use Sdl\LiteralType\SdlDate;
use Sdl\LiteralType\SdlBoolean;
use Sdl\LiteralType\SdlFloat;
use Sdl\LiteralType\SdlInteger;
use Sdl\LiteralType\SdlBinary;
use Sdl\LiteralType\SdlNull;

function dump($v)
{
    if ($v instanceof \Sdl\LiteralType\LiteralType)
    {
        printf("LiteralType<%s>: %s\n", $v->getType(), $v->getSdlLiteral());
    }
    else
    {
        var_dump($v);
    }
}

// Creating dates
dump(new SdlDate("2014-01-01"));

// This will end up being true
dump(new SdlBoolean("no"));
// this one is false
dump(SdlBoolean::fromLiteral("no"));

// Floats can be cast to Ints. Note that SDL floats map to PHP doubles.
$my_float = new SdlFloat(3.14);
dump($my_float);
$my_int = $my_float->castTo(new SdlInteger);
dump($my_int);

// Binary data
dump(new SdlBinary("Hello World!"));
$data = substr(file_get_contents(__FILE__),0,100);
dump(new SdlBinary($data));

// Nulls
dump(new SdlNull);