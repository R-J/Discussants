<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Discussants'] = array(
	'Name' => 'Discussants',
	'Description' => 'Shows all members of a discussion on discussion index',
	'Version' => '0.1',
	'Author' => 'Robin',
	'RequiredApplications' => array('Vanilla' => '>=2.0.18'),
	'RequiredTheme' => False, 
	'RequiredPlugins' => False,
	'License' => 'GPL'
);
/* great blueprint https://github.com/unlight/ThankfulPeople */
/** learning goals:
  *  (gitshit?)
  * DONE 1. how to add column
  * DONE 2. write dummy text to my new column
  * DONE 3. debug dummy (timestamp?) to table after each refresh of entry in
  *     discussion table
  * DONE, DONE KEEPING TRACK 4. get users of discussion.
  * DONE, NOT NECESSARY 5. info 4. surely exists as a singe array. find out which
  * DONE, COUNTING WHEN HAPPENING 6. count their comments !!!first one not being 
        a comment but a discussion?!!!
  * DONE, NOT REACHABLE WHEN NEEDED 7. Does this info already exist too?
  * DONE, BUT CHANGED 8. write serialized array (see structure) to table
  *  9. find out event to hook for discussion index (see indexphotos)
  * 10. search what :hover helper functions exist
  */
// TODO: comment deletion must be considered!

class DiscussantsPlugin extends Gdn_Plugin {

 /* just copied, not understood yet: what is that for?
	public function __construct() {
		$this->Session = Gdn::Session();
	}
*/

 /* Just in order to test and debug: eyerytime I call the profile, table gets updated */
  public function ProfileController_Render_Before($Sender) {
   
    $test = serialize(
      array(
          array(2, 8, 100)
        , array(1, 6, 75)
        , array(3, 1, 7)
      )  
    );
    Gdn::SQL()
      ->Update('Discussion')
			->Set('Discussants', $test)
			->Where('DiscussionID', 2)
			->Put();

    /** LessonsLearned
      * Nowhere documented put function to update a table :-(
      * best have a look at library/database/class.sqldriver.php
      * if you know sql and/or understood the way garden builds sql querys, it is
      * very straight forward, though
      */
  }

  public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
  
    $DiscussionID = $Args['DiscussionID'];
    $UserID = $Args['FormPostValues']['InsertUserID'];

    // for first posting no intelligence is needed. Store Count, Percentage, Discussion owner and last commentator
    $value = array(
      array($UserID => 1)
      , array($UserID => 100)
      , $UserID
      , $UserID
    );
    
    
    // might be encapsulated in "DiscussantsModel" as DiscussantsInit
    Gdn::SQL()
    ->Update('Discussion')
    ->Set('Discussants', serialize($value))
    ->Where('DiscussionID', $DiscussionID)
    ->Put();
   
/** LessonsLearned:
  * NEVER, NEVER use githhub as a reference for your development!
  * I spent 4 hours trying to get a hook working which was in the github source
  * of discussionmodel but which wasn't implemented in my test installation *ARGH*
  *
  * Events get fired with $sender being the controller? and $args which are
  * defined beforehand with $this->EventArguments['ArgName']. Looking at source is
  * best reference
*/

  }

  public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
    $DiscussionID = $Args['FormPostValues']['DiscussionID'];
    $UserID = $Args['FormPostValues']['InsertUserID'];

  $this->DiscussantsUpdate($DiscussionID, $UserID);

    /** LessonsLearned:
  * NOTHING! HA HA! Well, I did the same thing as with DiscussionModel but wait...
  * DiscussionID wasn't passed in $Args explicitly so I dumped $Args to my table 
  * and unserialized it with some online tool in order to find out where I can
  * get the DiscussionID
  * TODO: should I use GetValue('DiscussionID', $Args['FormPostValues'])
  * or $Args['FormPostValues']['DiscussionID'] for retrieving discussionID ?
  */
  
  }

 
   
  public function DiscussantsUpdate($DiscussionID, $UserID) {
  // two ways of calculating: either count() on comments or just calculate 
  // with what is already in discussants column
  // mode = delete will be called when comment is deleted
    
    $result = Gdn::SQL()
      ->Select('Discussants')
      ->From('Discussion')
      ->Where('DiscussionID', $DiscussionID)
      ->Get()->ResultArray();
    
    $Discussants = unserialize($result[0]['Discussants']);
    
    $Discussants[0][$UserID] += 1;
    $max = max($Discussants[0]);
    foreach ($Discussants[1] as $key => $value) {
      $Discussants[1][$key] = intval(ceil($Discussants[0][$key] / $max * 100));
    }
    $Discussants[1][$UserID] = intval(ceil($Discussants[0][$UserID] / $max * 100));
    
    $Discussants[3] = $UserID;
    
   Gdn::SQL()
    ->Update('Discussion')
    ->Set('Discussants', serialize($Discussants))
    ->Where('DiscussionID', $DiscussionID)
    ->Put();  
 /** LessonsLearned:
   * don't know php yet :-(
   * it took me awfully long, but is 2am and I'm proud of having reached this point :-)
   * the only new Garden related thing was the sql but that was easy.
   * ->ResultArray() instead of ->Result()
   */
 
  }

 
/**
  * Add a new column to table Discussion and update list of discussion members
  * each time, a comment in this discussion gets added or deleted
  * heavily stolen from "thankful people" by Jerl Liandri, pointed to by peregrin
  */ 
  public function Structure() {
    // extend disussion table for serialized array: array(UserID, CommentCountAbsolut, CommentCountPercentage)

    Gdn::Structure()
      ->Table('Discussion')
      ->Column('Discussants', 'text', TRUE) // must bbe NULLable!
      ->Set(FALSE, FALSE);

    /** LessonsLearned
      * There is no AddColumn or something like this!
      * Explicit = FALSE means existing columns will not be touched if you use 
      * the Set() command and therefore you could use Table()->Column()->Set()
      * for creating AND extending tables.
      * TODO: find out how to delete a row
      */
  }
   
  public function Setup() {
    $this->Structure();
    /* fill the column! otherwise only discussions with comments later than
       activation time will benefit */
  }
}
