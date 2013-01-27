=== Advanced Page Manager ===
Contributors: Uncategorized Creations
Tags: pages, page, manage, management, page management, tree, rearrange, order, reorder, hierarchical, admin, cms, content management
Requires at least: 3.4.2
Tested up to: 3.5.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A new way to create, move, edit and publish your pages for your favorite CMS.

== Description ==

**It is hard to manage Wordpress pages when it comes to have more than 10 static pages, right ?** Current UI makes it difficult to manage a whole tree of pages. Advanced Page Manager aims to create a totally new Page Manager Panel designed to help you get the job done.

Discover how in 3 minutes (better in HD or 480p).

[youtube http://www.youtube.com/watch?v=Sj3046LGefg]

= So, what should you expect from this plugin ? =

* **An easy understand tree.** All your pages are presented in an easy to understand tree with all necessary actions to take care of them.
* **Browse, Show/hide subpages.** The plugin even remembers the state of each page so that you won't have to browse again and again the tree to find the same page.
* **Classic actions.** Of course, classic actions such as *Edit*, *Preview*/*View* are still there !
* **Add, move and delete pages from within the tree.** But you will also be able to add, move and delete pages from within the tree. And no more cumbersome drag/drop to move pages. We have created a zen way for you to do that :-) Also, you don't have to edit to add a page anymore. Just click the *Add New* button.
* **New Status for pages.** Wordpress Status are fine for posts but sound odd for pages. With APM, your pages are online or... offline. Simple no ?
* **Publish/unpublish.** Push online (or offline) one or more pages directly from the tree.
* **Template Management.** Affect or change template for 1 page or any selected range of pages.
* **Select pages.** We also provide an easy way to select all subpages for a given parent page.
* **Where is my page ?** From the *Edit* panel, the result lists or even the theme itself, you'll be able to find your page in the tree thanks to the *Where my page?* button.
* **Search and filter.** Search for pages. Filter to get the list of all online or offline pages. Sort by column in result lists.
* **No clumsy interface.** As you, we love the Wordpress UI. You'll feel at home with APM. The plugin interface matches Wordpress standards.
* **Theme integration.** We also respect your theme. Pages are still pages even managed by APM. No need to change your templates.
* **Template Tags.** However, we provide a new set of Template Tags to handle pages in your themes. Feel free to use them if you need to.
* **Don't like it ?** Under the hood, it's still Wordpress. If you uninstall APM, your pages will be there because it's your content and we care for it.
* **Don't speak English ?** APM is also available in French and Dutch. Want it in your language ? All strings are available to translation in the [Wordpress way](http://codex.wordpress.org/Translating_WordPress).

**Download and install Advanced Page Manager to fully enjoy it !**

Don't forget to keep up with APM at [http://www.uncategorized-creations.com/](http://www.uncategorized-creations.com/) or follow our Twitter account : [@uncatcrea](https://twitter.com/UncatCrea).

== Installation ==

Advanced Page Manager doesn't require specific action to be installed. Just follow the regular process :

1. Upload `advanced-page-manager` to the `/wp-content/plugins/` directory

1. Activate the plugin through the *Plugins* menu in WordPress

1. Click the standard *Pages* item in the admin menu to access the new management panel

== Frequently Asked Questions ==

= What happen to the regular Wordpress pages when the plugin is installed ? =
Well nothing at all. The plugin creates a new management panel and still relies on the regular Wordpress pages. However we do use a new way to store page relationships to speed up display. Also we don't create any new database table and regular relationships are maintained. If you uninstall the plugin, pages will be there safe and sane.

= Does the plugin have hooks ? =
Currently, APM has some hooks but not a consistent way. We are going to develop a lot more hooks in the post 1.0 era.

= Is the plugin compatible with the Wordpress menu builder ? =
Yes. The plugin manages regular pages and the menu builder has still access to them.

= Do I have to modify my theme ? =
No. Regular page template tags and functions still work. Regular page template and permalinks are also supported. Also, we do have new template tags, however it is not mandatory to use them.

= Does the plugin support extra columns in the page tree and lists ? =
We've looked at the WP core itself and to several plugins making use of those extra columns (as Simply Exclude or Wordpress SEO for example). We found 2 things. First, using the WP hook is very difficult as it expects to be on the original panel. Second, plugins insert themselves in many ways into the Pages panel (eg. bringing new scripts testing the current URL). So unfortunatly, we came to the conclusion that we won't support extra columns as it will introduce to much specific and unstable code in our plugin. However, we perfectly understand the importance of this feature for the users and the developpers. So we've added following hooks to add extra columns : apm_manage_pages_columns, apm_manage_pages_custom_column and apm_load_wp_data.

= How do I report a bug ? =
Please, use the *Support* tab. However, remember this is not a commercial support of any kind. We check regularly the coming requests and questions and try to keep up with answering them. But we also have regular jobs and... lifes. One more detail : at the moment, we're all located in France. So if you are in another timezone, remember that even french sleep (yes I know, weird).

= Do you accept patches ? =
Yes (and we thank you in advance if so). All patches will be validated by our lead developer. If accepted, you'll be mentionned as contributor to the plugin (if you accept so). To submit a patch please report in the *Support* tab.

= Do you have a website for this plugin ? =
Yes and... no :-) Home for APM is here : [http://www.uncategorized-creations.com/](http://www.uncategorized-creations.com/). At the moment, it's a single page with a logo. You can leave your email address to get fresh news about APM. In the future, it will be a complete website. In the meantime, you can also follow us on Twitter : [@uncatcrea](https://twitter.com/UncatCrea).

= Which version of Wordpress do you support ? =
We support Wordpress 3.4.2 and 3.5 since version 0.9. We don't plan to support earlier versions.

= Which version of browsers do you support ? =
All developments have been done under the last version of Chrome, Firefox, Safari and Internet Explorer (Windows 7).

= Which language do you support ? =
By default, APM is in English and French. If you wish to, you can translate the interface in your own language [in the standard Wordpress way](http://codex.wordpress.org/Translating_WordPress).

= What is Uncategorized Creations ? =
*Uncategorized Creations* is the name chosen by a bunch of (french) Wordpress addicts (technical or not). Advanced Page Manager is their first creation. Please see the *Other Notes* tab for more details.


== Screenshots ==

1. Pages are presented as a nice tree. Clicking on the *arrow* will fold (or unfold) subpages.
2. Rollover a page will reveal the *Action Menu* for this page : *Rename*, *Preview*/*View*, *Publish*, *Edit*, *Template*, *Move* and *Delete* (if you're connected with the admin role).
3. You can select a page. And, if it has subpages, you have access to a submenu to select/unselect its subpages.
4. By default, new pages are offline. Clicking the *Publish* action link will publish/unpublish it instantaneously.
5. Clicking on the *Template* action link opens a side panel where you can change the page template.
6. You can select several pages and apply one of the bulk actions : *Publish*/*Unpublish*, *Change Template* or *Delete* (if you're connected with the admin role).
7. Click on the *Add New* button (on the right of each page), it opens a side panel to add one (or more) page(s). You can choose the template to apply, the position of the new page and you can also create more than one page at a time.
8. To move a page, click the *Move* action link. Then browse the page tree and decide where to drop the selected page.
9. You have access to the lists of all pages online or offline. You can also search. In result lists, the *Where is it?* button allows to switch back to the tree where the page is displayed in context.
10. When editing a page, the *Page Attributes* metabox allows to change template. You have a *Where is it?* button to switch back to the tree and you can edit next, previous, parent and subpages without returning to the tree.

== Changelog ==

= 1.0 =
* 0000042: Unable to (un)fold subpages when moving pages
* 0000044: Add New side panel remains open after all pages have been deleted
* 0000045: After folding/unfolding subpages, Move layer disappears
* 0000046: Add a pointer after plugin activation
* 0000047: Add Ajax spinner

= 0.9 =
* 0000040: [Wordpress 3.5.0] Edit Parent is always active
* 0000038: [Wordpress 3.5.0] Wrong labels top padding for move layer button
* 0000037: Cancel the... Cancel link :-)
* 0000036: [Wordpress 3.5.0] Wrong template dropdown liste width overflow
* 0000035: [Wordpress 3.5.0] Wrong top margin for Add New button
* 0000034: [Wordpress 3.5.0] No red flash feedback after actions
* 0000029: Search label not positioned correctly
* 0000013: Custom columns added by other plugins not supported

= 0.8.5 =
* 0000033: Move link no more available after (un)publishing
* 0000032: Page stays selected after closing the template side panel
* 0000029: Search label not positioned correctly

= 0.8 =
* 0000026: Selecting a row will update the Change template panel after one more click (thanks to Lionet Pointet)
* 0000025: Current template message with only the last selected page which has a custom template (thanks to Lionel Pointet)
* 0000009: Pages with 'auto-draft' status appear as empty rows when loading the tree from WP pages (thanks to Lionel Pointet)
* Recent Pages list has been reactivated following user request.

= 0.7.5 =
* 0000022: Impossible to fold/unfold pages after adding subpages
* 0000021: Error message doesn't disappear in Add New site panel
* 0000019: After creating first page, After radio button is unchecked (thanks to Thibaut Cotti)
* Adds Chinese (zh_CN) translation (thanks to Weiwei Guo)

= 0.7 =
* 0000020: Clicks on After, Before, Subpage labels should check their corresponding checkboxes
* 0000017: Move layer doesn't scale vertically
* 0000016: Add New layer doesn't scale vertically
* 0000015: Rows in tree doesn't scale vertically properly when content is big
* 0000014: When renaming, title field allows empty value
* 0000012: Subpages icon is positioned under the arrow
* 0000011: Move link available when tree has only one page

= 0.6.5 =
* 0000002: No position selected in the Add New side panel when clicking (again) the Add New button
* 0000004: Ajax layer not covering the whole width of the tree
* 0000005: Add New layer doesn't cover the whole width of the page slot
* 0000006: When creating the first page, Page Selected label is displayed
* 0000007: After creating the first page, Add New panel remains open
* 0000008: On the move layer, cancel button should be a link
* 0000009: Pages with 'auto-draft' status appear as empty rows when loading the tree from WP pages
* 0000010: Simple quotes are backslashed in french translation feedback messages
* Cleans and secures (nonce) the options panel
* Forces redirection of standard WP pages list to our cutomized pages tree (if it happens that someone gets there by any other way)
* Removes obsolete config constants
* Uses of WP global vars to test current admin page (thanks to Lionel Pointet)
* User cap from “activate_plugins” to “manage_options” for the APM settings panel (thanks to Lionel Pointet)
* Adds Dutch (nl_NL) translation (thanks to Ron Hartman)
* Fixes wrong “Selected” total on “Select all”
* Updates tree data at plugin re-installation (to handle new pages added while plugin was deactivated)
* New message when security check fails
* Optimization : only one query to retrieve pages data, whatever their status is + one global query to load pages meta data, using WP cache on meta data
* Handles the case where a page doesn't exist in WP (deleted from outside the plugin) but is still in APM tree

= 0.6 =
Beta 1 released

== About ==
Advanced Page Manager has been designed and developed by a group of Wordpress addicts doing professional Wordpress projects (among other things like living a normal life). The idea of Advanced Page Manager emerged as we were working on news sites with a lot of pro contents that were... not news but kind of knowledge base (eg. best practices, tutorials...). It was obvious that we could do those contents with standard posts but we had also to twist them in a way we felt as not as a good way to go. So we decided to give a boost to Wordpress pages to match a more CMS like management. But we also felt that we had to respect Wordpress (UI of course and also technically speaking). We are very happy to release it after 4 months of hard work and hope that you will enjoy it in your own projects.

We'd like to thank the [*Groupe Moniteur*](http://www.groupemoniteur.fr/), a french B2B news company, that allowed us to develop this project. More specificaly, we thank Caroline Tessier and Claire de Smedt, both wonderful project managers for their help (and patience).

* Benjamin Lupu : interface design, project management
* Mathieu Le Roi : lead developer, technical design
* Maxime Breton : frontend development
* Adrian Koss : web design, icons

*Advanced Page Studio logo : © M.studio - Fotolia.com* | *Uncategorized Creations logo : © M.studio - Fotolia.com*