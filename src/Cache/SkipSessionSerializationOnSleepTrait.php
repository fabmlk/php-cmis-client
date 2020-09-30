<?php

namespace Dkd\PhpCmis\Cache;

/**
 * Helper trait to ignore session property from serialization.
 */
trait SkipSessionSerializationOnSleepTrait
{
    /**
     * @return array
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        // discard session property, as noted in AbstractCmisObject::serialize()
        unset($properties['session']);

        return array_keys($properties);
    }
}