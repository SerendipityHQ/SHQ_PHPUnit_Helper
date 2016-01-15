<?php

/**
 * @author      Adamo Crespi <hello@aerendir.me>
 * @copyright   Copyright (C) 2016.
 * @license     MIT
 */
namespace SerendipityHQ\Library\PHPUnit_Helper;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * A PHPUnit helper to better manage tested resources, mocked objects and test values.
 */
trait PHPUnit_Helper
{
    /** @var  array The expected values */
    private $expectedValues = [];

    /** @var array Contains all the mocked objects */
    private $mocks = [];

    /** @var array Contains all the collections of mocked objects */
    private $mocksCollections = [];

    /** @var array Contains the resources used by the test */
    private $resources = [];

    /** @var object The tested resource */
    private $testingResource;

    private $useReflection = false;
    private $memoryAfterTearDown;
    private $memoryBeforeTearDown;

    /**
     * Add an expected value.
     *
     * @param $name
     * @param $value
     *
     * @return $this
     */
    protected function addExpectedValue($name, $value)
    {
        $this->expectedValues[$name] = $value;

        return $this;
    }

    /**
     * Add a mock object.
     *
     * Use "Help" for consistency with getHelpMock.
     *
     * @param $id
     * @param \PHPUnit_Framework_MockObject_MockObject $class
     * @param bool                                     $addToExpected Define if the mock has to be added to the expected values
     *
     * @return $this
     */
    protected function addHelpMock($id, \PHPUnit_Framework_MockObject_MockObject $class, $addToExpected = false)
    {
        $this->mocks[$id] = $class;

        if ($addToExpected) {
            $this->addExpectedValue($id, $this->getHelpMock($id));
        }

        return $this;
    }

    /**
     * @param $id
     * @param array $collection
     * @param bool  $addToExpected
     *
     * @return $this
     */
    protected function addHelpMocksCollection($id, array $collection, $addToExpected = false)
    {
        $this->mocksCollections[$id] = $collection;

        if ($addToExpected) {
            $this->addExpectedValue($id, $this->getHelpMocksCollection($id));
        }

        return $this;
    }

    /**
     * Add a resource to help during the test of the class.
     *
     * @param string $name     The name of the resource
     * @param mixed  $resource The resource
     *
     * @return $this
     */
    protected function addResource($name, $resource)
    {
        $this->resources[$name] = $resource;

        return $this;
    }

