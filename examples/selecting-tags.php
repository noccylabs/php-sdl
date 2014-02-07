<?php
/* 
 * Copyright (C) 2014 NoccyLabs.info
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

use Sdl\Parser\SdlParser;
use Sdl\Selector\SdlSelector;

$tag = SdlParser::parseFile(__DIR__."/sdl/products.sdl");

echo "Input:\n";
echo $tag->encode();
echo "\n";

$tag_sel = new SdlSelector($tag);

$expr = "/productcatalog/product[tag.attr('itemno')=='101-NAIL']";
echo "Query: {$expr}\n";
$deps = $tag_sel->query($expr);
foreach($deps as $dep)
{
    echo $dep->encodeTag();
}