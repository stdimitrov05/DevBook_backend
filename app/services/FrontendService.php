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
     * @return array
     */
    public function index(): array
    {
        return [
            'status' => 'Working!'
        ];
    }

    /**
     * Check locationId for exist
     * @param int $locationId
     * @return bool
     * */
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
     * Get locations for register page
     * @retrun array
     * */
    public function getLocations(): array
    {
        $sql = "SELECT id, state, city, country FROM locations ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $locations = $stmt->fetchAll();

        return !$locations ? [] : $locations;
    }
}