    /**
     * Automatically set the properties of the Resource with expected values.
     *
     * @return $this
     */
    protected function bindExpectedValuesToResource()
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->expectedValues as $property => $value) {
            if (is_array($value)) {
                $addMethod = 'add'.ucfirst($property);
                foreach ($value as $mock) {
                    $this->testingResource->$addMethod($mock);
                }
            } else {
                if ($accessor->isReadable($this->getTestingResource(), $property)) {
                    $accessor->setValue($this->testingResource, $property, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Clone a mock object generating a collection populated with mocks of the same kind.
     *
     * @param \PHPUnit_Framework_MockObject_MockObject $mock
     * @param int                                      $repeatFor
     *
     * @return array
     */
    protected function generateMocksCollection(\PHPUnit_Framework_MockObject_MockObject $mock, $repeatFor = 1)
    {
        $collection = [];

        for ($i = 1; $i <= $repeatFor; $i++) {
            $collection[] = clone $mock;
        }

        return $collection;
    }

    /**
     * Counts the number of elements in a collection in the expected values.
     *
     * @param $collection
     *
     * @return int
     */
    public function getExpectedCount($collection)
    {
        if (!isset($this->expectedValues[$collection])) {
            throw new \InvalidArgumentException(sprintf('The required expected collection "%s" doesn\'t exist.', $collection));
        }

        if (!is_array($this->expectedValues[$collection])) {
            throw new \InvalidArgumentException(sprintf('The required expected collection "%s" isn\'t a collection but a value.', $collection));
        }

        return count($this->expectedValues[$collection]);
    }

    /**
     * Get an expected value.
     *
     * @param $key
     *
     * @return mixed
     */
    public function getExpectedValue($key)
    {
        if (!isset($this->expectedValues[$key])) {
            throw new \InvalidArgumentException(sprintf('The required expected value "%s" doesn\'t exist.', $key));
        }

        return $this->expectedValues[$key];
    }

    /**
     * Get a mock object.
     *
     * @param $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHelpMock($id)
    {
        if (!isset($this->mocks[$id])) {
            throw new \InvalidArgumentException(sprintf('The required mock object "%s" doesn\'t exist.', $id));
        }

        return $this->mocks[$id];
    }

    /**
     * Get a collection of mocks.
     *
     * Use help to av
     *
     * @param $id
     *
     * @return array
     */
    protected function getHelpMocksCollection($id)
    {
        if (!isset($this->mocksCollections[$id])) {
            throw new \InvalidArgumentException(sprintf('The required mock collection "%s" doesn\'t exist.', $id));
        }

        return $this->mocksCollections[$id];
    }

    /**
     * Get a mock from a collection.
     *
     * If $removeFromCollection is set to true, it also removes the mock from the collection.
     * If the collection is in the expected values array, removes the mock from the expected values too.
     *
     * @param $mockName
     * @param $collection
     * @param $andRemove
     */
    protected function getMockFromMocksCollection($mockName, $collection, $andRemove = false)
    {
        if (!isset($this->mocksCollections[$collection][$mockName])) {
            throw new \InvalidArgumentException(sprintf('The required mock "%s" doesn\'t exist in collection "%s".', $mockName, $collection));
        }

        if ($andRemove) {
            $this->removeMockFromMocksCollection($mockName, $collection);
        }

        return $this->mocksCollections[$collection][$mockName];
    }

    /**
     * Get a resource to help during testing.
     *
     * @param $name
     *
     * @return mixed
     */
    protected function getResource($name)
    {
        if (!isset($this->resources[$name])) {
            throw new \InvalidArgumentException(sprintf("The resource \"%s\" you are asking for doesn't exist.", $name));
        }

        return $this->resources[$name];
    }

    /**
     * Removes a mock from a collection. Optionally, also from the expected values.
     *
     * @param string $mockName
     * @param string $collection
     * @param bool   $fromExpectedToo If true, removes the mock also from collection in expected values
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function removeMockFromMocksCollection($mockName, $collection, $fromExpectedToo = true)
    {
        if (!isset($this->mocksCollections[$collection][$mockName])) {
            throw new \InvalidArgumentException(sprintf('The required mock "%s" doesn\'t exist in collection "%s".', $mockName, $collection));
        }

        $return = $this->mocksCollections[$collection][$mockName];
        unset($this->mocksCollections[$collection][$mockName]);

        // Remove also from expected values
        if (isset($this->expectedValues[$collection][$mockName]) && $fromExpectedToo) {
            unset($this->expectedValues[$collection][$mockName]);
        }

        return $return;
    }

    /**
     * Set the resource to test.
     *
     * @param object $resourceToTest The resource to test
     *
     * @return $this
     */
    protected function setResourceToTest($resourceToTest)
    {
        if (false === is_object($resourceToTest)) {
            throw new \InvalidArgumentException(sprintf('The resource to test has to be an Object. You passed a "%s".', gettype($resourceToTest)));
        }

        $this->testingResource = $resourceToTest;

        return $this;
    }

    /**
     * Get the resource to test.
     *
     * @return object The tested resource
     */
    protected function getTestingResource()
    {
        return $this->testingResource;
    }

    /**
     * Sets to null all instantiated properties to freeup memory.
     */
    protected function helpTearDown()
    {
        if ($this->useReflection) {
            $refl = new \ReflectionObject($this);
            foreach ($refl->getProperties() as $prop) {
                if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                    $prop->setAccessible(true);
                    $prop->setValue($this, null);
                }
            }
            $refl = null;
            unset($refl);
        } else {
            // At least unset the helper properties
            $this->expectedValues = null;
            $this->testingResource = null;
            $this->mocks = null;
            $this->mocksCollections = null;
            $this->resources = null;
        }
    }

    public function measureMemoryAfterTearDown()
    {
        $this->memoryAfterTearDown = memory_get_usage();
    }

    public function measureMemoryBeforeTearDown()
    {
        $this->memoryBeforeTearDown = memory_get_usage();
    }

    /**
     * Print memory usage info.
     */
    public function printMemoryUsageInfo()
    {
        if (null === $this->memoryBeforeTearDown) {
            throw new \BadMethodCallException('To use measurement features you need to call PHPUnit_Helper::measureMemoryBeforeTearDown() first.');
        }

        if (null === $this->memoryAfterTearDown) {
            $this->measureMemoryAfterTearDown();
        }

        printf("\n(Memory used before tearDown(): %s)", $this->formatMemory($this->memoryBeforeTearDown));
        printf("\n(Memory used after tearDown(): %s)", $this->formatMemory($this->memoryAfterTearDown));
        printf("\n(Memory saved with tearDown(): %s)\n", $this->formatMemory($this->memoryBeforeTearDown - $this->memoryAfterTearDown));
    }

    /**
     * Set toggle or off the use of the reflection to tear down the test.
     *
     * @param bool $useReflection
     */
    public function useReflectionToTearDown($useReflection = true)
    {
        $this->useReflection = $useReflection;
    }

    /**
     * Format an integer in bytes.
     *
     * @see http://php.net/manual/en/function.memory-get-usage.php#96280
     *
     * @param $size
     *
     * @return string
     */
    private function formatMemory($size)
    {
        $isNegative = false;
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        if (0 > $size) {
            // This is a negative value
            $isNegative = true;
        }

        $return = ($isNegative) ? '-' : '';

        return $return
        .@round(
            abs($size) / pow(1024, ($i = floor(log(abs($size), 1024)))), 10
        ).' '.$unit[$i];
    }
}
