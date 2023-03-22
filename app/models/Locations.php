<?php


namespace App\Models;

use Phalcon\Mvc\Model;

# Locations
# This model is linked to countries
class  Locations extends Model
{
    /**
     * @param int $id
     * @Primary
     * @Identity
     * @Column (type='integer" AI)
     */
    public ?int $id = null;

    /**
     * @var string $city
     * @Column (type="varchar", leght=64)
     */
    public string $city;

    /**
     * @var string $state
     * @Column (type="varchar", leght=64)
     */
    public string $state;

    /**
     * @var string $country
     * @Column (type="varchar", leght=64)
     */
    public string $country;

    /**
     * @var string $country_short
     * @Column (type="varchar", leght=2)
     */
    public string $country_short;

    /**
     * @var string $continent
     * @Column (type="varchar", leght=16)
     */
    public string $continent;

    /**
     * @var int $continent_id
     * @Column (type="integer", FK)
     */
    public int $continent_id;

    /**
     * @var string $search_city
     * @Column (type="varchar", leght=64)
     */
    public string $search_city;

    /**
     * @var float $latitude
     * @Column (type="float", leght=64)
     */
    public float $latitude ;

    /**
     * @var float $longitude
     * @Column (type="float", leght=64)
     */
    public float $longitude ;

    /**
     * @var string $keywords
     * @Column (type="text", leght=64)
     */
    public string $keywords  ;

    /**
     * @var string $lang
     * @Column (type="string", leght=2)
     */
    public string $lang  ;


    public function initialize()
    {
        $this->setSource('locations');
    }
}