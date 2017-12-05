<?php

/**
 * This script helps to identify loading-order errors: You provide it a list
 * of "good" loading orders (as built by "renderLoadingTrace()" JS function),
 * and a list of "bad" loading orders which caused some error.
 *
 * Then in outputs a list of possibly missing dependencies, i.e. if it outputs:
 * neos/content/controller -> neos/content/model, the controller might miss
 * a dependency towards the model.
 *
 * INTERNALS
 *
 * The script expands all successful loading orders like "file1 -> file2" (meaning
 * that in *some* successful run, file1 was loaded before file2).
 *
 * Then, it expands each error run. For each loading dependency in the
 * error run, it checks whether this dependency can be found in *any* of the
 * successful runs. If so, this dependency cannot be responsible for the loading
 * order bug, as it was the same order in the successful run.
 *
 * If an error dependency like (a -> b) was NOT found in some successful run, it means
 * the following: "Because A was loaded before B, this might have caused the error".
 *
 * Thus, in order to fix these errors, the developer needs to check if A uses some
 * of B's code. If so, B needs to be loaded before A; so A needs to depend on B.
 *
 * In a nutshell: file A must define() that it depends on B.
 */
$successfulRuns = array(
json_decode('["jquery","emberjs","emberjs/dictionary-object","vie/entity","storage","jquery-ui","vie","neos/content/model","neos/common","create","neos/content/controller","neos/content/ui/contentelement-handles","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/elements/new-contentelement-button","neos/content/ui/elements","neos/content/ui","neos/contentmodule","contentmodule-main"]'),
json_decode('["jquery","emberjs","jquery-ui","vie","emberjs/dictionary-object","vie/entity","storage","neos/content/model","neos/common","create","neos/content/controller","neos/content/ui/contentelement-handles","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/elements/new-contentelement-button","neos/content/ui/elements","neos/content/ui","neos/contentmodule","contentmodule-main"]'),
json_decode('["jquery","emberjs","jquery-ui","vie","emberjs/dictionary-object","vie/entity","storage","neos/content/model","neos/common","create","neos/content/controller","neos/content/ui/contentelement-handles","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/elements/new-contentelement-button","neos/content/ui/elements","neos/content/ui","neos/contentmodule","contentmodule-main"]'),
json_decode('["jquery","emberjs","jquery-ui","vie","emberjs/dictionary-object","vie/entity","storage","neos/content/model","neos/common","create","neos/content/controller","neos/content/ui/contentelement-handles","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/elements/new-contentelement-button","neos/content/ui/elements","neos/content/ui","neos/contentmodule","contentmodule-main"]'),
json_decode('["jquery","emberjs","emberjs/dictionary-object","jquery-ui","vie","vie/entity","storage","neos/content/model","aloha","neos/common","create","neos/content/controller","neos/content/ui/elements/button","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/contentelement-handles","neos/content/ui/section-handles","neos/content/ui/elements","neos/content/ui/editors","neos/content/ui","neos/contentmodule","contentmodule-main"]'),
);

$errorRuns = array(
'Uncaught TypeError: Expecting a function in instanceof check, but got [object Object]' => json_decode('["jquery","emberjs","emberjs/dictionary-object","jquery-ui","vie","vie/entity","storage","neos/content/model","aloha","create","neos/common","neos/content/controller","neos/content/ui/elements/button","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/contentelement-handles","neos/content/ui/elements","neos/content/ui/editors","neos/content/ui","neos/contentmodule","contentmodule-main"]'),


"Uncaught TypeError: Cannot read property 'Collection' of undefined" => json_decode('["jquery","emberjs","emberjs/dictionary-object","jquery-ui","vie","aloha","neos/common","neos/content/ui/elements/button","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/contentelement-handles","neos/content/ui/elements","neos/content/ui/editors","neos/content/ui"]'),

"Uncaught TypeError: Cannot read property 'Collection' of undefined _Resources/Static/Packages/Neos.Neos/Library//vie/vie-latest.js:1" => json_decode('["jquery","emberjs","emberjs/dictionary-object","jquery-ui","vie","aloha","neos/content/ui/elements/button","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/common","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content"]'),

'Uncaught ReferenceError: CodeMirror is not defined' => json_decode('["jquery","emberjs","emberjs/dictionary-object","jquery-ui","vie","vie/entity","storage","neos/content/model","aloha","create","neos/common","neos/content/controller","neos/content/ui/elements/button","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/contentelement-handles","neos/content/ui/elements","neos/content/ui/editors","neos/content/ui","neos/contentmodule","contentmodule-main"]'),
'Uncaught ReferenceError: CodeMirror is not defined (2)' => json_decode('["jquery","emberjs","emberjs/dictionary-object","jquery-ui","vie","aloha","neos/content/ui/elements/button","neos/common","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/contentelement-handles","neos/content/ui/elements","neos/content/ui/editors"]'),

'Uncaught ReferenceError: CodeMirror is not defined (3)' => json_decode('["jquery","emberjs","emberjs/dictionary-object","jquery-ui","vie","aloha","neos/common","neos/content/ui/elements/button","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/contentelement-handles","neos/content/ui/elements","neos/content/ui/editors","neos/content/ui"]'),

"Uncaught TypeError: Cannot read property 'NodeSelection' of undefined _Resources/Static/Packages/Neos.Neos/JavaScript/neos/content/controller.js:151" =>
json_decode('["emberjs","jquery-ui","emberjs/dictionary-object","aloha","neos/common","vie","vie/entity","storage","create","neos/content/controller","neos/content/model","neos/content/ui/elements/button","neos/content/ui/elements/toggle-button","neos/content/ui/elements/popover-button","neos/content/ui/elements/toolbar","neos/content/ui/elements/new-contentelement-popover-content","neos/content/ui/contentelement-handles","neos/content/ui/elements","neos/content/ui/editors","neos/content/ui","neos/contentmodule","contentmodule-main"]')
);

$allBuiltClauses = array();
foreach ($successfulRuns as $runIndex => $successfulRun) {
    for ($i = 0; $i < count($successfulRun); $i++) {
        for ($a = $i+1; $a < count($successfulRun); $a++) {
            $allBuiltClauses[] = $successfulRun[$i] . ' -> ' . $successfulRun[$a];
        }
    }
}

$clausesForErrorRuns = array();
foreach ($errorRuns as $index => $errorRun) {
    $clausesForErrorRuns[$index] = array();
    for ($i = 0; $i < count($errorRun); $i++) {
        for ($a = $i+1; $a < count($errorRun); $a++) {
            $clausesForErrorRuns[$index][] = $errorRun[$i] . ' -> ' . $errorRun[$a];
        }
    }
}


$possibleErrorClauses = array();

foreach ($clausesForErrorRuns as $index => $errorRunClauses) {
    foreach ($errorRunClauses as $errorRunClause) {
        if (!in_array($errorRunClause, $allBuiltClauses)) {
            $possibleErrorClauses[] = $errorRunClause;
        }
    }
};
printf('----------------


%s

Found %d orders which might be responsible for loading order problems.
', implode("\n", $possibleErrorClauses), count($possibleErrorClauses));
