<?php

/**
 * @package     PHPUnit_Helper
 *
 * @author      Adamo Crespi <hello@aerendir.me>
 * @copyright   Copyright (C) 2016.
 * @license     MIT
 */

namespace SerendipityHQ\Library\PHPUnit_Helper;

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
        $refl = new \ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }
}
