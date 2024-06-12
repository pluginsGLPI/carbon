#!/bin/bash

#
# -------------------------------------------------------------------------
# carbon plugin for GLPI
# -------------------------------------------------------------------------
#
# MIT License
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
# -------------------------------------------------------------------------
# @copyright Copyright (C) 2024 Teclib' and contributors.
# @copyright Copyright © 2011 - 2021 Teclib'
# @license   MIT https://opensource.org/licenses/mit-license.php
# @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
# @link      https://github.com/pluginsGLPI/carbon
# -------------------------------------------------------------------------
#

# Clean existing file
rm -f locales/carbon.pot && touch locales/carbon.pot

# Append locales from PHP
xgettext `find ./front ./src ./*.php -type f -name "*.php"` -o locales/carbon.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing \
    --keyword=_n:1,2 --keyword=__s --keyword=__ --keyword=_x:1c,2 --keyword=_sx:1c,2 --keyword=_nx:1c,2,3 --keyword=_sn:1,2

# Append locales from JavaScript
# xgettext js/*.js -o locales/carbon.pot -L JavaScript --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing \
#     --keyword=_n:1,2 --keyword=__ --keyword=_x:1c,2 --keyword=_nx:1c,2,3 \
#     --keyword=i18n._n:1,2 --keyword=i18n.__ --keyword=i18n._p:1c,2 \
#     --keyword=i18n.ngettext:1,2 --keyword=i18n.gettext --keyword=i18n.pgettext:1c,2

# Append locales from Twig templates
for file in $(find ./templates -type f -name "*.twig")
do
    # 1. Convert file content to replace "{{ function(.*) }}" by "<?php function(.*); ?>" and extract strings via std input
    # 2. Replace "standard input:line_no" by file location in po file comments
    contents=`cat $file | sed -r "s|\{\{\s*([a-z0-9_]+\(.*\))\s*\}\}|<?php \1; ?>|gi"`
    cat $file | perl -0pe "s/\{\{(.*?)\}\}/<?php \1; ?>/gism" | xgettext - -o locales/carbon.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing \
        --keyword=_n:1,2 --keyword=__ --keyword=_x:1c,2 --keyword=_nx:1c,2,3
    sed -i -r "s|standard input:([0-9]+)|`echo $file | sed "s|./||"`:\1|g" locales/carbon.pot
done

#Update main language
#LANG=C msginit --no-translator -i locales/carbon.pot -l en_GB -o locales/en_GB.po

### for using tx :
##tx set --execute --auto-local -r GLPI.glpipot 'locales/<lang>.po' --source-lang en_GB --source-file locales/carbon.pot
## tx push -s
## tx pull -a
