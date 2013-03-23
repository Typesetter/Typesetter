/**
 * Ini brush for Code Syntax Highlighter http://alexgorbatchev.com/SyntaxHighlighter
 * by Boris Guéry, http://borisguery.com
 *
 *             DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 *                   Version 2, December 2004
 *
 * Copyright (C) 2004 Sam Hocevar «sam@hocevar.net»
 *
 * Everyone is permitted to copy and distribute verbatim or modified
 * copies of this license document, and changing it is allowed as long
 * as the name is changed.
 *
 *           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
 *  TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION
 *
 * 0. You just DO WHAT THE FUCK YOU WANT TO.
 *
 */

// CommonJS
typeof (require) != 'undefined' ? SyntaxHighlighter = require('shCore').SyntaxHighlighter
        : null;

function Brush() {

    var keywords = 'false true on off';

    this.regexList = [
    {
        regex : SyntaxHighlighter.regexLib.doubleQuotedString,
        css : 'string'
    }, // double quoted strings
    {
        regex : SyntaxHighlighter.regexLib.singleQuotedString,
        css : 'string'
    }, // single quoted strings
    {
        regex : /\b[-+]?[0-9]*\.?[0-9]+\b/g,
        css : 'number'
    }, // numbers (int or float)
    {
        regex : /;.*/g,
        css : 'comments'
    },
    {
        regex : /\[[a-z0-9:\-\s]+\]/gi,
        css : 'color3'
    },
    {
        regex: /\w+(\[\])*(?=\s*=)/g,
        css: 'variable'
    },
    {
        regex: new RegExp(this.getKeywords(keywords), 'gmi'),
        css: 'keyword'
    }
    ];
};

Brush.prototype = new SyntaxHighlighter.Highlighter();
Brush.aliases = ['ini'];

SyntaxHighlighter.brushes.Ini = Brush;

// CommonJS
typeof (exports) != 'undefined' ? exports.Brush = Brush : null;
