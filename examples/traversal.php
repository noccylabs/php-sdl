<?php
/*
 * This example demonstrates how to traverse the node tree and work with child
 * tags.
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

use Sdl\Parser\SdlParser;

$tag = SdlParser::parseFile(__DIR__."/sdl/complex.sdl");

$packages = $tag
        ->getChildrenByTagName("package");
$pkg_name = $packages[0]
        ->getChildrenByTagName("package:name")[0]
        ->getValue();
echo "Package name: {$pkg_name}\n";


