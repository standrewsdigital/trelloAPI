<?php
/**
 * Trello API Class 
 * @author nick mullen
 */

class trello {
   private $apiKey   = '';  
   private $apiToken = ''; 
   private $board    = array();
   private $API      = 'https://api.trello.com';
   private $list;               // target list
   private $lists    = array(); // all lists

   /**
    * Set apiKey
    */
   public function setAPIKey($apiKey) {
      $this->apiKey = trim($apiKey);
   }

   /**
    * Set apiToken
    */
   public function setAPIToken($apiToken) {
      $this->apiToken = trim($apiToken);
   }

   /**
    * Set target borad
    */
   public function setBoard($board) {
      $this->board = $board;  
      $this->getLists();  // request all lists for the board
      $this->getLabels(); // request all labels from the board
   }

   /**
    * Set target list
    */
   public function setList($list) {
      $this->list = $list;
     
   }

   /**
    * Get target list id
    */
   public function getListID() {
      if($this->list !== null){
         return $this->list->id;
      }
      return false;
   }

   /**
    * Get board id
    */
   public function getBoardID() {
      return $this->board['id'];
   }

   /**
    * Add a trello card
    */
   public function addItem($card){
   	  if($this->getListID() == null){
         throw new Exception("Error list id is not set", 1); 
   	  }
   	  
      // new card
      $myCard = json_decode($this->addCard($card));
      
      // add customdata to card
      if(isset($card['customdata'])){
         foreach($card['customdata'] as $cData){
            $result = $this->addCustomField($myCard->id,$cData);
         }
      }

      // add a checklist
      if(isset($card['checklist'])){
        $checklist = json_decode($this->newCheckList($myCard->id,$card['checklist']));
        if(isset($card['checklist']['values'])){
        	//echo 'checklist created id='.$checklist->id;
        	foreach($card['checklist']['values'] as $value){
        	   $result = $this->newCheckListItem($checklist->id,$value);
        	}
        }
      }
      return $result;
   }

   /**
    * Get all labels form the target list
    */
   public function getLabels(){
      if($this->getBoardID() == null){
         throw new Exception("Error list id is not set", 1); 
      }
      $card['entity'] ='boardlabels';
      $call['url'] = $this->buildUrl($card); 
      $call['notdata'] = true;
      $this->labels = json_decode($this->callTrello($call));
      return $this->labels;
   }

   /**
    * Get lable by name
    */
   public function getLabel($label){
      if($this->labels == null){
         throw new Exception("Error labels not set", 1); 
      }
      $key = array_search($label, array_column($this->labels, 'name'));
      if($key !== false){
         return $this->labels[$key];
      }
      return false;
   }

  /**
   * Get all lists
   **/
  public function getLists(){
     if($this->getBoardID() == null){
       throw new Exception("Error list id is not set", 1); 
     }
     $card['entity'] ='lists';
     $call['url'] = $this->buildUrl($card); 
     $call['notdata'] = true;   
     $this->lists = json_decode($this->callTrello($call));
     return $this->lists;
   }

   /**
    * Get list by name
    */
   public function getList($list){
      if($this->lists == null){
         throw new Exception("Error lists not set", 1); 
      }
      $key = array_search(trim($list), array_column($this->lists, 'name'));
      if($key !== false){
         return $this->lists[$key];
      }
      return false;
   }

   /**
    *  Set the target list by list name
    **/
   public function setTargetList($listName){
      $target = $this->getList($listName);
      if($target){
         $this->list = $target;
         return true;
      }
      return false;
    }

   /**
    * Add a trello card
    */
   public function addCard($card){
   	  if($this->getListID() == null){
         throw new Exception("Error list id is not set", 1); 
   	  }
      $card['entity'] ='card';
   	  $call['url'] = $this->buildUrl($card); 
   	  $result = $this->callTrello($call);
      return $result;
   }

   /**
    * Get all customer data field
    */
   public function getCustomfields(){
  	  if($this->getBoardID() == null){
         throw new Exception("Error list id is not set", 1); 
   	  }
   	  $card['entity'] ='getcustomfields';
   	  $call['url'] = $this->buildUrl($card); 
   	  $call['notdata'] = true;
   	  $result = $this->callTrello($call);
      return $result;
   }

