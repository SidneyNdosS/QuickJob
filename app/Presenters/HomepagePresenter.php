<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;
use App\Model\PositionManager;

/**
 * Class presenter for the homepage
 * @final
 * @author Luan Sidney <luansidneyseliga@gmail.com>
 * @@method string stringCitiesPosition(int $positionID) Fetch all cities where a position is open and combine them in one string as result
 * @@method void renderDefault(int $Page) Render the default page
 * @@method void createComponentSearchForm() Creates a form for the search option
 * @@method void SearchPosition(Form $form, array $values) Saves the search parameters informed by the user in the URL.
 * @@method void sendPayload() Overwrites the sendPayload method to salve the flash messages and allow full redirect from the back end side
 */

final class HomepagePresenter extends Nette\Application\UI\Presenter
{
   /**
    * @access private
    */
    private PositionManager $PositionManager;

    public function __construct(PositionManager $PositionManager)
    {
        $this->PositionManager = $PositionManager;
    }
   /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Obejct  
    */
    private function stringCitiesPosition($positionID){
        $cities = $this->PositionManager->getCitiesAvailable($positionID);//Fetch all the cities where this position is available
        
        $cities_select = "";
        $glue = "";
        //Iterates through the cities to create an array to be used in the form
        foreach($cities as $city){
            $cities_select .= $glue . $city->city_name . " ({$city->country_abbreviation})";
            $glue = ", ";
        }
        
        return $cities_select;
    }
   /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param int $positionID Position id 
    * @return Obejct  
    */
    private function TagsPosition($positionID): array{
        $tags = $this->PositionManager->getPositionTags($positionID);//Fetch all the TAGS listed for this position
        
        $tags_select = array();
        //Iterates through the cities to create an array to be used in the form
        foreach($tags as $tag){
            $tags_select[] = $tag->tag_name;
        }
        
        return $tags_select;
    }   
    public function renderDefault(int $page = 1): void
    {
        $search = $this->getParameter('search');
        $job_positions = $this->PositionManager->getActivePositions($search);
        
        $lastPage = 0;
        $opportunities_found = $job_positions->count();
        $jobsPage = $job_positions->page($page, 2, $lastPage);
        
        $ArrayPositions = array();
        foreach($jobsPage as $job_position){
            $cities_select = $this->stringCitiesPosition($job_position->id);
            $tags = $this->TagsPosition($job_position->id);
            
            $ArrayPositions[] = ['id' => $job_position->id,
                                 'slug' => $job_position->slug,
                                 'title' => $job_position->title, 
                                 'category_name' => $job_position->category_name,
                                 'content' => substr(strip_tags($job_position->lead), 0, 200) . "...",
                                 'img_src' => $job_position->img_src,
                                 'img_alt' => $job_position->img_alt,
                                 'cities' => $cities_select,
                                 'tags' => $tags];
        }
        
        $this->template->job_positions = $ArrayPositions;
        
        // Add information into the template
        $this->template->opportunities_found = $opportunities_found;
        $this->template->opportunities_found_text = $opportunities_found > 1 ? "opportunities found" : "opportunity found";
        $this->template->page = $page;
        $this->template->search = $search;
        $this->template->lastPage = $lastPage;
    }
    
    protected function createComponentSearchForm(): Form
    {    
        $form = new Form; 
        $form->addText('search', 'Search')
                ->setHtmlAttribute("class=\"text p-1\" placeholder=\"Position title here\"")                
                ->setDefaultValue($this->getParameter('search'))
                ->setMaxLength(255)
                ->addCondition($form::MAX_LENGTH, 255);
        
        $form->addSubmit('send', 'Search')
                ->setHtmlAttribute("class=\"button bg-success text-white border-0 rounded p-1 px-2 d-none d-md-block\"");         
        $form->onSuccess[] = [$this, 'SearchPosition'];
        return $form;
    }
    
    public function SearchPosition(Form $form, array $values){
        $this->redirect('this?page=1&search=' . $values["search"]);
    }
    
    public function sendPayload(): void{
        if ($this->hasFlashSession()) {
            $flashes = $this->getFlashSession();
            $this->payload->flashes = iterator_to_array($flashes->getIterator());
            $flashes->remove();
        }
        
        $this->payload->allowAjax = false;
        parent::sendPayload();
    }
}
