<?php

/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2020 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 * 
 * @author Benjamin Long <ben@offsite.guru>
 */

 /*  ############  HOW TO USE  ############
 Run this script with PHP on the command line:
 $ php ./updatehooks.php

 It will search for files ending in LogicHook.php in the same directory.

 For each of these files it will configure the Logic Hook Method as described in the methods
 PhpDoc header's '@logichooktab' entry.

 The '@logichooktab' entry has the following format:
    @logichooktab [label] [module] [sort] [event]

 Here are the elements:
 label: This is a short description of the hook entry. You can have spaces in the string as 
    long as it's enclosed with double quotes.

 module: The module the hook is for. It's case sensitive, and must match a valid module. You
    will receive an error if it is invalid.

 sort: The sort order for this hook. If you make this the string 'null' (case insensitive)
    then the system will pick the next available sort slot.

 event: The event the hook is for, like 'after-save', etc.

 Here is an example:

    * This hook method does the thing... the thing with the bean.
    * 
    * There might be a long description here. One that has a lot of
    * big words and multiple lines. Who knows, anything is possible.
    * The updatehooks.php script won't care about it. It's only
    * looking for the lines below:
    *
    * @logichooktab "update Assigned User Related Label" Accounts null after_save
    * @logichooktab updateAssignedUserRelatedLabel Accounts NULL before_save

 You can pass a '-D' option to get some debugging output. Otherwise the script will output
 that it's removing and adding hooks.

 TIPS:
 If a method doesn't have a '@logichooktab' entry in it's doc block, this script will skip
 it, and nothing will be changed.

 If a method has an empty '@logichooktab' entry, as in it just says @logichooktab' and
 nothing else, that method will be removed from the logic_hook.php for every module.

 I would recomend including this tab in every logic hook method, to insure the
 logic_hook.php files remain clean.

 */

// Make sure we're not running over the web.
if (php_sapi_name() !== "cli") {
    die("Must be run from CLI.\n");
}

// Check for debug option
global $dbug;
$dbug = 0;
$argv[] = null;
if ($argv[1] === '-D'){
    $dbug = 1;
    echo "Running in Debug Mode.\n";
}

/**
 * Simple Debug Output Function
 * @param String message to output
 * 
 * @author Benjamin Long <ben@offsite.guru>
 */ 
function debugOutput($text) {
    global $dbug;
    if($dbug === 1){
        echo $text;
    }
}

// This finds the root of the SuiteCRM install by iterating up though the the file system until it finds the suitecrm_version.php file.
chdir(__DIR__);
$filename = "suitecrm_version.php";
$dir = __DIR__;
while ($dir != '/'){
    if (file_exists($dir.'/'. $filename)) {
        $rootDir = getcwd();
        break;
    } else {
        chdir('../');
        $dir = getcwd();
    }
}
if (!isset($rootDir)) {
    die("Failed to find SuiteCRM root!\n");
}

// sanity check some things
debugOutput('Current Directory: ' . getcwd() . "\n");
debugOutput("Root Dir: " . $rootDir . "\n");

// Going to need this for later
if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

// Tie Into SuiteCRM
require_once('include/entryPoint.php');

/**
 * Check against the list of modules to validate input
 * @param String module name
 * 
 * @author Benjamin Long <ben@offsite.guru>
 * @return Bool pass/fail
 */ 
function validateModuleName($modulename) {
    if (in_array($modulename, $GLOBALS['app_list_strings']['moduleList'])) {
        return true;
    }
    return false;
}

/**
 * Get a list of methods under class file, pass them downstream
 *
 * @param Method $method The Method object we are running on
 * 
 * @author Benjamin Long <ben@offsite.guru>
 */ 
function getHookFunctionList($filename) {
    $classListBefore = get_declared_classes();
    require_once $filename;
    $classNew = array_diff(get_declared_classes(), $classListBefore);

    foreach ($classNew as $class) {
        debugOutput("Found Class: " . $class ."\n");
        // get methods
        $methods = get_class_methods($class);
        $reflec = new ReflectionClass($class);
        foreach ($methods as $methodStr) {
            $method = $reflec->getMethod($methodStr);
            getMethodLogicHookTab($method);
        }
    }
}

/**
 * Outputs an array of LogicHook tab entries for a method
 *
 * @param Method $method The Method object we are running on
 * 
 * @author Benjamin Long <ben@offsite.guru>
 */ 