   /**
    * Makes a new check list
    **/
   public function newCheckList($cardID,$args){
      $args['entity'] ='newchecklist';
      $args['cardID'] =$cardID;
   	  $call['url'] = $this->buildUrl($args); 
   	  $result = $this->callTrello($call);
      return $result;
   }

   /**
    * Add item to check list
    **/
    public function newCheckListItem($checklistID,$value){
      $args['entity'] ='listitem';
      $args['checklistid'] = $checklistID;
      $args['name'] = $value;
      $args['data']['name'] = $value;
   	  $call['url'] = $this->buildUrl($args);  
   	  $result = $this->callTrello($call);
      return $result;
    }

   /**
    * Add a custom field
    */
   public function addCustomField($cardID,$customField){
   	  $args['cardID'] = $cardID;
   	  $args['customFieldID'] = $customField['customFieldID'];
   	  $args['entity'] = 'customfield';
      $call['action'] = 'put';
      $call['url'] = $this->buildUrl($args);
      $call['data']['value'][$customField['type']] =  $customField['value'];
      $result = $this->callTrello($call);
      return $result;
   }

   /**
    * Make a URL 
    */
   public function buildUrl($args){
      $url ='';
      switch (strtolower($args['entity'])) {
      	case 'card':
      	$url = $this->API.'/1/cards?key='.$this->apiKey.'&token='.$this->apiToken.'&idList='.$this->getListID().'&name='.urlencode ( $args['cardName']).'&desc='.urlencode ( $args['cardDesc']);
      	  	$url .= isset($args['idLabels'])?'&idLabels='.$args['idLabels']:'';
      		break;
      	case 'newchecklist':
      		 $url = $this->API.'/1/checklists?key='.$this->apiKey.'&token='.$this->apiToken.'&idCard='.$args['cardID'].'&name='.urlencode ( $args['name']);
      		break;
        case 'listitem':
      		 $url = $this->API.'/1/checklists/'.$args['checklistid'].'/checkItems?key='.$this->apiKey.'&token='.$this->apiToken.'&name='.urlencode($args['name']);
      		 break;
        case 'getcustomfields':
      		$url = $this->API.'/1/boards/'.$this->getBoardID().'/customFields?key='.$this->apiKey.'&token='.$this->apiToken;
      		break;
      	case 'customfield':
      	    // adding custom field to card
      		$url = $this->API.'/1/cards/'.$args['cardID'].'/customField/'.$args['customFieldID'].'/item?key='.$this->apiKey.'&token='.$this->apiToken;
      		break;	
        case 'boardlabels':
          // adding custom field to card
          $url = $this->API.'/1/boards/'.$this->getBoardID().'/labels?key='.$this->apiKey.'&token='.$this->apiToken;
          break;
        case 'lists':
           $url = $this->API.'/1/boards/'.$this->getBoardID().'/lists?key='.$this->apiKey.'&token='.$this->apiToken;
           break;
      	default:
      		
      		break;
      }
      return $url;
   }

   /**
    * Curl calling the API
    */
   private function callTrello($arg){
      if($this->apiKey == '' || $this->apiToken =='' ){
         throw new Exception("Error please set security keys", 1); 
      }

      $data = array(
        'key' => $this->apiKey,
        'token' => $this->apiToken
      );
      $listid = $this->getListID();
      if(isset($listid) ){
        $data = array(
          'idList' => $listid
        );
      }
      if(isset($arg['data']) && count($arg['data']) > 0){
      	  $myData = array_merge($data, $arg['data']);
      }else{
       	 $myData = $data;
      }
      $myData = json_encode($myData);
      $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    if(isset($arg['action']) && $arg['action'] == 'put'){
	       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	    }
      if(!isset($arg['notdata'])){
	       curl_setopt($ch, CURLOPT_POSTFIELDS, $myData);
	    }
		  curl_setopt($ch, CURLOPT_HTTPHEADER,
		    array(
		        'Content-Type:application/json',	    
		    )
	    );
	    curl_setopt($ch, CURLOPT_URL, $arg['url']);
	    $result = curl_exec($ch);
	    curl_close($ch);

	   return $result;
   } 
}
?>