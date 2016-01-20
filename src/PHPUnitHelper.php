<?php

/**
 * @author      Adamo Crespi <hello@aerendir.me>
 * @copyright   Copyright (C) 2016.
 * @license     MIT
 */
namespace SerendipityHQ\Library\PHPUnit_Helper;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * A PHPUnit helper to better manage tested resources, mocked objects, collections of mocks and test values.
 */
trait PHPUnitHelper
{
    /** @var mixed The result of the test */
    private $actualResult;

    /** @var  array The expected mocks */
    private $expectedMocks = [];

    /** @var  array Contains the expected collection of mock objects */
    private $expectedMocksCollections = [];

    /** @var  array The expected values */
    private $expectedValues = [];

    /** @var array Contains all the mocked objects */
    private $helpMocks = [];

    /** @var array Contains the resources used by the test */
    private $helpResources = [];

    /** @var array Contains the help values */
    private $helpValues = [];

    /** @var object The tested resource */
    private $objectToTest;

    private $memoryAfterTearDown;
    private $memoryBeforeTearDown;

    /**
     * @param $key
     * @param \PHPUnit_Framework_MockObject_MockObject $mock
     *
     * @return $this
     */
    protected function addExpectedMock($key, \PHPUnit_Framework_MockObject_MockObject $mock)
    {
        if (isset($this->expectedMocks[$key]) || isset($this->expectedMocksCollections[$key]) || isset($this->expectedValues[$key])) {
            throw new \LogicException(
                sprintf('The expected mock "%s" you are trying to add is already set as mock, mock collection or value.', $key)
            );
        }

        $this->expectedMocks[$key] = $mock;

        return $this;
    }

    /**
     * @param $key
     * @param array $collection
     *
     * @return $this
     */
    protected function addExpectedMocksCollection($key, array $collection)
    {
        if (isset($this->expectedMocks[$key]) || isset($this->expectedMocksCollections[$key]) || isset($this->expectedValues[$key])) {
            throw new \LogicException(
                sprintf('The expected mocks collection "%s" you are trying to add is already set as mock, mock collection or value.', $key)
            );
        }

        foreach ($collection as $mock) {
            if (false === $mock instanceof \PHPUnit_Framework_MockObject_MockObject) {
                throw new \InvalidArgumentException(
                    sprintf('One of the elements in the mocks collection "%s" is not a mock object.', $key)
                );
            }
        }

        $this->expectedMocksCollections[$key] = $collection;

        return $this;
    }

    /**
     * Add an expected value.
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    protected function addExpectedValue($key, $value)
    {
        if (isset($this->expectedMocks[$key]) || isset($this->expectedMocksCollections[$key]) || isset($this->expectedValues[$key])) {
            throw new \LogicException(
                sprintf('The expected value "%s" you are trying to add is already set as mock, mock collection or value.', $key)
            );
        }

        if (is_object($value)) {
            throw new \InvalidArgumentException('The expected value you are trying to add is an object. Use addExpectedMock() instead.');
        }

        $this->expectedValues[$key] = $value;

        return $this;
    }

    /**
     * Add a mock object.
     *
     * Use "Help" for consistency with getHelpMock.
     *
     * @param $key
     * @param \PHPUnit_Framework_MockObject_MockObject $mock
     *
     * @return $this
     */
    protected function addHelpMock($key, \PHPUnit_Framework_MockObject_MockObject $mock)
    {
        if (isset($this->helpMocks[$key])) {
            throw new \LogicException('The help mock you are trying to add is already set.');
        }

        $this->helpMocks[$key] = $mock;

        return $this;
    }

    /**
     * Add a resource to help during the test of the class.
     *
     * @param string $key      The name of the resource
     * @param mixed  $resource The resource
     *
     * @return $this
     */
    protected function addHelpResource($key, $resource)
    {
        if (isset($this->helpResources[$key])) {
            throw new \LogicException('The resource you are trying to add is already set.');
        }

        if (false === is_object(($resource))) {
            throw new \InvalidArgumentException(sprintf('The resource "%s" you are trying to add is not an object. addHelpResource() accepts only objects. Use addHelpValue() to store other kind of values.', $key));
        }

        $this->helpResources[$key] = $resource;

        return $this;
    }

    /**
     * Add a value used in tests.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    protected function addHelpValue($key, $value)
    {
        if (is_object($value)) {
            throw new \InvalidArgumentException(sprintf('The HelpValue with ID "%s" you are trying to add is an object. Use $this->addHelpMock() instead.', $key));
        }

        if (isset($this->helpValues[$key])) {
            throw new \LogicException('The HelpValue you are trying to add is already set. Set the fourth parameter to "true" to overwrite it.');
        }

        $this->helpValues[$key] = $value;

        return $this;
    }

    /**
     * Automatically set the properties of the Resource with expected values.
     *
     * @return $this
     */
    protected function bindExpectedToObject()
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $values = array_merge($this->expectedValues, $this->expectedMocks, $this->expectedMocksCollections);

