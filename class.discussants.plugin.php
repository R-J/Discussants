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
  *  4. get users of discussion.
  *  5. info 4. surely exists as a singe array. find out which
  *  6. count their comments !!!first one not being 
        a comment but a discussion?!!!
  *  7. Does this info already exist too?
  *  8. write serialized array (see structure) to table
  *  9. find out event to hook for discussion index (see indexphotos)
  * 10. search what :hover helper functions exist
  */

class DiscussantsPlugin extends Gdn_Plugin {

/* just copied, not understood yet: what is that for?
	public function __construct() {
		$this->Session = Gdn::Session();
	}
*/

  public function ProfileController_Render_Before($Sender) {
    /* Just in order to test: eyerytime I call the profile, table gets updated */
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
    
    Gdn::SQL()
    ->Update('Discussion')
    ->Set('Discussants', serialize($Args['Fields']))
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

//UpdateDiscussant($DiscussionID, serialized string)

  }

  public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
    $DiscussionID = $Args['FormPostValues']['DiscussionID'];
    
    Gdn::SQL()
    ->Update('Discussion')
    ->Set('Discussants', serialize($Args))
    ->Where('DiscussionID', $DiscussionID)
    ->Put();  
/** LessonsLearned:
  * NOTHING! HA HA! Well, I did the same thing as with DiscussionModel but wait...
  * DiscussionID wasn't passed in $Args explicitly so I dumped $Args to my table 
  * and unserialized it with some online tool in order to find out where I can
  * get the DiscussionID
  * TODO: should I use GetValue('DiscussionID', $Args['FormPostValues'])
  * or $Args['FormPostValues']['DiscussionID'] for retrieving discussionID ?
  */
  
  //UpdateDiscussant($DiscussionID, serialized string)
  }
  
  
  public function UpdateDiscussants($DiscussionID, $DiscussantContent) {
  // two ways of calculating: either count() on comments or just calculate 
  // with what is already in discussants column
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
      
  /*
  // need this for updating?
      $RequestArgs = Gdn::Controller()->RequestArgs;
      if (ArrayHasValue($RequestArgs, 'vanilla')) {
        ThanksLogModel::RecalculateUserReceivedThankCount();
      }
  */    
  }
  public function Setup() {
    $this->Structure();
    /* fill the column! otherwise only discussions with comments later than
       activation time will benefit */
  }
}
