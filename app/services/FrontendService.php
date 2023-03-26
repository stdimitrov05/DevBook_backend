<?php

namespace App\Services;

/**
 * Business-logic for site frontend
 *
 * Class FrontendService
 */
class FrontendService extends AbstractService
{
    /**
     * Returns a simple array with a status message indicating that the endpoint is working.
     * @return array An array with a 'status' key and its value set to 'Working!'.
     */
    public function index(): array
    {
        return [
            'status' => 'Working!'
        ];
    }

    /**
     * Checks if a given location ID exists in the database.
     * @param int $locationId The location ID to check for existence.
     * @return bool Returns `true` if the location ID exists, `false` otherwise.
     */
    public function locationIdExists(int $locationId): bool
    {
        $sql = "SELECT location_id FROM locations WHERE location_id=:locationId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam("locationId", $locationId, \PDO::PARAM_INT);
        $stmt->execute();
        $locationId = $stmt->fetchColumn();

        return !empty($locationId);
    }

    /**
     * Fetches all available locations from the database.
     * @return array An array of location objects, each containing the `id`, `state`, `city`, and `country` properties.
     */
    public function getLocations(): array
    {
        $sql = "SELECT id, state, city, country FROM locations ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $locations = $stmt->fetchAll();

        return !$locations ? [] : $locations;
    }
}
