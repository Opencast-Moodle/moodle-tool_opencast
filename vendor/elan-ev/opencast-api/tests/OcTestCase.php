<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

#[\AllowDynamicProperties]
class OcTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        echo "\n====== #START Testing (" . get_called_class() . ") ======\n";
    }

    public static function tearDownAfterClass(): void
    {
        echo "\n====== #END Testing (" . get_called_class() . ") ========\n---\n";
    }
}
?>
