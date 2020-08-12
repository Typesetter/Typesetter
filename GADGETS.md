### Typesetter CMS ###

## Built-in Gadgets ##
* Contact
* Search
* Admin Link
* Login Link

Gadgets can be added to the layout via Layout Manager or to the content via File Include sections. Gadgets can also be called programmatically from template.php via gpOutput::GetGadget(the_gadget_id)

## Output functions, which can be called staticall from template.php  ##

* GetAdminLink($messages=true)  
			Outputs the sitemap link, admin login/logout link, powered by link and messages


* GetSitemapLink()  
			Outputs only the sitemap link


* GetLoginLink($force_show=false)  
			Outputs only the login/logout link. force_show=true allows to ignore site settings


* GetPoweredByLink($always_show=false)  
			Outputs only the powered_by link


Output functions can be called staticall from template.php. E.g., gpOutput::GetSitemapLink();
