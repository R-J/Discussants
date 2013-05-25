<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Discussants'] = array(
	'Name' => 'Discussants',
	'Description' => 'Shows all members of a discussion on discussion index',
	'Version' => '0.2',
	'Author' => 'Robin',
	'RequiredApplications' => array('Vanilla' => '>=2.0.18'),
	'RequiredTheme' => False, 
	'RequiredPlugins' => False,
	'License' => 'GPL'
);

// TODO: handle for deletion of comments

class DiscussantsPlugin extends Gdn_Plugin {

  public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
    $UserID = $Args['FormPostValues']['InsertUserID'];

    // arr(user->postingcount),arr(user->percentage), author, last annotator
    $Discussants = array(
      array($UserID => 1)
      , array($UserID => 100)
      , $UserID
      , $UserID
    );
    
    DiscussantsModel::InitDiscussants(
      $Args['DiscussionID']
      , $UserID
      , $Discussants);
  } // End of DiscussionModel_AfterSaveDiscussion

  public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
    $DiscussionID = $Args['FormPostValues']['DiscussionID'];
    $UserID = $Args['FormPostValues']['InsertUserID'];

    $Discussants = DiscussantsModel::GetDiscussants($DiscussionID);

    // increase posting count for user
    $Discussants[0][$UserID] += 1;
    
    $MaxPostingCount = max($Discussants[0]);
    
    // recalculate percentage on base of current max posting count
    foreach ($Discussants[1] as $user => $value) {
      $Discussants[1][$user] = intval(ceil($Discussants[0][$user] / $MaxPostingCount * 100));
    }
    
    // in case of a new annotator, this will add his percentage to the percentage array
    $Discussants[1][$UserID] = intval(ceil($Discussants[0][$UserID] / $MaxPostingCount * 100));
    
    // update last annotator
    $Discussants[3] = $UserID;

    DiscussantsModel::SetDiscussants($DiscussionID, $Discussants);
  } // End of CommentModel_AfterSaveComment
 
/**
 * Add a new column to table Discussion and update list of discussion members
 * @param void
 *
 * @return void
 */ 
  public function Structure() {
    // Discussants = serialized arr(arr(user->postingcount)
    // , arr(user->percentage), author, last annotator)
    Gdn::Structure()
      ->Table('Discussion')
      ->Column('Discussants', 'text', TRUE) // must be NULLable!
      ->Set(FALSE, FALSE);
  } // End of Structure
   
  public function Setup() {
    $this->Structure();
    // TODO: fill new column for existing discussions
  } // End of Setup
}