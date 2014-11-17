<?php

use DataSift\Storyplayer\PlayerLib\StoryTeller;
use DataSift\Storyplayer\Prose\E5xx_ActionFailed;

include '../plugin/swifty-page-manager/lib/swifty_plugin/php/probe/ss_story.php';

$story = $GLOBALS['story'] = newStoryFor('Wordpress')
    ->inGroup('Swifty Page Manager')
    ->called('Test Swifty Page Manager general behaviour.');

$story->addTestSetup( function( StoryTeller $st ) {
    $ssStory = $GLOBALS['ssStory'];
    $ssStory->st = $st;
    $ssStory->ori_story = $GLOBALS['story'];
    $ssStory->TestSetup();
} );

$story->addTestTeardown( function( StoryTeller $st ) {
    $ssStory = $GLOBALS['ssStory'];
    $ssStory->st = $st;
    $ssStory->TestTeardown();
} );

$story->addAction( function( StoryTeller $st ) {
    $ssStory = $GLOBALS['ssStory'];
    $ssStory->st = $st;
    $ssStory->TakeAction();
} );

$story->addPostTestInspection( function( StoryTeller $st ) {
//    $st->assertsString($this->data->testText)->equals("Akismet");
} );

