<?php

namespace Sdl;

class SdlParserException extends \Exception {
    const ERR_INVALID_IDENTIFIER = 1;
    const ERR_NOT_IMPLEMENTED = 2;
    const ERR_RECURSION_MISMATCH = 3;
}
