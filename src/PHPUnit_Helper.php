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
    /** @var object The tested resource */
    private $resource;

    /**
     * @param object $resource The resource to test
     */
    protected function setResource($resource)
    {
        if (false === is_object($resource))
            throw new \InvalidArgumentException('A Resource has to be an Object');

        $this->resource = $resource;
    }

    /**
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
