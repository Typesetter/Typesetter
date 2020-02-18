### Typesetter CMS ###

**About Typesetter CMS hooks**

Addon hooks are defined in the Addon.ini file of a plugin or theme and registered during its installation. During addon development, to add or remove hooks, the addon must be updated (or uninstalled / reinstalled) via Typesetter's admin menu.

**Filters**: Filter hooks are designed to manipulate certain values that are used in the execution of Typesetter. Depending on the hook, the output, the response to a certain request or the behavior of the system can be changed. Usually one or more parameters are passed to the hook function / method as an array or as an array of arrays. The called filter method will normally return the first passed parameter &ndash; in a modified form.

**Actions**: Contrary to filters, action hook calls do not expect a return value and they do not necessairly pass any parameters. The may, for example, be used to simply output content at the point in the CMS execution stack where the hook is called, such as the &lt;head&gt; section of a page. Action hook calls may also trigger the execution of custom actions (hence the name) such as modifying a file that was just uploaded.

Both filter and action hooks are commonly used to process global variables (e.g. $config), global objects (e.g. $page) or superglobals (e.g. $_REQUEST). It largely depends on the point where the hook is called if a global variable, object or object property is already available. For optimal performance, it is recommended to implement early exit conditions in hook methods to avoid unnecessary code execution. If a hook is used by several addons, its calls will be executed in the order of addon installation. This fact usually has no noticable effect but may play a role in plugin design under rather special circumstances, e.g. when addons themselves call other addon hooks.


## All Current Plugin Hooks ##

# A #

* [AdminLinkLabel](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27AdminLinkLabel%27%22)  						(filter) (new as of ver. 5.2)  
			Replace an Admin Link Label (of a plugin or theme) e.g. for internationalization, based on `$config('language')`

* [AllowedTypes](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27AllowedTypes%27%22)							(filter)  
			Change the file type extensions allowed to upload via finder

* [AntiSpam_Form](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27AntiSpam_Form%27%22)						(filter)  
			Append form elements to the contact form, e.g. for anti-spam measures

* [AntiSpam_Check](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27AntiSpam_Check%27%22)						(filter)  
			Check the submitted input values of the contact form, e.g. to verify anti-spam measures added via AntiSpam_Form

* [AvailableClasses](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27AvailableClasses%27%22)					(filter) (new as of ver. 5.2)  
			Dynamically filters the Available Classes array before loaded into the Manage Sections editor. Does not affect the saved classes via configuration.


# B #


# C #

* [CKEditorConfig](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27CKEditorConfig%27%22)						(filter)  
			Allows to change the CKEditor configuration in order to add&nbsp;/&nbsp;remove toolbar elements and others

* [CKEditorPlugins](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27CKEditorPlugins%27%22)					(filter)  
			Allows to add CKEditor plugins

* [CleanText](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27CleanText%27%22)								(filter)  
			Allows to modify strings after being filtered by the CleanText method

* [contact_form_check](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27contact_form_check%27%22)				(filter)  
			May be used to prevent sending an e-mail by returning `false`

* [contact_form_pre_captcha](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27contact_form_pre_captcha%27%22)	(action)  
			May be used to echo html / text before the output of a CAPTCHA in the contact form


# D #


# E #

* [edit_layout_cmd](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27edit_layout_cmd%27%22)					(action)  
			No description

* [ExecArea](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27ExecArea%27%22)									(filter)  
			May be used to modify parameters before they are passed to the `\gp\tool\Output::ExecInfo` function



# F #

* [FileDeleted](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27FileDeleted%27%22)							(action)  
			Allows to take actions after a file was deleted (via finder or gallery editor)

* [FileUploaded](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27FileUploaded%27%22)							(action)  
			Allows to take actions after a file was uloaded (via finder or gallery editor)

* [FinderOptionsClient](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27FinderOptionsClient%27%22)			(filter)  
			May be used to modify the client options of finder


# G #

* [GenerateContent_Admin](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27GenerateContent_Admin%27%22)		(action)  
				Add to all pages in case a user adds a gallery

* [GetAdminLink](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27GetAdminLink%27%22)							(action)  
			May be used to add output to the admin link (login / logout link)

* [GetContent_After](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27GetContent_After%27%22)					(action)  
			Gets called after the content has been send, so you can add your own stuff

