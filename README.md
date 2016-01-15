[![Latest Stable Version](https://poser.pugx.org/serendipity_hq/phpunit_helper/v/stable)](https://packagist.org/packages/serendipity_hq/phpunit_helper)
[![Total Downloads](https://poser.pugx.org/serendipity_hq/phpunit_helper/downloads)](https://packagist.org/packages/serendipity_hq/phpunit_helper)
[![Latest Unstable Version](https://poser.pugx.org/serendipity_hq/phpunit_helper/v/unstable)](https://packagist.org/packages/serendipity_hq/phpunit_helper)
[![License](https://poser.pugx.org/serendipity_hq/phpunit_helper/license)](https://packagist.org/packages/serendipity_hq/phpunit_helper)
[![Code Climate](https://codeclimate.com/github/SerendipityHQ/SHQ_PHPUnit_Helper/badges/gpa.svg)](https://codeclimate.com/github/SerendipityHQ/SHQ_PHPUnit_Helper)
[![Test Coverage](https://codeclimate.com/github/SerendipityHQ/SHQ_PHPUnit_Helper/badges/coverage.svg)](https://codeclimate.com/github/SerendipityHQ/SHQ_PHPUnit_Helper/coverage)
[![Issue Count](https://codeclimate.com/github/SerendipityHQ/SHQ_PHPUnit_Helper/badges/issue_count.svg)](https://codeclimate.com/github/SerendipityHQ/SHQ_PHPUnit_Helper)
[![StyleCI](https://styleci.io/repos/49512498/shield)](https://styleci.io/repos/49512498)

# SHQ_PHPUnit_Helper

This helper permits you to tear down your tests in an easy way.

It can also print some info about memory usage before and after tear down.

## How to install `PHPUnit_Helper`

To install `PHPUnit_Helper` use Composer:

    composer require serendipity_hq/phpunit_helper

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

All those operations can be handled by the helper in a simple and elegant way.

This is a sample code to test an hypothetical `Order` entity:

    /**
     * Tests all get and set methods
     */
    public function testOrder()
    {
        $this->setResourceToTest(new Order());

        $this->addHelpMocksCollection('purchase',
            $this->generateMocksCollection($this->getMock('\AppBundle\Entity\Purchase'), 3),
            true
        )
            ->addExpectedValue('id',          1)
            ->addHelpMock('channel',          $this->getMock('\AppBundle\Entity\Store'), true)
            ->addHelpMock('placedBy',         $this->getMock('\AppBundle\Entity\Customer'), true)
            ->addExpectedValue('placededOn',  new \DateTime('2015-04-12 00:08'))
            ->addExpectedValue('modifiedOn',  new \DateTime('2015-04-12 00:08'))
            ->addExpectedValue('completedOn', new \DateTime('2015-04-12 00:08'))
            ->bindExpectedValuesToResource();

        $this->assertEquals($this->getExpectedValue('id'),          $this->getTestingResource()->getId());
        $this->assertEquals($this->getExpectedValue('channel'),     $this->getTestingResource()->getChannel());
        $this->assertEquals($this->getExpectedValue('placedBy'),    $this->getTestingResource()->getPlacedBy());
        $this->assertEquals($this->getExpectedValue('placedOn'),    $this->getTestingResource()->getPlacedOn());
        $this->assertEquals($this->getExpectedValue('modifiedOn'),  $this->getTestingResource()->getModifiedOn());
        $this->assertEquals($this->getExpectedValue('completedOn'), $this->getTestingResource()->getCompletedOn());
        $this->assertEquals($this->getExpectedValue('id'),          $this->getTestingResource()->__toString());
        $this->assertEquals($this->getExpectedCount('purchase'),    count($this->getTestingResource()->getPurchases()));

        $this->getTestingResource()->removePurchase($this->removeMockFromMocksCollection(1, 'purchase'));
        $this->assertEquals($this->getExpectedCount('purchase'), count($this->getTestingResource()->getPurchases()));
    }

### Using `$this->setResourceToTest()`

Using `$this->setResourceToTest()` you can tell the helper which is the tested resource.
Knowing this, the helper can help you to populate it after you've added some expected values.

### Using `$this->addHelpMocksCollection()` and `$this->generateMocksCollection()`

To test the `add*()` and `remove*()` methods you have to create a lot of mocks of the same kind of objects.

Maybe your `add*()` methods appear to be very similar to this:

    /**
     * Add a Product
     *
     * @param \AppBundle\Entity\Purchase $purchase
     * @return $this
     */
    public function addPurchase(Purchase $purchase)
    {
        $this->purchases[] = $purchase;
        $purchase->setInOrder($this);

        return $this;
    }

The old way to test this method is something like this:

    $resource = new Order();
    
    $testPurchases = [
        $this->getMock('\AppBundle\Entity\Purchase'),
        $this->getMock('\AppBundle\Entity\Purchase'),
        $this->getMock('\AppBundle\Entity\Purchase')
    ];
    
    foreach ($testPurchases as $purchase)
        $resource->addPurchase($purchase);
        
Using the `PHPUnit_Helper::generateMocksCollection()` method, you only need to write one line of code:

    $this->addHelpMocksCollection('purchase',
        $this->generateMocksCollection($this->getMock('\AppBundle\Entity\Purchase'), 3),
        true
    )

As name of the collection use the name of the `add*()` method without the `add` part (in the sample `Order` entity,
the method to add a `Purchase` is called `Order::addPurchase()`).

Using a collection makes you able to use the methods `PHPUnit_Helper::getExpectedCount({name_of_the_collection})` and 
`PHPUnit_Helper::removeMockFromMocksCollection({key}, {name_of_the_collection})`.

By default `PHPUnit_Helper::removeMockFromMocksCollection()` also removes the mock from the expected values, but you can
disable this behavior passing the optional third parameter and setting it to `false`.

### Using `PHPUnit_Helper::->bindExpectedValuesToResource()`

Adding expected values and calling them as the property of your class, makes you able to use the method `PHPUnit_Helper::->bindExpectedValuesToResource()`.
This can automatically populate your set tested resource (that you set through `PHPUnit_Helper::setResource()`) with
expected values, so you don't have to populate it manually.

## Use of the reflection to tear down the tests

The `PHPUnit_Helper::helpTearDown()` method by default sets to `null` the internal properties, without taking care of
properties of the tests.

If you are using the method with an existent test class, you may find helpful to use the method
`PHPUnit_Helper::useReflectionToTearDown()`.

Using this method you set to `true` an internal flag that tells to `PHPUnit_Helper` to use the reflection to set to
`null` all the found properties of the test class.

This is useful during the implementation to taking advantage of the tear down functionality, also if you don't still use
all the methods of the helper to manage your tests.

## Measuring the memory usage during tests

You can measure the memory usage before and after the tear down using the methods `$this->measureMemoryBeforeTearDown()` 
and `$this->printMemoryUsageInfo()`. This last method automatically calls `$this->measureMemoryAfterTearDown()` if it is not
yet called.

So you can explicitly call it in you tear down method to measure the memory usage when you best like.
