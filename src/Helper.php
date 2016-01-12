<?php

/**
 * @package     TrustBackMe.Web
 *
 * @author      Adamo Crespi <hello@aerendir.me>
 * @copyright   Copyright (C) 2012 - 2015 TrustBack.me. All rights reserved.
 * @license     SECRETED. No distribution, no copy, no derivative, no divulgation or any other activity or action that could disclose this text.
 */

namespace SerendipityHQ\Library\PHPUnit_Helper;

trait PHPUnit_Helper
{
    /**
     * Sets to null all instantiated properties to freeup memory
     */
    protected function tearDown()
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
