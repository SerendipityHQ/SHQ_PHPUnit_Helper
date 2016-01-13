<?php

/**
 * @package     PHPUnit_Helper
 *
 * @author      Adamo Crespi <hello@aerendir.me>
 * @copyright   Copyright (C) 2016.
 * @license     MIT
 */

namespace SerendipityHQ\Library\PHPUnit_Helper;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * A PHPUnit helper to better manage tested resources, mocked objects and test values
 *
 * @package SerendipityHQ\Library\PHPUnit_Helper
 */
trait PHPUnit_Helper
{
    /** @var  array The expected values */
    private $expectedValues = [];

    /** @var array Contains all the mocked objects */
    private $mocks = [];

    /** @var object The tested resource */
    private $resource;

    private $useReflection = false;
    private $memoryAfterTearDown;
    private $memoryBeforeTearDown;

    /**
     * Add an expected value
     *
     * @param $name
     * @param $value
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
     * @return $this
     */
    protected function addHelpMock($id, \PHPUnit_Framework_MockObject_MockObject $class, $addToExpected = false)
    {
        $this->mocks[$id] = $class;

        if ($addToExpected)
            $this->addExpectedValue($id, $this->getHelpMock($id));

        return $this;
    }

    /**
     * Automatically set the properties of the Resource with expected values
     *
     * @return $this
     */
    protected function bindExpectedValuesToResource()
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->expectedValues as $property => $value) {
            if ($accessor->isReadable($this->getResource(), $property))
                $accessor->setValue($this->resource, $property, $value);
        }

        return $this;
    }

    /**
     * Get an expected value.
     *
     * @param $key
     * @return mixed
     */
    public function getExpectedValue($key)
    {
        if (!isset($this->expectedValues[$key]))
            throw new \InvalidArgumentException(sprintf('The required expected value "%s" doesn\'t exist.', $key));

        return $this->expectedValues[$key];
    }

    /**
     * Get a mock object.
     *
     * Use "Help" to avoid conflicts with PHPUnit getMock method.
     *
     * @param $id
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getHelpMock($id)
    {
        if (!isset($this->mocks[$id]))
            throw new \InvalidArgumentException(sprintf('The required mock object "%s" doesn\'t exist.', $id));

        return $this->mocks[$id];
    }

    /**
     * Set the resource to test
     *
     * @param object $resource The resource to test
     * @return $this
     */
    protected function setResource($resource)
    {
        if (false === is_object($resource))
            throw new \InvalidArgumentException('A Resource has to be an Object');

        $this->resource = $resource;

        return $this;
    }

    /**
     * Get the resource to test
     *
     * @return object The tested resource
     */
    protected function getResource()
    {
        return $this->resource;
    }

    /**
     * Sets to null all instantiated properties to freeup memory
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
            $this->resource       = null;
            $this->mocks          = null;
            $this->expectedValues = null;
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
     * Print memory usage info
     */
    public function printMemoryUsageInfo()
    {
        if (null === $this->memoryBeforeTearDown)
            throw new \BadMethodCallException('To use measurement features you need to call PHPUnit_Helper::measureMemoryBeforeTearDown() first.');

        if (null === $this->memoryAfterTearDown)
            $this->measureMemoryAfterTearDown();

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
     * Format an integer in bytes
     *
     * @see http://php.net/manual/en/function.memory-get-usage.php#96280
     * @param $size
     * @return string
     */
    private function formatMemory($size)
    {
        $isNegative = false;
        $unit = ['b','kb','mb','gb','tb','pb'];

        if (0 > $size) {
            // This is a negative value
            $isNegative = true;

        }

        $return = ($isNegative) ? '-' : '';

        return $return
        . @round(
            abs($size)/pow(1024,($i=floor(log(abs($size),1024)))),10
        ) . ' ' . $unit[$i];
    }
}
