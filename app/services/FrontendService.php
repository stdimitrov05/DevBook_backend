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
     * @retrun array $countries
     * */
    public function countries(): array
    {
        $sql = "Select * FROM countries";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $countries = $stmt->fetchAll();

        return !$countries ? [] : $countries;
    }
}
