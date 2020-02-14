## Logic Hook Manager

#### Script to automatically manage SuiteCRM logic hook entries.

Run this script with PHP on the command line:

```$ php ./updatehooks.php```

It will search for files ending in .php in the 'logichooks' subdirectory. This repo is
configured to ignore the 'logichooks' directory so that another git repo can be used
at that location. In any case, you'll need to create that directory.

For each of these files it will configure the Logic Hook Method as described in the methods
PhpDoc header's '**@logichooktab**' entry.

The '@logichooktab' entry has the following format:

```@logichooktab [label] [module] [sort] [event]```

Here are the elements:

**label**: This is a short description of the hook entry. You can have spaces in the string as 
long as it's enclosed with double quotes.

**module**: The module the hook is for. It's case sensitive, and must match a valid module. You
will receive an error if it is invalid.

**sort**: The sort order for this hook. If you make this the string 'null' (case insensitive)
then the system will pick the next available sort slot.

**event**: The event the hook is for, like 'after-save', etc.

Here is an example:
```
/**
 * This hook method does the thing... the thing with the bean.
 * 
 * There might be a long description here. One that has a lot of
 * big words and multiple lines. Who knows, anything is possible.
 * The updatehooks.php script won't care about it. It's only
 * looking for the lines below:
 *
 * @logichooktab "update Assigned User Related Label" Accounts null after_save
 * @logichooktab updateAssignedUserRelatedLabel Accounts NULL before_save
 */
```

You can pass a `-D` option to get some debugging output. Otherwise the script will output
that it's removing and adding hooks.

TIPS:
If a method doesn't have a '@logichooktab' entry in its doc block, this script will skip
it, and nothing will be changed.

If a method has an empty '@logichooktab' entry, as in it just says `@logichooktab` and
doesn't define any options, that method will be removed from the logic_hook.php for
every module.

I would recomend including this tab in every logic hook method, to insure the
logic_hook.php files remain clean.

If you move a method to a different class, or rename a method, after already running
this script, it will not know to remove the old one. If you do move it, I would leave
an empty function in the original class, and give it an empty '@logichooktab' entry.
This will cause the old one to be removed from all the modules.

Renaming files will not cause any issues. The script uses only the class and method
to address hooks.
