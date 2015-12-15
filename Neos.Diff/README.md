[![BSD License](https://img.shields.io/github/license/mashape/apistatus.svg)]
[![Latest Stable Version](https://poser.pugx.org/neos/diff/version)](https://packagist.org/packages/neos/diff)

# Diff Library

This is a repackaged and modernized version of Chris Boulton's PHP Diff
Library. It has been transformed to the Neos namespace and is working out
of the box with Composer's and Flow's auto loading mechanism. This library
is compatible with PHP 5 (tested with 5.5 and 5.6) and PHP 7.

Note: Even though this library is rather stable and has not been modified
by its original author for years, the Neos Team does not actively maintain
all contained renderers.

## Features

This is a comprehensive library for generating differences between
two hashable objects (strings or arrays). Generated differences can be
rendered in all of the standard formats including:

 * Unified
 * Context
 * Inline HTML
 * Side by Side HTML

The logic behind the core of the diff engine (ie, the sequence matcher)
is primarily based on the Python [difflib package](https://docs.python.org/2/library/difflib.html). The reason for doing
so is primarily because of its high degree of accuracy.


## License (BSD License)

Portions Copyright by Contributors of the Neos Project - www.neos.io

Copyright (c) 2009 Chris Boulton <chris.boulton@interspire.com>
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

 - Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.
 - Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.
 - Neither the name of the Chris Boulton nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
