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

namespace Sdl;

/**
 * Description of SdlUtils
 *
 * @author noccy
 */
class SdlUtils
{
        /**
     * Check if the specified identifier is valid per SDL 1.2
     * 
     * From the SDL language guide: An SDL identifier starts with a unicode
     * letter or underscore (_) followed by zero or more unicode letters,
     * numbers, underscores (_), dashes (-), periods (.) and dollar signs
     * ($).
     *
     * @param type $identifier
     * @return type
     */
    public static function isValidIdentifier($identifier)
    {
        // Check if this identifier has a namespace, and if so check the parts
        // independently and return true only if both are valid.
        if (strpos($identifier,":")!==false)
        {
            list($namespace,$identifier) = explode(":",$identifier,2);
            return (self::isValidIdentifier($namespace) &&
                    self::isValidIdentifier($identifier));
        }
        // Check if the string is a valid identifier.
        return (preg_match("/^[_a-zA-Z]{1}[_\-\.\$a-zA-Z0-9]*/", $identifier));
    }

}
