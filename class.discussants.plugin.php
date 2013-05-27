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

/**
 * Add additional info to discussion
 *
 * @param  array  $Sender  
 * @param  array  $Args
 *
 * @return void
 */
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
  
  public function CategoriesController_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
  }
  public function DiscussionsController_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
  }
  public function ProfileController_Render_Before($Sender) {
    $Sender->AddCssFile($this->GetResource('design/custom.css', FALSE, FALSE));
  }

  public function CategoriesController_AfterDiscussionTitle_Handler($Sender) {
    $this->InsertDiscussants($Sender);
  }
  public function DiscussionsController_AfterDiscussionTitle_Handler($Sender) {
    $this->InsertDiscussants($Sender);
  }
  public function ProfileController_AfterDiscussionTitle_Handler($Sender) {
    $this->InsertDiscussants($Sender);
  }
  
  protected function InsertDiscussants($Sender) {
/* Does not work!
		$Sender->View = $this->GetView('discussants.php');
		$Sender->Render();
*/
    $Discussants = unserialize(GetValue('Discussants', $Sender->EventArguments['Discussion']));
    
    $FirstDiscussant = $Discussants[2]; // Discussion Author
    $LastDiscussant =  $Discussants[3]; // Last Annotator
    
    $CssClassFirst = intval($Discussants[1][$FirstDiscussant]/10); 
    $CssClassLast = intval($Discussants[1][$LastDiscussant]/10);

    unset($Discussants[1][$FirstDiscussant]);
    unset($Discussants[1][$LastDiscussant]);

    $output = '<div class="DiscussantsContainer"><span class="DiscussantsFirst Discussants'.$CssClassFirst.'">'.$FirstDiscussant.'</span>';
    $Discussants[1]=array(4 => 34, 10 => 68, 5 => 59, 6 => 100, 11 => 17, 7 => 12, 15 => 90, 33 => 20, 21 => 61, 8 => 4, 12 => 33, 9 => 80);
    
    // maximum usercount we show without placeholders is 8 (first + last + rest)
    if (count($Discussants[1]) <= 6) {
      // loop through percentages of 
      foreach ($Discussants[1] as $key => $value) {
        $CssClass = 'Discussants'.intval($value/10); // Discussants0, Discussants1, ... Discussants9, Discussants10
        $output .= "<span class=\"{$CssClass}\">{$key}</span>";
      }
    } else { 
    // split output, showing first, 5 f rest, placeholder, last
      $output .= 'steam
      <span class="placeholder">...</span>';
      
    }
    if ($FirstDiscussant != $LastDiscussant) { 
      $output .= '<span class="DiscussantsLast Discussants'.$CssClassLast.'">'.$LastDiscussant.'</span>';
    }
    echo $output.'</div>';

  // maybe overlay first with a small alpha top left and last with a small omega top right?
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
    // TODO: fill new column for existing discussions
  } // End of Setup
}