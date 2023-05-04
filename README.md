<img alt="Drupal Logo" src="https://www.drupal.org/files/Wordmark_blue_RGB.png" height="60px">


# custom_group

This module has the purpose of synchronizing two modules, namely the updated version of the group module and the domain group module. It comprises three significant elements:

- The first component is a file called "GroupIdFromUrl.php", which contains a default argument for group contextual filter.
- The second component is "GroupTitle.php", which includes an exposed group filter that allows for the group input box to be changed to a select list.
- The third component, "GroupService.php", provides several useful features related to groups, including the ability to retrieve the active group, get the group domain, and obtain related groups.

### Default Argument
This Argument allow the retrieval of the ID of the active group from the present domain name.
### Views filter
By using this custom filter, you can transform the input text into a selection list. Remember to activate the filter within the "hook_views_data_alter()" function.
### Group Service
This service contains several useful methods for interacting with both groups and nodes.
- getActiveGroup : This method enables the retrieval of the active group from the current host
- getGroupDomain : This method enables the retrieval of domain name using the group id
- getRelatedGroups : This method enables the retrieval of related groups to given node
- getFirstRelatedGroupLabel : This method enables the retrieval of the first related groups label 

If you have any questions, please feel free to contact me via email at ahmed.matri@sesame.com.tn.