The second task was to make the bulk actions while manageing the courses my managers of moodle.

So the following files were created and some were changed whose patch will be uploaded soon.
// todo upload a patch of changed files i.e. lang/en/moodle.php and course/index.php

Contents - 

1. smanage.php
   - smanage.php is the main file of all, as it is the nearest replica of manage.php, which is used for managing courses in moodle. The categories can be managed by the same php script, on the same location.

2. staticoperations.php
   - Courses are managed statically by this file, a form which shows a course and required action is displayed, along with a dropdown menu of categories, which on selection shows the courses of that particular category.

3. action.php and actioncat.php
   - these are the action files.

Installation Guide of these files.
1. Simply move these files to .../moodle/course/ directory and access the smanage.php directly from url field.
2. If the patch from course/index.php is applied, then the smanage.php can also be accessed from clicking a button on the course/index.php page.
3. Rest is self explanatory.

Important note:
A patch file is uploaded, so apply the patch for moodle.php, with the command like $ patch -p1 < patchfile.diff, read more about applying a patch at http://docs.moodle.org/dev/How_to_apply_a_patch#Apply_a_Patch_in_Linux_using_.22patch.22
