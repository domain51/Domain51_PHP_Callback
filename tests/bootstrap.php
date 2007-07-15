<?php

set_include_path(
    dirname(__FILE__) . PATH_SEPARATOR .
    dirname(__FILE__) . '/../src' . PATH_SEPARATOR .
    get_include_path()
);

require_once 'simpletest/autorun.php';
require_once 'simpletest/ui/colortext_reporter.php';

if (empty($_ENV['KOMODO_VERSION'])) {
    $reporter = SimpleReporter::inCli() ? new ColorTextReporter() : new HtmlReporter();
    SimpleTest::prefer(clone $reporter);
}
unset($reporter);
