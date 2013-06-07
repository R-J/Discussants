<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Discussants'] = array(
    'Name' => 'Discussants',
    'Description' => 'Shows all members of a discussion on discussion index',
    'Version' => '0.8',
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
        $this->DiscussantsUpdate($Args['DiscussionID']);
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
        $this->DiscussantsUpdate($Args['FormPostValues']['DiscussionID']);
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
        $this->DiscussantsUpdate($DiscussionID, $CommentID);
// debug: deleting one comment renews all information
$this->DiscussantsUpdate();
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
        if ($Discussants==false) {
            return;
        }
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
        $output_hidden = '';

        foreach ($Discussants[1] as $key => $value) {
            $CssClass = 'Discussants'.intval($value/10); // Discussants0, Discussants1, ... Discussants9, Discussants10
            
            $DiscussantsCounter += 1;
            if ($DiscussantsCounter >= 7 && (count($Discussants[1]) > 8)) {
                $output_hidden .= UserPhoto($Users[$key], $CssClass);
            } else {
                $output .= UserPhoto($Users[$key], $CssClass);
            }
        }
      
        if ($output_hidden != '') {
            $output_placeholder = Img('plugins/Discussants/design/placeholder.png', array('class' => 'DiscussantsPlaceholder'));
            $output_hidden = HoverHelp($output_placeholder, $output_hidden);            
        }

        echo $output_pre.$output.$output_hidden.$output_post;
    }

/**
 * Updates Discussants info in discussion
 * 
 * @param type $DiscussionID
 */ 
    protected function DiscussantsUpdate($DiscussionID = '', $DeleteCommentID =''){
        $DiscussionModel = new DiscussionModel();
        $CommentModel = new CommentModel();

        if ($DiscussionID == '') {
            // Update Discussants of _all_ discussions if no param is given
            $Discussions = $DiscussionModel->Get()->ResultArray();
        } else {
            // or else use only the given discussion
            $Discussion = $DiscussionModel->GetID($DiscussionID);
            $Discussions[0]['DiscussionID'] = $Discussion->DiscussionID;
            $Discussions[0]['InsertUserID'] = $Discussion->InsertUserID;
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
            // we are forced to give a limit, so choose an extremly high value
            $Comments = $CommentModel->Get($DiscussionID, 9999999)->ResultArray();
            foreach ($Comments as $Comment) {
                // do not count comments that will be deleted
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
                $Discussants[1][$user] = intval(ceil($Discussants[0][$user] / $MaxPostingCount * 100));
            }
            DiscussantsModel::SetDiscussants($Discussion['DiscussionID'], $Discussants);
//DiscussantsModel::debug($Discussions->DiscussionID);            
        }
    }
    

/**
 * Add a new column to table Discussion and update list of discussion members
 * @param void
 *
 * @return void
 */ 
    private function Structure() {
        // Discussants = serialized arr(arr(user->postingcount)
        // , arr(user->percentage), author, last annotator)
        Gdn::Structure()
        ->Table('Discussion')
        ->Column('Discussants', 'text', TRUE) // must be NULLable!
        ->Set(FALSE, FALSE);
    } // End of Structure
   
    public function Setup() {
        $this->Structure();
        // $this->DiscussantsUpdate();
        
    } // End of Setup

    public function OnDisable() {
        // Delete additional column
        $Database = Gdn::Database();
        $Structure = $Database->Structure();
        $Px = $Database->DatabasePrefix;
        $Structure->Query("ALTER TABLE {$Px}Discussion drop column Discussants");
   }
}