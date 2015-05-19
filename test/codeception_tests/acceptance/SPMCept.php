<?php

global $ssI;
$ssI = new AcceptanceTester($scenario);

include 'test.php';

$ssI->wait(3);
