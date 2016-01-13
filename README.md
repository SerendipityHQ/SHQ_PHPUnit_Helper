# SHQ_PHPUnit_Helper

This helper permits you to tear down your tests in an easy way.

It can also print some info about memory usage before and after tear down.

## Quick use

To quickly use PHPUnit_Helper, create a `TestCase.php` file and extend all your tests from this.

The helper is built as a `trait`, so you can use it in conjuction with other test cases (for example, the `WebTestCase` 
provided by LiipFunctionalTestBundle or the Symfony's `WebTestCase`.

    <?php
    
    /**
     * @package     PHPUnit_Helper
     *
     * @author      Adamo Crespi <hello@aerendir.me>
     * @copyright   Copyright (C) 2016.
     * @license     MIT
     */

    namespace AppBundle\Tests;
    
    use Liip\FunctionalTestBundle\Test\WebTestCase as BaseWebTestCase;
    use SerendipityHQ\Library\PHPUnit_Helper\PHPUnit_Helper;
    
    /**
     * Class WebTestCase
     *
     * @package AppBundle\Tests
     */
    class WebTestCase extends BaseWebTestCase
    {
        /** Here you include the trait in the test case */
        use PHPUnit_Helper;
    
        public function tearDown()
        {
            /** Measure the memory usage before tear down */
            $this->measureMemoryBeforeTearDown();

            /** Call the parent tear down method */
            parent::tearDown();
    
            /** CALL THIS AFTER the parent tear down method */
            $this->helpTearDown();
    
            /** Print in the console the information about memory usage */
            $this->printMemoryUsageInfo();
        }
    }

## Using the helper

During unit testing you need to:

1. Instantiate the class to test;
2. Create some mocks to emulate original classes used by the tested class;
3. Set some expected values.

All those operations can be handled by the helper in a simple and elegant way:



## Use of the reflection to tear down

The `PHPUnit_Helper::helpTearDown()` method by default sets to `null` the internal properties, without taking care of
properties of the tests.

If you are using the method with an existent test class, you may need helpful to use the method
`PHPUnit_Helper::useReflectionToTearDown()`.

Using this method you set to `true` an internal flag that tells to `PHPUnit_Helper` to use the reflection to set to
`null` all the found properties of the test class.

This is useful during the implementation to taking advantage of the tear down functionality, also if you don't still use
all the methods of the helper to manage your tests.
