<?php

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

use App\Model\PositionManager;
use App\Model\ApplicationManager;

/**
 * Class presenter for the position
 * @final
 * @author Luan Sidney <luansidneyseliga@gmail.com>
 * @@method void renderShow() Renders the position details interface
 * @@method void createComponentApplicationForm() Create an application form for the potential candidates
 * @@method void applicationFormSucceeded() Salves the candidate`s application.
 * @@method void sendPayload() Overwrites the sendPayload to save the Flash messages
 * @@method void callFashMessage(array $issues) Loop through the array informed and add a flash message for each one
 */

final class PositionPresenter extends Nette\Application\UI\Presenter
{
   /**
    * @access private
    */
    private PositionManager $PositionManager;
   /**
    * @access private
    */
    private ApplicationManager $ApplicationManager;
    
    public function __construct(PositionManager $PositionManager, ApplicationManager $ApplicationManager)
    {
        $this->PositionManager = $PositionManager;
        $this->ApplicationManager = $ApplicationManager;
    }
    
    public function renderShow(string $idfriendly): void
    {           
        $position = $this->PositionManager->getPositionSlug($idfriendly);
        if (!isset($position->id)) {
            $this->flashMessage("Position not found", "error");
            $this->redirectUrl("/QuickJob/www");
        }
        
        $this->template->page = $this->getParameter('page');
        $this->template->search = $this->getParameter('search');
        $this->template->position = $position;
    }
    
    protected function createComponentApplicationForm(): Form
    {        
        $cities = $this->PositionManager->getCitiesAvailableSlug($this->getParameter("idfriendly"));//Fetch all the cities where this position is available
        
        $cities_select = ["" => "Select"];
        //Iterates through the cities to create an array to be used in the form
        foreach($cities as $city){
            $cities_select[$city->id] = $city->city_name;
        }
        
        $form = new Form; 
        $form->setHtmlAttribute("class=\"apply-now\"");
        
        //Creates a group to contem all form fields
        $form->addGroup("Apply Now");
        
        //Create a input text field to capture the candidate first name
        $form->addText('fist_name', 'First Name')
                ->setRequired("Please fill your first name.")
                ->setMaxLength(255)
                ->addCondition($form::MAX_LENGTH, 255);
        
        //Create a input text field to capture the candidate last name
        $form->addText('last_name', 'Last Name')
                ->setRequired("Please fill your last name.")
                ->setMaxLength(255)
                ->addCondition($form::MAX_LENGTH, 255);

        //Create a input text field of the email type to capture the candidate`s email
        $form->addEmail('email', 'E-mail')
                ->setRequired("Please, provide your email address.")
                ->setMaxLength(255)
                ->addCondition($form::MAX_LENGTH, 255);
        
        //Create a input text field to capture the candidate`s phone
        $form->addText('phone_number', 'Phone Number')
                ->setRequired()
                ->setHtmlType('tel')
                ->setEmptyValue('+420')
                ->setMaxLength(255);
        
        //Create a input text field to capture the candidate`s Linkedln link
        $form->addText('linkedln', 'Linkedln')
                ->setMaxLength(255)
                ->addCondition($form::MAX_LENGTH, 255);
        
        //Create an open text filed area for the candidate ot answer why he/she is a good fit
        $form->addTextArea('why_you', 'Why you', 5, 5)
                ->setRequired("Please, let us knows why you are a good fit.");
        
        //Select dropdown with all cities where the position is open
        $form->addSelect('id_city', 'City', $cities_select)
                ->setRequired("Please, pick one city to apply for.");

        //Upload file filed, it allows up to 5 files and each might be up to 5MB of size.
        $form->addMultiUpload('files', 'You may upload up to 5 files of 5MB each (DOC, PDF or JPG)')
                //->setHtmlAttribute("onchange=\"updateImageDisplay();\"")  
                ->setHtmlAttribute("id=\"files_id\"")        
                ->addRule($form::MAX_LENGTH, 'A maximum of %d files can be uploaded', 5)
                ->addRule($form::MAX_FILE_SIZE, 'A maximum files size is 5 MB', 625000)
                ->addRule($form::MIME_TYPE, 'Please, upload the file in one of those formats PDF, DOC or JPG', ["image/jpeg", "application/pdf", "application/msword"]);
        
        $form->addSubmit('send', 'Apply')
                ->setHtmlAttribute("class=\"btn btn-primary\"");
                
        $form->onSuccess[] = [$this, 'applicationFormSucceeded'];
        return $form;
    }
    
    public function applicationFormSucceeded(Form $form, array $values): void
    {
        $position = $this->PositionManager->getPositionSlug($this->getParameter("idfriendly"));
        if (!$position) {
            $this->flashMessage("Position not found", "error");
            $this->redirectUrl("/QuickJob/www");
            return;
        }
        
        $values['id_job_position'] = $position->id;
        
        //Get all uploaded files
        $files = array();
        $httpRequest = $this->getHttpRequest();
        if(isset($httpRequest->getFiles()["files"])){
            $files = $httpRequest->getFiles()["files"];  
        }
        
        $reditect = true;
        try{
            if(!$this->ApplicationManager->insertApplication($values, $files)){
                $this->callFashMessage($this->ApplicationManager->getIssues());
                $reditect = false;
            }
        }catch (\Exception $e) {
            $reditect = false;
            $form->addError($e->getMessage());
        }    
        
        if($reditect){ 
            $this->flashMessage('Thank you for your application', 'success');
            //$this->redirect('this');
        }
        $this->redrawControl();
    }
   /*
    * @access public
    * @author Luan Sidney <luansidneyseliga@gmail.com>
    * @param array $issues Array with messages to be add on the flash messages pile   
    */
    public function callFashMessage($issues){
        foreach($issues as $title => $describtion){
            //$form->addError($title);
            $this->flashMessage($title, 'error');
        }
    }
    
    public function sendPayload(): void{
        //Sabe all fash messages in the payload, so they can be render after the ajax request
        if ($this->hasFlashSession()) {
            $flashes = $this->getFlashSession();
            $this->payload->flashes = iterator_to_array($flashes->getIterator());
            $flashes->remove();
        }
        
        //$this->payload->allowAjax = false;
        parent::sendPayload();
    }
}

