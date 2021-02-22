<?php

namespace App\Model;

use Nette;

/**
 * Class responsible for handling job position data manipulation and query
 * @access public
 * @author Luan Sidney <luansidneyseliga@gmail.com>
 * @@method Object getActivePositions()  Returns an array of object with the positions title
 * @@method Object getPosition(int $positionID) Return all details related to a position
 * @@method Object getPositionSlug(string $slug) Return all details related to a position base on a slug
 * @@method Boolean validPosition(int $positionID) Return`s true is the position informed exist`s and it`s active
 * @@method Object getCitiesAvailable(int $positionID) Return all cities where the position is available
 * @@method Object positionCityValid(int $positionID, int $cityID) Check if the position is open in a specific city
 * @@method Object getPositionTags(int $positionID) Returns all tags available for one position
 */

class PositionManager extends ModelManager
{
    use Nette\SmartObject;

    private Nette\Database\Explorer $database;

    public function __construct(Nette\Database\Explorer $database)
    {
            $this->database = $database;
    }
    
    /**
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @return Obejct  
    */
    public function getActivePositions($search): Nette\Database\Table\Selection
    {
        return $this->database->table('job_positions')
                ->select('job_positions.id, job_positions.slug, job_positions.title, job_positions.content, job_positions.lead, img_src, img_alt, id_category.category_name')
                ->where("is_active IS TRUE AND lower(title) LIKE ?", "%$search%")
                ->order('title DESC');
    }
    
    /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Obejct  
    */
    public function getPosition(int $positionID)
    {
        return $this->database->table('job_positions')
                      ->where("is_active IS TRUE")
                      ->get($positionID);
    }
    /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Obejct  
    */
    public function getPositionSlug(string $slug)
    {
        return $this->database->table('job_positions')
                      ->where("is_active IS TRUE")
                      ->where("slug = ?", $slug)
                      ->fetch();
    }
    /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Boolean True: Position exist and it`s active, False: Position don`t exist or it`s not active 
    */
    public function validPosition(int $positionID)
    {
        $valid = true;
        if(!$this->getPosition($positionID)) $valid = false;
        
        return $valid;
    }
    /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Obejct  
    */
    public function getCitiesAvailable(int $positionID)
    {
        return $this->database->table('job_position_cities')
                ->select("id_city.id, id_city.city_name, id_city.id_country.country_abbreviation")
                ->where("id_job_position = {$positionID}")
                ->order("id_city.city_name");
    }
    /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Obejct  
    */
    public function getCitiesAvailableSlug(string $slug)
    {
        return $this->database->table('job_position_cities')
                ->select("id_city.id, id_city.city_name, id_city.id_country.country_abbreviation")
                ->where("id_job_position.slug = ?", $slug)
                ->order("id_city.city_name");
    }
   /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @param int $cityID City id  
    * @return True: The position is open in that city, False: The position is not open in that city  
    */
    public function positionCityValid(int $positionID, int $cityID){
        $positCity = $this->database->table('job_position_cities')
                          ->where("id_job_position = ?", $positionID)
                          ->where("id_city = ?", $cityID);
        return count($positCity) >= 1; 
    }
   /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Obejct  
    */
    public function getPositionTags(int $positionID)
    {
        return $this->database->table('job_positions_tags')
                ->select("id_tags.id, id_tags.tag_name")
                ->where("id_job_position = {$positionID}")
                ->order("id_tags.tag_name");
    }
}