* [GetDefaultContent](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27GetDefaultContent%27%22)				(filter)  
			Get the default content for the specified content type (section type)

* [GetHead](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27GetHead%27%22)									(action)  
			May be used to add output to the `<head>` section. Last chance to modify the page object before template.php is executed

* [GetMenuArray](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27GetMenuArray%27%22)							(filter)  
			Allows to modify a menu before rendering
			
* [GetUrl](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27GetUrl%27%22)										(filter)  
			May be used to filter urls generated by the `\gp\tool::GetUrl`function


# H #

* [HeadContent](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27HeadContent%27%22)							(action)  
			Gets called before ob_start() so plugins can get buffer content

* [Html_Output](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27Html_Output%27%22)							(filter)  
			Allows to filter the DOM array of the html output before saving it, e.g. to remove elements that shouldn't be saved


# I #

* [InlineEdit_Scripts](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27InlineEdit_Scripts%27%22)				(filter)  
			Define the script content sent to the editor component of a section type (via AJAX)


# J #


# K #


# L #

* [LoggedIn](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27LoggedIn%27%22)									(filter)  
			May be used to modify the boolean returned by the `\gp\tool\LoggedIn` function. Use it wisely.
			


# M #

* [MenuCommand](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27MenuCommand%27%22)							(filter)  
			Allows to modify the `$cmd` value used by menu editing operations

* [MenuPageOptions](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27MenuPageOptions%27%22)					(action)  
			Allows to add options (e.g. controls) to the menu editing callout in Page Manager

* [MenuPageTrashed](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27MenuPageTrashed%27%22)					(action)  
			May be used to take custom actions after a menu item (page) was moved to the trash

* [MetaTitle](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27MetaTitle%27%22)								(filter)  
			Allows to modify the content of the `<title>` tag in the `<head>` section


# N #

* [NewSections](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27NewSections%27%22)							(filter)  
			Used to define new section types that can be added to a page via 'Sections' (formerly 'Page') mode of the content editor

* [Notifications](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27Notifications%27%22)						(action) (new as of ver. 5.2)  
			Allows to use the methods of the `$notifications` object e.g. to add custom ones before output


# O #


# P #

* [PageCreated](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27PageCreated%27%22)							(action)  
			Allows to take additional actions when a new page was created

* [PageRunScript](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27PageRunScript%27%22)						(filter)  
			A mighty hook, called relatively early, that allows to execute own functions based on the passed cmd parameter in a request. It has complete control the type and content of the response / output

* [PageSetVars](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27PageSetVars%27%22)							(action)  
			A hook called relatively early to modify variables of the `$page` object

* [PostedSlug](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27PostedSlug%27%22)								(filter)  
			May be used to modify a slug posted by the user. Called after cleaning / filtering a slug by the `\gp\admin\Tools::PostedSlug` function


# Q #


# R #

* [RenameFileDone](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27RenameFileDone%27%22)						(action)  
			May be used to take custom actions once a file was deleted (via finder)

* [ReplaceContentVars](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27ReplaceContentVars%27%22)				(filter) (new as of ver. 5.2)  
			Allows to change existing or implement additional processing / replacement of variables used in content


# S #

* [SaveSection](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27SaveSection%27%22)							(filter)  
			Used to process posted data of certain / cutom section types before saving them

* [Search](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AAction%28%27Search%27%22)										(action)  
			Used to add custom content to be queried by Typesetter's built-in search 'engine'

* [SectionIsHidden](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27SectionIsHidden%27%22)					(filter)  
			Allows to change a content section's hidden state programmatically

* [SectionToContent](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27SectionToContent%27%22)					(filter)  
			This filter is used to return formatted content built from the $section_data array

* [SectionTypes](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27SectionTypes%27%22)							(filter)  
			Used with plugins to introduce / register new section types to the CMS

* [SimilarTitles](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27SimilarTitles%27%22)						(filter) (new as of ver. 5.2)  
			Hook to filter/remove similar page links suggested on the 'Missing' (Error 404) page and used for auto-redirection


# T #


# U #


# V #


# W #

* [WhichPage](https://github.com/Typesetter/Typesetter/search?q=%22Plugins%3A%3AFilter%28%27WhichPage%27%22)								(filter)  
			The WhichPage filter hook is one of the first hooks called hence it can be used for quite a lot of 'early stuff'.


# X #


# Y #


# Z #
