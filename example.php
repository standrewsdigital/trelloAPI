require_once 'trello.php';

$trello = new trello;
$trello->setAPIKey('xxxxxxxxxxxxxxxxxxxxx');
$trello->setAPIToken('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
$trello->setBoard(array('id'=>'xxxxxxxxxxxxxxxxxxxxx'));

// get labels
foreach ($trello->getLabels() as $Label => $value) {
   $labels[strtolower(trim($value->name))]   = $value->id;  
}

// make a card
$card = array();
$card['cardName'] = 'I want to: ';
$card['cardDesc'] = '**So that: ';
$card['idLabels'] = 'xxxxxxxxxxxxxxx'
$card['customdata']['ID'] = '45516';
$card['checklist'] = array('name'=>'Acceptance criteria');
$card['checklist']['values'][0] = 'Item 1';

// add to trello
$trello->addItem($card);