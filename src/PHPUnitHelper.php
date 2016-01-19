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
trait PHPUnitHelper
{
    /** @var  array The expected values */
    private $areExpected = [];

    /** @var array Contains all the mocked objects */
    private $helpMocks = [];

    /** @var array Contains all the collections of mocked objects */
    private $helpMocksCollections = [];

    /** @var array Contains the resources used by the test */
    private $helpResources = [];

    /** @var mixed The result of the test */
    private $helpResult;

    /** @var object The tested resource */
    private $objectToTest;

    /** @var array Contains the help values */
    private $helpValues = [];

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
    private function addExpectedValue($name, $value)
    {
        $this->areExpected[$name] = $value;

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
        $this->helpMocks[$id] = $class;

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
    protected function addHelpMocksCollection($id, array $collection, $addToExpected = false, $overwrite)
    {
        if (isset($this->helpMocksCollections[$id]) && false === $overwrite)
            throw new \LogicException('The Mocks Collection you are trying to add is already set. Set the fourth parameter to "true" to overwrite it.');

        $this->helpMocksCollections[$id] = $collection;

        if ($addToExpected) {
            $this->addExpectedValue($id, $this->getHelpMocksCollection($id));
        }

        return $this;
    }

    /**
     * Add a resource to help during the test of the class.
     *
     * @param string $id        The name of the resource
     * @param mixed  $resource  The resource
     * @param bool   $overwrite Defines if a resource can be overwritten or not
     *
     * @return $this
     */
    protected function addHelpResource($id, $resource, $overwrite = false)
    {
        if (isset($this->helpResources[$id]) && false === $overwrite) {
            throw new \LogicException('The resource you are trying to add is already set. Set the third parameter to "true" to overwrite it.');
        }

        if (false === is_object(($resource))) {
            throw new \InvalidArgumentException(sprintf('The resource "%s" you are trying to add is not an object. addResource() accepts only objects. Use addHelpValue() to store other kind of values.', $id));
        }

        $this->helpResources[$id] = $resource;

        return $this;
    }

    /**
     * Add a value used in tests.
     *
     * @param string $id
     * @param mixed $value
     * @param bool $addToExpected Define if the mock has to be added to the expected values
     * @param $overwrite bool If false, the result isn't overwritten
     *
     * @return $this
     */
    protected function addHelpValue($id, $value, $addToExpected = false, $overwrite = false)
    {
        if ($value instanceof \PHPUnit_Framework_MockObject_MockObject) {
            throw new \LogicException('The HelpValue with ID "%s" you are trying to add is a mock object. Use $this->addHelpMock() instead.', $id);
        }

        if (isset($this->helpValues[$id]) && false === $overwrite)
            throw new \LogicException('The HelpValue you are trying to add is already set. Set the fourth parameter to "true" to overwrite it.');

        if (isset($this->helpValues[$id])) {
            throw new \LogicException('Another HelpValue with ID "%s" is already set.', $id);
        }

        $this->helpValues[$id] = $value;

        if ($addToExpected) {
            $this->addExpectedValue($id, $this->getHelpValue($id));
        }

        return $this;
    }

    /**
     * Automatically set the properties of the Resource with expected values.
     *
     * @return $this
     */
    protected function bindExpectedToObjectToTest()
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->areExpected as $property => $value) {
            if (is_array($value)) {
                $addMethod = 'add'.ucfirst($property);
                foreach ($value as $mock) {
                    $this->getObjectToTest()->$addMethod($mock);
                }
            } else {
                if ($accessor->isWritable($this->getObjectToTest(), $property)) {
                    $accessor->setValue($this->getObjectToTest(), $property, $value);
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
     * Get an expected value.
     *
     * @param $id
     *
     * @return mixed
     */
    public function getExpected($id)
    {
        if (!isset($this->areExpected[$id])) {
            throw new \InvalidArgumentException(sprintf('The required expected value "%s" doesn\'t exist.', $id));
        }

        return $this->areExpected[$id];
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
        if (!isset($this->helpMocks[$id])) {
            throw new \InvalidArgumentException(sprintf('The required mock object "%s" doesn\'t exist.', $id));
        }

        return $this->helpMocks[$id];
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
        if (!isset($this->helpMocksCollections[$id])) {
            throw new \InvalidArgumentException(sprintf('The required mock collection "%s" doesn\'t exist.', $id));
        }

        return $this->helpMocksCollections[$id];
    }

    /**
     * Get a resource to help during testing.
     *
     * @param $id
     *
     * @return mixed
     */
    protected function getHelpResource($id)
    {
        if (!isset($this->helpResources[$id])) {
            throw new \InvalidArgumentException(sprintf("The resource \"%s\" you are asking for doesn't exist.", $id));
        }

        return $this->helpResources[$id];
    }

    /**
     * The result of the test.
     *
     * For example, the output of a command, or the crawler object of a request.
     *
     * @return mixed
     */
    protected function getHelpResult()
    {
        if (null === $this->helpResult) {
            throw new \LogicException('Before you can call getResult(), you have to set a result with setResult().');
        }

        return $this->helpResult;
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function getHelpValue($id)
    {
        if (!isset($this->helpValues[$id])) {
            throw new \InvalidArgumentException(sprintf('The required help value "%s" doesn\'t exist.', $id));
        }

        return $this->helpValues[$id];
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
        if (!isset($this->helpMocksCollections[$collection][$mockName])) {
            throw new \InvalidArgumentException(sprintf('The required mock "%s" doesn\'t exist in collection "%s".', $mockName, $collection));
        }

        if ($andRemove) {
            $this->removeMockFromMocksCollection($mockName, $collection);
        }

        return $this->helpMocksCollections[$collection][$mockName];
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
     * @param bool   $fromExpectedToo If true, removes the mock also from collection in expected values
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function removeMockFromMocksCollection($mockName, $collection, $fromExpectedToo = true)
    {
        if (!isset($this->helpMocksCollections[$collection][$mockName])) {
            throw new \InvalidArgumentException(sprintf('The required mock "%s" doesn\'t exist in collection "%s".', $mockName, $collection));
        }

        $return = $this->helpMocksCollections[$collection][$mockName];
        unset($this->helpMocksCollections[$collection][$mockName]);

        // Remove also from expected values
        if (isset($this->areExpected[$collection][$mockName]) && $fromExpectedToo) {
            unset($this->areExpected[$collection][$mockName]);
        }

        return $return;
    }

    /**
     * The result of the test.
     *
     * This not allows method chaining.
     *
     * @param $result
     * @param $overwrite bool If false, the result isn't overwritten
     */
    protected function setHelpResult($result, $overwrite = false)
    {
        if (null !== $this->helpResult && false === $overwrite) {
            throw new \LogicException('A result is already set. Set the third parameter to "true" to overwrite it.');
        }

        $this->helpResult = $result;
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
            $this->areExpected = null;
            $this->helpMocks = null;
            $this->helpMocksCollections = null;
            $this->helpResources = null;
            $this->helpResult = null;
            $this->objectToTest = null;
            $this->helpValues = null;
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
        .round(
            abs($size) / pow(1024, ($i = floor(log(abs($size), 1024)))), 2
        )
        .' '
        .$unit[$i];
    }
}
