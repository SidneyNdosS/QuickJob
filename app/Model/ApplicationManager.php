<?php

namespace App\Model;

use Nette;
use Nette\SmartObject;
use Nette\Http\FileUpload;
use App\Model\PositionManager;
use App\Model\CityManager;

/**
 * Class responsible for handling application data manipulation and queries
 * @access public
 * @author Luan Sidney <luansidneyseliga@gmail.com>
 * @@method Boolean ApplicationDataValidation(array $values) Validate the input date according with the table application`s constraints and business rules  
 * @@method Object insertApplication(array $values) Saves the candidate`s application
 * @@method Boolean alreadyApplied(string $email, int $id_city) Checks if this candidate already applied for the position on a specific city
 */

class ApplicationManager extends ModelManager
{
   /**
    * @access private
    */
    private Nette\Database\Explorer $database;
   /**
    * @access private
    */
    private PositionManager $PositionManager; 
   /**
    * @access private
    */
    private CityManager $CityManager; 
    
    public function __construct(Nette\Database\Explorer $database, PositionManager $PositionManager, CityManager $CityManager)
    {
            $this->database = $database;
            $this->PositionManager = $PositionManager;
            $this->CityManager = $CityManager;
    }
   /**
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @return Boolean  
    */
    private function ApplicationDataValidation(array $values){
        $success = true;
        //Users the obejct Database to save the information in the data base, 
        //note that the array needs to have all required field as key.
        if(!(isset($values['fist_name']) && $values['fist_name'] != "") ||
           !(isset($values['last_name']) && $values['last_name'] != "") ||
           !(isset($values['email']) && $values['email'] != "") ||
           !(isset($values['why_you']) && $values['why_you'] != "") ||
           !(isset($values['id_city']) && is_numeric($values['id_city']))){     
            
            $this->addIssue("Required field is missing", "There are required field missing, please check your application.");
            $success = false;
        }
        
        if($this->alreadyApplied($values['email'], $values['id_city'], $values['id_job_position'])){
            $this->addIssue("You have already applied for this position in this city", "You have already applied for this position.");
            $success = false;
        }
        
        if(!$this->PositionManager->validPosition($values['id_job_position'])){
            $this->addIssue("This position is not active or it`s was removed", "This position is not active or it`s was removed.");
            $success = false;
        }
        
        if(!$this->CityManager->validCity($values['id_city'])){
            $this->addIssue("This city is not valid", "This city is not valid.");
            $success = false;
        }
        
        //Check if this position is open in the city the candidate has chosen 
        if(!$this->PositionManager->positionCityValid($values['id_job_position'], $values['id_city'])){
            $this->addIssue("This city is not valid", "This city is not valid.");
            $success = false;
        }
        
        return $success;
    }
    /**
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @return Obejct  
    */
    public function insertApplication(array $values, array $files = array())
    {  
        //Valid all data input to ensure the table constraints were respected
        $success = $this->ApplicationDataValidation($values);       
        
        //If any problemes were detected, stop the insertion and report those issues back to the presenter.
        if(!$success){return $success;}
        
        //Start a transcation, so in case of failed we can rollback the changes in the data base 
        $this->database->beginTransaction();
        try{
            $application = $this->database->table('application')->insert([
                                        'first_name' => $values['fist_name'], 
                                        'last_name' =>  $values['last_name'],
                                        'email' => $values['email'],
                                        'phone_number' => $values['phone_number'],
                                        'linkedln' => $values['linkedln'],
                                        'why_you' => $values['why_you'],
                                        'id_job_position' => $values['id_job_position'],
                                        'id_city' => $values['id_city']]);
            
            if(count($files) > 0){
                $this->insertApplicationFiles($application->id, $files);
            }
            
            //Salve all data permanentaly 
            $this->database->commit();
        }
        catch(\Exception $e){
            $success = false;
            //Revert all the inserts made
            $this->database->rollBack();
            throw new \Exception("We are very sorry but due to unforeseen circumstances your application could not be submitted, please try again later.");
        }
        
        return $success;
    }
   /**
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $applicationID
    * @param array $files Array of objects FileUpload.
    * @return Void  
    */
    public function insertApplicationFiles(int $applicationID, array $files){
        
        foreach($files as $key => $file){
            if ($file instanceof FileUpload && $file->hasFile()) {
                $name = $applicationID . "_" . $file->getSanitizedName();
                $this->database->table('application_files')->insert(["file_src" => $name,"id_application" => $applicationID]);
                
                $file->move("storage/candidates/{$name}");
            }
        }
    }
   /**
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param string $email
    * @param int $id_city
    * @return Obejct  
    */
    public function alreadyApplied(string $email, int $id_city, int $positionID){
        $application =  $this->database->table('application')
                                       ->where("`email` = ? AND `id_city` = ?", $email, $id_city)
                                       ->where("`id_city` = ?", $id_city)
                                       ->where("`id_job_position` = ?", $positionID);
        return count($application) >= 1;
    }
}