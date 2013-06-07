<?php if (!defined('APPLICATION')) exit();

    $Discussants = unserialize(GetValue('Discussants', $Sender->EventArguments['Discussion']));
    // if something goes wrong, do not print out anything at all
    if ($Discussants==false) {
        return;
    }
    // class names:
    // DiscussantsFirst, DiscussantsLast
    // Discussants0, Discussants1 .. Discussants9, Discussants10
    
    $UserModel = new UserModel();
    
    // Set First and Last Discussant
    $FirstDiscussant = $Discussants[2]; // Discussion Author
    $LastDiscussant =  $Discussants[3]; // Last Annotator

    // As well as their css classes
    $FirstDiscussantPercentage = $Discussants[1][$FirstDiscussant]; 
    $LastDiscussantPercentage = $Discussants[1][$LastDiscussant];

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
        
        
        
?>
<div class="DiscussantsContainer">
    <?php
    // special case if we have only 1 discussant
    if (count($Discussants[1]) == 1) {
        $User = $UserModel->GetID($Discussants[2]);
        echo UserPhoto($User, 'Discussants10 DiscussantsFirst DiscussantsLast');
    } else {
        $Users = $UserModel->GetIDs(array_keys($Discussants[1]));
        // Set First and Last Discussant
        $FirstDiscussant = $Discussants[2]; // Discussion Author
        $LastDiscussant =  $Discussants[3]; // Last Annotator

        // As well as their css classes
        $FirstDiscussantCSS = 'Discussants'.intval($Discussants[1][$FirstDiscussant]/10).' DiscussantsFirst'; 
        $LastDiscussantCSS = 'Discussants'.intval($Discussants[1][$LastDiscussant]/10).' DiscussantsLast';
        
        // delete first and last for easy handling
        unset($Discussants[1][$FirstDiscussant]);
        unset($Discussants[1][$LastDiscussant]);
        
        // how much avatars should be shown before and after placeholder is displayed
        $MaxToShow = 4;
        $UserIDs = array_keys($Discussants[1]);
        if ((count($UserIDs) + 2) > (2 * $MaxToShow)) {
            // show placeholder
            echo UserPhoto($User, $FirstDiscussantCSS.' DiscussantsFirst');
            for ($i=0; $i<=2; $i++) {
                echo UserPhoto($UserID, $CssClass);
            }
            echo '<span class="DiscussantsPlaceholder">...</span><span class="DiscussantsHidden">';
            echo '</span>';
            echo UserPhoto($User, $LastDiscussantCSS.' DiscussantsLast');
        } else {
            // show all and do not show placeholder
        }
        
        
    }
    ?>
</div>
