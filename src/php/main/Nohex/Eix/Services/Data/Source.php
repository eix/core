<?php

namespace Nohex\Eix\Services\Data;

/**
 * Interface that defines a CRUD data provider.
 */
interface Source
{
    /**
     * Stores a data object. If that object exists, it is updated.
     *
     * @returns string ID the ID of the newly-created object.
     */
    public function create(array $data);

    /**
     * Gets one or more data objects.
     *
     * @return \stdClass the selected object's data.
     */
    public function retrieve($id);

    /**
     * Gets all related data objects.
     *
     * @return \stdClass[] an array of the selected objects' data.
     */
    public function retrieveAll(array $filter = null, array $fields = null);

    /**
     * Stores a data object. If that object exists, it is updated.
     *
     * @return boolean whether the operation has been successful.
     */
    public function update($id, array $data);

    /**
     * Deletes a data object.
     *
     * @return boolean whether the operation has been successful.
     */
    public function destroy($id);
}
