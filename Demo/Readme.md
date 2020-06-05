---TODO First---

Instead of looking-up the role "Anonymous", should be refer to it by ROleID

Look into checking for duplicate unique entries: they seem to be allowed at the moment(and can cause trouble)

When signing-up for an event, auto-fill the MIID...

Fix vertical listings

If we have a pageLink in a module, add another option: to propogate instance privileges from the linked module to the current one.  This will be useful when designating command, so after adding them as Dir of Ops for the event, they can also edit all of the crews, etc.

Fix error with max length of field labels

https://www.youtube.com/v/kQ26uwaE1tw
---High Priority---

Help System
- Link on each page; provides explanation of the page, a description of each fields, and values those fields accept

Triggers
- Automatic emails
- Add/remove privileges
- Change module field instance values (eg. when a member drops a shift)
- Create/Delete Module Instances

Audit (ie. all changes get recorded in a DB table)
- Undo/Redo Support

Ability to backup/restore the database through the site web interface

Use jQuery UI Autocomplete widget for SelectField

Uploaded Files Browser
- Create a separate DB table for uploaded files
- <MIID, File location, orig file name>
- Use case: photo repository, organized by event, anyone can upload/download (ie. create folders, bulk upload/download)

Label should be an attribute of form field, not Module Field

Change the "Unique" mf attribute to refer to multiple fields

The Module Editor needs some re-writing
- The drop-down menu to select the module should instead be a list (like Form/Permission editors)
- No longer use a module/form
- Remove "hidden" attribute of modules

Option to only encrypt specific fields

New Field Type: Time


---Medium Priority---

Google calendar plugin

Duplicate a particular ModuleInstance (what to do about other MIIDs that link to it, copy them too?)

Reports
- Phone Tree Creator?

For combo-boxes, should have a separate div that stores all of the selected values, with a remove button next to each one

Bind keyboard enter to hitting submit button on many forms

Add 'Remember Me' Checkbox to logon page

Announcements Table/page
- Can probably be as simple as a Listing without options

Create a seperate universal error handler for use in all AJAX function
- And error messages should instead be ina  structured form: don't rely on an empty string to mean success

NavBar Editor

Improve Regex functionality with regards to usability for the average computer user


---Low Priority---

Listing Entry Highlighting

RSS Feeds

Equipment Purchase/Request

Option to compare a module instance with some template
-Can be used to create/grade quizzes
