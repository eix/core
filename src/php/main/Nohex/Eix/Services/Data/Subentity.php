<?php

namespace Nohex\Eix\Services\Data;

/**
 * Structured representation of a set of related data that can be persisted.
 */
abstract class Subentity extends Entity
{
    /**
     * Sub-entities are not persistable by themselves
     */
    protected function assignDataSource()
    {
        throw new \RuntimeException('Subentities cannot be stored independently');
    }

    /**
     * Returns the factory instance that serves objects of the current class.
     * @return Factory
     */
    protected function getFactory()
    {
        throw new \RuntimeException('Subentities do not use factories');
    }
}
