<?php

/*
 * This example demonstrates how it is possible to create tags and trees of tags
 * easily using symfony2-style fluid programming. All the setX() methods return
 * an instance of the tag, the createChild() spawns a new child tag and adds it
 * to the current tag before returning the child tag. To get back into the
 * context of the parent tag, use end().
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

use Sdl\SdlTag;

// Change the comment style from the default '//' to '--'
Sdl\SdlComment::setCommentStyle(Sdl\SdlComment::STYLE_DASH);

echo SdlTag::createRoot()
    ->createChild("configuration")
        ->createComment("Database configuration")
        ->createChild("database")
            ->createChild("connection")
                ->setValuesFromArray(["pdo+sqlite://@APP_DATA/master.db"])
                ->setAttribute("charset", "UTF-8")
            ->end()
            ->createChild("cache")
                ->setValue("appdata://cache/")
            ->end()
        ->end()
        ->createComment("Directories and search paths")
        ->createChild("directories")
            ->createChild("data")
                ->setValuesFromArray(["./data/"])
            ->end()
        ->end()
    ->encode();
