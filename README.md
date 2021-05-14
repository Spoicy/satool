# SA-Tool

This is the Git repository for the SA-Tool plugin.

## Installation

**This Plugin requires PHP >=7.2 and Moodle >=3.7 to guarantee functionality.**

To install this Moodle plugin, clone the repository and place it into the "local" folder of your Moodle installation i.e. local/satool. Upon reloading your Moodle website, it will begin the installation of the plugin. Alternatively, after cloning the repository you can remove the .git folder and put the plugin into a zipped folder with the name "local_satool_moodle[moodle version]_[plugin version].zip", i.e. "local_satool_moodle37_2021051400.zip", which you can then upload to moodle via the "Install plugins" page in the Site administration and continue the same installation process as previously mentioned.

In order for the SA manager to access the course creation page, an additional system role has to be created:
* Go to Site administration and go to the "Define roles" page under the "Users" section.
* Click on "Add a new role"
* Select no roles, archetypes or presets and click "Continue"
* Give it an appropriate name such as "SA-Manager" and under "Context types where this role may assigned", select System. Scroll down and search for "satool" in the Capabilities section, and set both to "Allow". Then click on create role.
* Once the role is created, you can assign the role to the SA manager.