<?php

namespace Eix\Services\Data\Sources;

/**
 * Mock data source for testing purposes.
 */
class Mock extends Immutable
{
    /**
     * This method obtains the full set of entities.
     */
    protected function loadEntities()
    {
        return array(
            'entity1' => array(
                'id' => 'entity1',
                'key' => 'value1',
            ),
            'entity2' => array(
                'id' => 'entity2',
                'key' => 'value2',
            ),
        );
    }
}
