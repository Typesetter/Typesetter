# Bootstrap 4.4.1 for Typesetter CMS #

This is a non-standard build of Bootstrap.
Typesetter CMS does server-side Scss compilation but it **does not** use Autoprefixer.
Given that, this implementation was largely modified in order to do vendor-prefixing via mixins as in Bootstrap 3.

When upgrading to a newer version of Bootstrap, these (more than 450) modifications must be reapplied manually. 
Otherwise there will be no vendor-prefixing at all and legacy browser support will be lost.