function getMethodLogicHookTab($method) {
    //print_r($method);
    remove_logic_hook_everywhere($method);
    //die();
    $array = Array();
    $docStr = $method->getDocComment();
    $line = strtok($docStr, "\r\n");
    $searchfor = "/^\s*\*\s*\@logichooktab/";
    while ($line !== false) {
        if(preg_match($searchfor, $line) === 1 ) {
            debugOutput("Retrieved logichooktab line: " . $line . "\n");
            $linearray = parseLogicHookTabString($line);
            if (empty($linearray)) {
                break;
            }

            // validate the requested SuiteCRM module
            if (!validateModuleName($linearray['module'])) {
                echo "ERROR: Method \"" . $method->getName() . "\" specifies invalid module \"" . $linearray['module'] . "\"\n";
                break;
            }

            // validate and convert the requested sort-order
            if(strtolower($linearray['sortorder']) === 'null'){
                $linearray['sortorder'] = null;
            } elseif(!ctype_digit($linearray['sortorder'])) {
                echo "ERROR: Method \"" . $method->getName() . "\" specifies invalid sort-order \"" . $linearray['sortorder'] . "\". Must be numeric or 'null'.\n";
            break;
            }

            $linearray['class'] = $method->getDeclaringClass()->name;
            $linearray['method'] = $method->getName();
            $linearray['file'] = str_replace(getcwd() . "/" , "", $method->getFileName());

            $array['module'] = $linearray['module'];
            unset($linearray['module']);
            $array['event'] = $linearray['event'];
            unset($linearray['event']);

            $array['hook'] = $linearray;

            if(!empty($array)) {
                //remove_logic_hook_everywhere($array);
                enableLogicHook($array);
            }
        }
        $line = strtok("\r\n");
    }
}

/**
 * Writes the logic hook array to the file.
 *
 * @param Array The logic hook aray
 * 
 * @author Benjamin Long <ben@offsite.guru>
 */ 
function enableLogicHook($array) {
    $newHook = Array(
        $array['hook']['sortorder'],
        $array['hook']['label'],
        $array['hook']['file'],
        $array['hook']['class'],
        $array['hook']['method']
    );
    echo "Adding hook \"" .  $array['hook']['class'] . "::" . $array['hook']['method'] . " in module \"" . $array['module'] . "\" for event \"". $array['event'] . "\"\n";
    check_logic_hook_file($array['module'], $array['event'], $newHook);
}

/**
 * Parses a LogicHook tab string and returns an array
 *
 * @param String $tabstring The string we parse
 * 
 * @author Benjamin Long <ben@offsite.guru>
 * @return Array
 */ 
function parseLogicHookTabString($tabstring) {
    $cleanString = ltrim(trim(preg_replace('!\s+!', ' ', $tabstring)), "/ "); // Collapse all the whitespace into single spaces, trim all whitespace, remove "/'s" from beginging of string
    //print_r(str_getcsv($cleanString, ' '));
    //die();
    //$array = array_slice(explode(" ", $cleanString), 2, 4);
    $array = array_slice(str_getcsv($cleanString, ' '), 2, 4);

    if(empty($array)) {
        return Array();
    }

    $output = Array(
        'label' => $array[0],
        'module' => $array[1],
        'sortorder' => $array[2],
        'event' => $array[3]
    );
    return $output;
}

/**
 * Removes a Logic Hook from all modules. Matches only Function and Class from action array.
 *
 * @param Array $method The method object were removing from every logic_hook.php file.
 * 
 * @author Benjamin Long <ben@offsite.guru>
 */ 
function remove_logic_hook_everywhere($method) {
    require_once 'include/utils/logic_utils.php';
    $add_logic = false;
    $method_name = $method->getName();
    $method_class = $method->getDeclaringClass()->getName();
    foreach($GLOBALS['app_list_strings']['moduleList'] as $module_name) {
        $needToWrite = false;
        if (file_exists('custom/modules/' . $module_name . '/logic_hooks.php')) {
            // The file exists, let's make sure the hook is there
            $hook_array = get_hook_array($module_name);
            foreach(array_keys($hook_array) as $event) {
                foreach ($hook_array[$event] as $i => $hook) {
                    if ($hook[3] == $method_class && $hook[4] == $method_name) {
                        unset($hook_array[$event][$i]);
                        $needToWrite = true;
                    }
                }
            }
            if($needToWrite) {
                echo "Removing hook \"" .  $method_class . "::" . $method_name . " in module \"" . $module_name . "\"\n";
                $new_contents = replace_or_add_logic_type($hook_array);
                write_logic_file($module_name, $new_contents);
            }
        }
    }
}

// Loop though the files
foreach (glob(dirname(__FILE__) . "/*LogicHook.php") as $filename) {
    debugOutput("Parsing file: $filename" . "\n");
    getHookFunctionList($filename);
}
?>