        foreach ($values as $property => $value) {
            if (is_array($value)) {
                $addMethod = 'add'.ucfirst($property);
                foreach ($value as $mock) {
                    $this->getObjectToTest()->$addMethod($mock);
                }
            } else {
                if ($accessor->isWritable($this->getObjectToTest(), $property)) {
                    // Use direct access to property to avoid "only variables should be passed by reference"
                    $accessor->setValue($this->objectToTest, $property, $value);
                }
            }
        }

        // Tear down
        unset($values);
        unset($accessor);

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
     * The result of the test.
     *
     * For example, the output of a command, or the crawler object of a request.
     *
     * @return mixed
     */
    protected function getActualResult()
    {
        if (null === $this->actualResult) {
            throw new \LogicException('Before you can call getActualResult(), you have to set a result with setActualResult().');
        }

        return $this->actualResult;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    protected function getExpectedMock($key)
    {
        if (!isset($this->expectedMocks[$key])) {
            throw new \InvalidArgumentException(sprintf('The required expected mock "%s" doesn\'t exist.', $key));
        }

        return $this->expectedMocks[$key];
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    protected function getExpectedMocksCollection($key)
    {
        if (!isset($this->expectedMocksCollections[$key])) {
            throw new \InvalidArgumentException(sprintf('The required expected mocks collection "%s" doesn\'t exist.', $key));
        }

        return $this->expectedMocksCollections[$key];
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    protected function getExpectedValue($key)
    {
        if (!isset($this->expectedValues[$key])) {
            throw new \InvalidArgumentException(sprintf('The required expected value "%s" doesn\'t exist.', $key));
        }

        return $this->expectedValues[$key];
    }

    /**
     * Get a mock object.
     *
     * @param $key
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHelpMock($key)
    {
        if (!isset($this->helpMocks[$key])) {
            throw new \InvalidArgumentException(sprintf('The required mock object "%s" doesn\'t exist.', $key));
        }

        return $this->helpMocks[$key];
    }

    /**
     * Get a resource to help during testing.
     *
     * @param $key
     *
     * @return mixed
     */
    protected function getHelpResource($key)
    {
        if (!isset($this->helpResources[$key])) {
            throw new \InvalidArgumentException(sprintf("The resource \"%s\" you are asking for doesn't exist.", $key));
        }

        return $this->helpResources[$key];
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    protected function getHelpValue($key)
    {
        if (!isset($this->helpValues[$key])) {
            throw new \InvalidArgumentException(sprintf('The required help value "%s" doesn\'t exist.', $key));
        }

        return $this->helpValues[$key];
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
        if (!isset($this->expectedMocksCollections[$collection][$mockName])) {
            throw new \InvalidArgumentException(sprintf('The required mock "%s" doesn\'t exist in collection "%s".', $mockName, $collection));
        }

        if ($andRemove) {
            $this->removeMockFromMocksCollection($mockName, $collection);
        }

        return $this->expectedMocksCollections[$collection][$mockName];
    }

    /**
     * Get the resource to test.
     *
     * @return object The tested resource
     */
    protected function getObjectToTest()
    {
        return $this->objectToTest;
    }

    /**
     * Removes a mock from a collection. Optionally, also from the expected values.
     *
     * @param string $mockName
     * @param string $collection
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function removeMockFromMocksCollection($mockName, $collection)
    {
        if (!isset($this->expectedMocksCollections[$collection][$mockName])) {
            throw new \InvalidArgumentException(sprintf('The required mock "%s" doesn\'t exist in collection "%s".', $mockName, $collection));
        }

        $return = $this->expectedMocksCollections[$collection][$mockName];
        unset($this->expectedMocksCollections[$collection][$mockName]);

        return $return;
    }

    /**
     * The result of the test.
     *
     * This not allows method chaining.
     *
     * @param $result
     */
    protected function setActualResult($result)
    {
        if (null !== $this->actualResult) {
            throw new \LogicException('A result is already set. Set the third parameter to "true" to overwrite it.');
        }

        $this->actualResult = $result;
    }

    /**
     * Set the resource to test.
     *
     * @param object $objectToTest The resource to test
     *
     * @return $this
     */
    protected function setObjectToTest($objectToTest)
    {
        if (false === is_object($objectToTest)) {
            throw new \InvalidArgumentException(sprintf('The resource to test has to be an Object. You passed a "%s".', gettype($objectToTest)));
        }

        $this->objectToTest = $objectToTest;

        return $this;
    }

    /**
     * Sets to null all instantiated properties to freeup memory.
     */
    protected function helpTearDown()
    {
        // At least unset the helper properties
        $this->actualResult = null;
        $this->expectedMocks = null;
        $this->expectedMocksCollections = null;
        $this->expectedValues = null;
        $this->helpMocks = null;
        $this->helpResources = null;
        $this->helpValues = null;
        $this->objectToTest = null;
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
     */
    public function tearDownWithReflection()
    {
        $this->helpTearDown();

        $refl = new \ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
        $refl = null;
        unset($refl);
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
        .round(
            abs($size) / pow(1024, ($i = floor(log(abs($size), 1024)))), 2
        )
        .' '
        .$unit[$i];
    }
}
