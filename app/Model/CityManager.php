<?php

namespace App\Model;

use Nette;
use Nette\SmartObject;
/**
 * Class responsible for handling city data manipulation and query
 * @access public
 * @author Luan Sidney <luansidneyseliga@gmail.com>
 * @@method Object getCity() Return the detail about one city
 * @@method Boolean validCity() Checks if a city id is linked to a valid city
 */

class CityManager extends ModelManager
{
    private Nette\Database\Explorer $database;

    public function __construct(Nette\Database\Explorer $database)
    {
            $this->database = $database;
    }
    
    /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $idCity City id 
    * @return Obejct  
    */
    public function getCity(int $idCity)
    {
        return $this->database->table('cities')->get($idCity);
    }
    /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $idCity City id 
    * @return Boolean True: City exist, False: City don`t exist
    */
    public function validCity(int $idCity)
    {
        $valid = true;
        if(!$this->getCity($idCity)) $valid = false;
        
        return $valid;
    }
}
