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
     * @retrun array $locations
     * */
    public function locations(): array
    {
        $sql = "Select * FROM locations";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $locations = $stmt->fetchAll();

        return !$locations ? [] : $locations;
    }
}
