### New in Typesetter CMS 5.2 (release pending) ###

## New System Requirements ##

**PHP 5.6 - 7.4:** Typesetter 5.2 officially requires at least PHP 5.6 and has been tested up to PHP 7.4. PHP 5.3 and 5.4 are no longer supported.


## Thirdparty Components and Libraries ##

* Bootstrap 4: Typesetter now adds support for Bootstrap 4.4.1 and ships the 'Bootswatch 4 Scss' theme. There is also a new Bootstrap 4 preset loadable for 'Available Classes'. But no worries, Bootstrap 3 and all its Bootswatch themes are still there.
* elFinder 2.1.50: Our file manager was updated to the most recent version and comes with a fresh new look and some interesting new features. E.g. see [here](https://www.typesettercms.com/Blog/elFinder_2.1.50_in_Upcoming_Release)
* CK Editor 4.13: We now use the most recent version from the CK Editor 4 branch.
* jQuery 2.2.4: We would have even chosen jQuery 3, but Bootstrap 3 doesn't play along.
* Less.php 1.8.1: With the newest version of our LESS CSS compiler we gain some performance boost and support for less 2.5.3
* ScssPhp 1.0.5: Version 1.0.5 comes with better support for recent development of the the Scss language.
* Colorbox 1.6.4: Merely an update to the latest version but we also added a modern Colorbox style called 'Minimalistic'
* PHPmailer 5.2.27: fixes a security issue.


## Security ##
* SVG upload: SVG file upload is now prevented by default but can be activated during setup or via configuration. Admin 'accepts' a security warning about possible malicious scripting in SVGs from untrusted sources.


## CSS Compilation ##
* LESS and SCSS compilers now generate compressed CSS.


## Configuration Options ##
* minify JS: Combined JavaScript can now be minified.
* allow SVG upload (as noted above)
* Hide Admin UI: set viewport with threshold and keyboard shortcut (see below)


## Admin User Interface and Editing ##
* Admin Panel (AKA top bar): we re-arranged some items here to save more space, e.g. for links added by plugins.
* Page Revision History: page revisions moved from the former modal box to a sidebar (similar to Layout Editor) which makes preview and restoration more convenient.
* Notifications: Contrary to messages (the yellow bar) which rather show immediate feedback, the new notificaton system shows things that an administrator should pay attention to sooner or later, such as working drafts, pages set to 'visibility: private', available updates and debugging warnings. Notifications persist unless their issues are solved but can be muted.
* Section Attributes: Attribute value fields now grow multi-line if required. Available Classes now use a more compact representation and tooltips for descriptions.
* CK Editor skin and built-in plugins: We now use the more modern, monochromatic 'moono-lisa' skin. The Source code dialog now features CodeMirror with syntax highlighting and pretty printing.
* Image sections now have an 'Alternative Text' input field.
* Gallery Editor: gallery style variants can now be choosen via icons instead of dropdowns.
* The 'Plugins &raquo; Available' list (merely for developer installations) will now be sorted alphanmerical.
* Manage sections: Admin actions in (now called) 'Sections' editor mode will be saved immediately. Internal data changes like wrapper collapse state and changes to color and text labels will not cause a new working draft of the page anyomre.
* Manage sections: [Ctrl] + click bypasses the 'Are you sure you want to … ?' dialog and immediately removes sections.
* Layout Editor now handles themes with vh units and flexbox on the html and body elements.
* Hide the Admin UI: All administration user interface elements, such as the admin menu, the top bar, open messages, modal boxes, the editor area and all editing overlays will be hidden automatically as soon as the viewport width falls below a certain threshold (992px by default). This enables a better assessment of the page as it is shown to normal visitors, especially on mobile devices. Additionally the admin ui can always be hidden / shown by clicking the new icon in the top-left corner of the viewport, or by pressing the keyboard shortcut [Ctrl] + [H]. The auto-hide threshold as well as the keyboard shortcut can be customized via Configuration -> Settings -> Hide Admin UI.
* Messages: the yellow message bar is now responsive which means that it remains visible and closable in narrow viewports. If you are logged in, it can even be resized so that e.g. longer debug messages can be displayed better.


## Supported Languages ##
* Thanks to GitHub user sveinki, Typsesetter now also speaks Icelandic.
* There are also many improvements to already existing translations.


## Extra Content ##
* Extra Content Areas visibility can now be set, globally or per page.
* Working Drafts of Extra Content Areas can now be dismissed before being published.


## Development ##
* General note: We made quite a lot of changes, even architectural ones, but with backward compatibility in mind. If your custom code is broken using Typesetter 5.2, we will most likely be able to assist you via GitHub issues or the forum on TypesetterCMS.com
* Theme development: Bootstrap based themes shipping with Typesetter now have a new Addon.ini section called 'FrontEndFramework' wich declares Bootstrap and version used by the theme. This makes server-side framework detection possible and allows plugins to adapt their output accordingly. Although it's not mandatory, please consider adding this information to all new themes, even if they use other frameworks like Foundation, Materialize, you name it.
* Supported PHP versions: Typesetter dropped support for PHP 5.3 to 5.5 and added support for PHP up to version 7.4.
* CSS helper classes: In addition to body.gpAdmin we now have html.gpEditing (when editor is open) html.isPrivate (on private pages) and html.gpAdmin (for consistency).
* Popper.js, the JS positioning library behind Bootstrap tooltips is now also available outside of Bootstrap themes as loadable component. 
\gp\tool::LoadComponents('popper'); Popper.js will be loaded by default when you are logged in. 
* LoadComponents: \gp\tool::LoadComponents() is now more robust and also accepts CSV strings with spaces and arrays.
* Error management: Catching and reporting of fatal errors was improved for better debugging and stability.
* CSS source mapping for LESS and Scss: We added the configuration constant 'create_css_sourcemaps' to gpconfig.php for better CSS debugging during theme (or plugin) development. Note: When set to true, CSS combination is prevented, regardless of whether it is activated via configuration.
* JavaScript events: Besides 'SectionAdded', 'SectionRemoved' and 'SectionSorted' there is now a 'SectionCopied' JS event. Typesetter now also fires a 'section_options:closed' event (we already had 'section_options:loaded' before). Furthermore, there is now 'editor:loaded' which is triggered everytime a section editor is loaded or re-activated. This event contains additional data of the loaded editor and corresponding section.


## New Plugin Hooks ###
* 'AdminLinkLabel' filter hook allows better plugin internationalization.
* 'AvailableClasses' filter hook can be used to dynamically manipulate the Available Classes array.
* 'Notifications' filter hook can be used to manipulate Notifications before they are displayed.
* 'ReplaceContentVars' is a new filter hook to manage content variables to be replaced in output, such as $myName (in content) => John Doe (in output).
* 'SimilarTitles' filter hook to manipulate similar page links shown on the 'Missing' (Error 404) page and used for automatic redirection


## Bug Fixes ##
* Section Clipboaard now also works with nested content structures of any depht.
* Forgotten password: New password sent via e-mail now also works with 'password_hash' algorithm. 
* Change password: Now also works with 'password_hash' algorithm. 
* Fix page corruption with too many sections: With lots of sections, posting discrete values came to its limits (depending on server config) and saving a page failed. We now post all in a single JSON value, which is safe.
* Multiple section operations in quick succession: It now shouldn't be possible anymore to cause 'Not a Draft' and 'Invalid Section Number" errors (fingers crossed ;).


## New Bugs ##
* Although we worked hard to fix them all, there may still be new bugs. Please feel free to report them on https://github.com/Typesetter/Typesetter/issues or in the [forum on TypesetterCMS.com](https://www.typesettercms.com/Forum)


## Thanks ##
Many thanks to all beta testers and contributors who made this new version possible. Especially mahotilo, sestowner, gtbu, a2exfr and some others.
