<?php if (!defined('APPLICATION')) exit();

class DiscussantsModel extends Gdn_Model {

/**
 * Save discussants info when discussion is created
 *
 * @param  integer  $DiscussianID  ID of new discussion
 * @param  integer  $UserID        ID of discussion author
 * @param  array    $Discussants   Value to store
 *
 * @return void
 */
  public static function InitDiscussants($DiscussionID, $UserID, $Discussants) {
    // write serialized array to new new column in table Discussion
    Gdn::SQL()
    ->Update('Discussion')
    ->Set('Discussants', serialize($Discussants))
    ->Where('DiscussionID', $DiscussionID)
    ->Put();
  } // End of InitDiscussants
 
/**
 * Gets what extra information is stored about discussants
 *
 * @param  integer  $DiscussionID  ID of new discussion
 *
 * @return array    $Discussants   arr(arr(user->postingcount), arr(user->percentage), author, last annotator)
 */ 
  public static function GetDiscussants($DiscussionID) {
    $result = Gdn::SQL()
      ->Select('Discussants')
      ->From('Discussion')
      ->Where('DiscussionID', $DiscussionID)
      ->Get()->ResultArray();
    return unserialize($result[0]['Discussants']);
  } // End of GetDiscussants
  

/**
 * Sets extra information about discussants
 *
 * @param  integer  $DiscussionID  ID of new discussion
 * @param  array    $Discussants   arr(arr(user->postingcount), arr(user->percentage), author, last annotator)
 *
 * @return void
 */ 
  public static function SetDiscussants($DiscussionID, $Discussants) {  
    Gdn::SQL()
      ->Update('Discussion')
      ->Set('Discussants', serialize($Discussants))
      ->Where('DiscussionID', $DiscussionID)
      ->Put(); 
  } // End of SetDiscussants
} // End of class