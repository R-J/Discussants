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

/**
 * Add init info to discussion
 *
 * @param  array  $Sender  
 * @param  array  $Args
 *
 * @return void
 */
  public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
    // $this->DiscussantsUpdate($Args['DiscussionID']);
    $UserID = $Args['FormPostValues']['InsertUserID'];

    // arr(user->postingcount),arr(user->percentage), author, last annotator)
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

/**
 * Add additional info to discussion
 *
 * @param  array  $Sender  
 * @param  array  $Args
 *
 * @return void
 */
  public function CommentModel_AfterSaveComment_Handler($Sender, $Args) {
    // $this->DiscussantsUpdate($Args['FormPostValues']['DiscussionID']);
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
 * Reduces count in extra info
 *
 * @param  array  $Sender  
 *
 * @return void
 */
  public function CommentModel_DeleteComment_Handler($Sender) {
    $CommentID = $Sender->EventArguments['CommentID'];
    $CommentModel = new CommentModel();
    $Comment = $CommentModel->GetID($CommentID);
    $DiscussionID = GetValue('DiscussionID', $Comment);
    $Discussants = DiscussantsModel::GetDiscussants($DiscussionID);
    $UserID = GetValue('InsertUserID', $Comment);

    // decrease postingcount or delete user if that was his last comment
    if ($Discussants[0][$UserID] == 1) {
        unset($Discussants[0][$UserID]);
        unset($Discussants[1][$UserID]);
    } else {
        $Discussants[0][$UserID] = $Discussants[0][$UserID] - 1;
    }
    
    // recalculate percentage on base of current max posting count
    $MaxPostingCount = max($Discussants[0]);
    foreach ($Discussants[1] as $user => $value) {
      $Discussants[1][$user] = intval(ceil($Discussants[0][$user] / $MaxPostingCount * 100));
    }
    
    // update last annotator if necessary
    if ($Discussants[3] == $UserID) {
        $DiscussionModel = new DiscussionModel();
        $Discussion = $DiscussionModel->GetID($DiscussionID);
        $Discussants[3] = GetValue('LastCommentUserID', $Discussion);
        // $Discussants=array($Discussants[3], $UserID)
    }
    DiscussantsModel::SetDiscussants($DiscussionID, $Discussants);
    
    // $this->DiscussantsUpdate($DiscussionID, $CommentID);
  }
  
   // Add CSS file
  public function CategoriesController_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
  }
  public function DiscussionsController_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
  }
  public function ProfileController_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
  }

  // Add View
  public function CategoriesController_AfterDiscussionTitle_Handler($Sender) {
    $this->DiscussantsView($Sender);
  }
  public function DiscussionsController_AfterDiscussionTitle_Handler($Sender) {
    $this->DiscussantsView($Sender);
  }
  public function ProfileController_AfterDiscussionTitle_Handler($Sender) {
    $this->DiscussantsView($Sender);
  }
  
  protected function DiscussantsView($Sender) {
/* Does not work!
		$Sender->View = $this->GetView('discussants.php');
		$Sender->Render();
*/
    // get Discussants of current Discussion
    $Discussants = unserialize(GetValue('Discussants', $Sender->EventArguments['Discussion']));
    
    // Set First and Last Discussant
    $FirstDiscussant = $Discussants[2]; // Discussion Author
    $LastDiscussant =  $Discussants[3]; // Last Annotator
    
    // As well as their css classes
    $CssClassFirst = 'DiscussantsFirst Discussants'.intval($Discussants[1][$FirstDiscussant]/10); 
    $CssClassLast = 'DiscussantsLast Discussants'.intval($Discussants[1][$LastDiscussant]/10);

    // get all user in discussion
    $UserModel = new UserModel();
    $Users = $UserModel->GetIDs(array_keys($Discussants[1]));

    // First Discussant
    $output_pre = '<div class="DiscussantsContainer">';
    $CssClass = $CssClassFirst;
    if ($FirstDiscussant == $LastDiscussant) { 
      $CssClass .= ' DiscussantsLast ';
    }
    $output_pre .= UserPhoto($Users[$FirstDiscussant], $CssClass);

    // Last Discussant (only if there is more than 1 discussant in discussion)
    if (count($Discussants[1]) > 1) {
      $output_post = UserPhoto($Users[$LastDiscussant], $CssClassLast);
    }
    $output_post .= '</div>';

    // delete first and last for easy handling
    unset($Discussants[1][$FirstDiscussant]);
    unset($Discussants[1][$LastDiscussant]);

    // loop through all discussants until max to show is reached
    $DiscussantsCounter = 0;
    foreach ($Discussants[1] as $key => $value) {
      $CssClass = 'Discussants'.intval($value/10); // Discussants0, Discussants1, ... Discussants9, Discussants10
      $output .= UserPhoto($Users[$key], $CssClass);
      $DiscussantsCounter += 1;
      if ($DiscussantsCounter == 7 && (count($Discussants[1]) > 9)) {
        $output .= '<span class="DiscussantsHidden">'.Img('plugins/Discussants/design/placeholder.png', array('class' => 'DiscussantsPlaceholder'));
        // $this->GetResource('design/custom.css', FALSE, FALSE)
        $output_add = '</span>';
      }
    }
    
    echo $output_pre.$output.$output_add.$output_post;
    
  }

/**
 * Updates Discussants info in discussion
 * 
 * @param type $DiscussionID
 */ 
  protected function DiscussantsUpdate($DiscussionID = '', $DeleteCommentID =''){
    $DiscussionModel = new DiscussionModel();
    $CommentModel = new CommentModel();
    if ($DiscussionID = '') {
        // Update Discussants of _all_ discussions if no param is given
        $Discussions = $DiscussionModel->Get()->ResultArray();
    } else {
        $Discussions = $DiscussionModel->GetID($DiscussionID)->ResultArray();
    }
    foreach ($Discussions as $Discussion) {
        $DiscussionID = $Discussion['DiscussionID'];
        $UserID = $Discussion['InsertUserID'];
        // init values
        $Discussants = array(
            array($UserID => 1)
            , array($UserID => 100)
            , $Discussion['InsertUserID']
            , $Discussion['InsertUserID']
        );
        $Comments = $CommentModel->Get($DiscussionID, 999999999999999)->ResultArray();
        foreach ($Comments as $Comment) {
            // if current comment the one that will be deleted, go to next
            if ($Comment['CommentID'] == $DeleteCommentID) {
                continue;
            } 
            $UserID = $Comment['InsertUserID'];
            $Discussants[0][$UserID] = $Discussants[0][$UserID] + 1;
            $Discussants[1][$UserID] = 100; // init percentage array
        }
        $Discussants[3] = $UserID;
        // calculate percentage on base of current max posting count
        $MaxPostingCount = max($Discussants[0]);
        foreach ($Discussants[1] as $user => $value) {
            $Discussants[1][$use<r] = intval(ceil($Discussants[0][$user] / $MaxPostingCount * 100));
        }
        DiscussantsModel::SetDiscussants($Discussion['DiscussionID'], $Discussants);
    }
 }
 
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
    $this->DiscussantsUpdate();
  } // End of Setup
}