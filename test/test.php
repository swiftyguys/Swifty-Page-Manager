<?php

//include 'spm_story.php';
require '../php/probe/ss_ception.php';

////////////////////////////////////////

//class ThisStory extends SSStory {
class ThisStory extends SSCeption {
    protected $pluginName = 'Swifty Page Manager';

    function TakeAction() {
        parent::TakeAction();   // Must be called first!

        $this->WPLogin();

        // When plugin runs for the first time (without other SS plugins), SS mode must be off
        $this->DeleteCookie( 'ss_mode' );
        $this->Probe( 'SPM.CheckSSMode', '', array(
            "plugin_name" => $this->pluginName,
            "ss_mode" => 'wp'
        ) );

        foreach( array( 'ss_force', 'wp' ) as $ss_mode ) {
            $this->EchoMsg( "#\n#\n#\n# START RUNNING TESTS FOR MODE " . $ss_mode . "\n#\n#\n#" );

            $this->SetCookie( 'ss_mode', $ss_mode );

            $this->Probe( 'SPM.CheckRunning', '', array(
                "plugin_name" => $this->pluginName
            ) );

            $this->Probe( 'SPM.CheckSSMode', '', array(
                "plugin_name" => $this->pluginName,
                "ss_mode" => $ss_mode
            ) );

            $this->Probe( 'WP.DeleteAllPages', '', array(
                'plugin_name' => $this->pluginName
            ) );

            $this->Probe( 'WP.EmptyTrash', '', array(
                'plugin_name' => $this->pluginName
            ) );

            // dojh Enable when bug is fixed
            //        $this->Probe( 'SPM.NoPagesExist', '', array(
            //            'plugin_name' => $this->pluginName
            //        ) );

            $this->Probe( 'WP.CreateXDraftPages', '', array(
                'plugin_name' => $this->pluginName,
                'x_pages' => 4
            ) );

            $this->Probe( 'SPM.XPagesExist', '4', array(
                'plugin_name' => $this->pluginName,
                'x_pages'     => 4
            ) );

            $this->Probe( 'SPM.MovePage', '1 before 4', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 1',
                'destination' => 'WP Page 4',
                'position'    => 'before',
            ) );

            $this->Probe( 'SPM.PageExists', '1 at pos 3', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 1',
                'at_pos'      => 3
            ) );

            $this->Probe( 'SPM.PageExists', '3 at pos 2', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 3',
                'at_pos'      => 2
            ) );

            $this->Probe( 'SPM.MovePage', '1 before 2', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 1',
                'destination' => 'WP Page 2',
                'position'    => 'before',
            ) );

            $this->Probe( 'SPM.PageExists', '1 at pos 1', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 1',
                'at_pos'      => 1
            ) );

            $this->Probe( 'SPM.PageExists', '2 at pos 2', array(
                'plugin_name' => $this->pluginName,
                'page'  => 'WP Page 2',
                'at_pos'      => 2
            ) );

            $this->Probe( 'SPM.MovePage', '2 after 4', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 2',
                'destination' => 'WP Page 4',
                'position'    => 'after',
            ) );

            $this->Probe( 'SPM.PageExists', '2 at pos 4', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 2',
                'at_pos'      => 4
            ) );

            $this->Probe( 'SPM.PageExists', '4 at pos 3', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 4',
                'at_pos'      => 3
            ) );

            $this->Probe( 'SPM.MovePage', '2 before 1', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 2',
                'destination' => 'WP Page 1',
                'position'    => 'after',
            ) );

            $this->Probe( 'SPM.PageExists', '2 at pos 2', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 2',
                'at_pos'      => 2
            ) );

            $this->Probe( 'SPM.PageExists', '4 at pos 4', array(
                'plugin_name' => $this->pluginName,
                'page'        => 'WP Page 4',
                'at_pos'      => 4
            ) );

            $this->Probe( 'SPM.SavePage', '4 -> page last', array(
                'plugin_name' => $this->pluginName,
                'action' => 'add',
                'page' => 'WP Page 4',
                'values' => json_encode( array(
                    'post_title' => array(
                        'type' => 'text',
                        'value' => 'SPM Page last'
                    ),
                    'add_mode' => array(
                        'type' => 'radio',
                        'value' => 'after'
                    ),
                    'post_status' => array(
                        'type' => 'radio',
                        'value' => 'draft'
                    ),
                    'page_template' => array(
                        'type' => 'select',
                        'value' => 'Full Width Page'
                    )
                ) )
            ) );

            $this->Probe( 'SPM.XPagesExist', '5', array(
                'plugin_name' => $this->pluginName,
                'x_pages' => 5
            ) );

            $this->Probe( 'SPM.SavePage', '1 -> page second', array(
                'plugin_name' => $this->pluginName,
                'action' => 'add',
                'page' => 'WP Page 1',
                'values' => json_encode( array(
                    'post_title' => array(
                        'type' => 'text',
                        'value' => 'SPM Page second'
                    ),
                    'add_mode' => array(
                        'type' => 'radio',
                        'value' => 'after'
                    ),
                    'post_status' => array(
                        'type' => 'radio',
                        'value' => 'draft'
                    )
                ) )
            ) );

            $this->Probe( 'SPM.XPagesExist', '6', array(
                'plugin_name' => $this->pluginName,
                'x_pages' => 6
            ) );

            $this->Probe( 'SPM.PageExists', 'page last at pos 6', array(
                'plugin_name' => $this->pluginName,
                'page' => 'SPM Page last',
                'x_pages' => 1,
                'at_pos' => 6
            ) );

            $this->Probe( 'SPM.CheckPageStatus', 'page last = draft', array(
                'plugin_name' => $this->pluginName,
                'page' => 'SPM Page last',
                'is_status' => 'draft'   // draft | publish
            ) );

            $this->Probe( 'SPM.PageExists', 'page second at pos 2', array(
                'plugin_name' => $this->pluginName,
                'page' => 'SPM Page second',
                'x_pages' => 1,
                'at_pos' => 2
            ) );

            $this->Probe( 'SPM.SavePage', 'page second -> tweede pagina; publish', array(
                'plugin_name' => $this->pluginName,
                'action' => 'edit',
                'page' => 'SPM Page second',
                'values' => json_encode( array(
                    'post_title' => array(
                        'type' => 'text',
                        'value' => 'Tweede SPM Pagina'
                    ),
                    'post_status' => array(
                        'type' => 'radio',
                        'value' => 'publish'
                    ),
                    'page_template' => array(
                        'type' => 'select',
                        'value' => 'Contributor Page'
                    )
                ) )
            ) );

            $this->Probe( 'SPM.PageExists', 'tweede pagina at pos 2', array(
                'plugin_name' => $this->pluginName,
                'page' => 'Tweede SPM Pagina',
                'x_pages' => 1,
                'at_pos' => 2
            ) );

            $this->Probe( 'SPM.CheckPageStatus', 'tweede pagina = publish', array(
                'plugin_name' => $this->pluginName,
                'page' => 'Tweede SPM Pagina',
                'is_status' => 'publish'   // draft | publish
            ) );

            $this->Probe( 'SPM.MovePage', '2 inside tweede pagina', array(
                'plugin_name' => $this->pluginName,
                'page' => 'WP Page 2',
                'destination' => 'Tweede SPM Pagina',
                'position' => 'inside',
            ) );

            $this->Probe( 'SPM.SubPageExist', 'tweede pagina has sub 2', array(
                'plugin_name' => $this->pluginName,
                'page' => 'Tweede SPM Pagina',
                'sub_page' => 'WP Page 2'
            ) );

            $this->Probe( 'SPM.DeletePage', '3', array(
                'plugin_name' => $this->pluginName,
                'page' => 'WP Page 3',
            ) );

            $this->Probe( 'SPM.XPagesExist', '5', array(
                'plugin_name' => $this->pluginName,
                'x_pages' => 5
            ) );

            $this->Probe( 'SPM.PublishPage', 'page last', array(
                'plugin_name' => $this->pluginName,
                'page' => 'SPM Page last',
            ) );

            $this->Probe( 'SPM.CheckPageStatus', 'page last = publish', array(
                'plugin_name' => $this->pluginName,
                'page' => 'SPM Page last',
                'is_status' => 'publish'   // draft | publish
            ) );

            $this->Probe( 'SPM.SavePage', 'page inside', array(
                'plugin_name' => $this->pluginName,
                'action' => 'add',
                'page' => 'SPM Page last',
                'values' => json_encode( array(
                    'post_title' => array(
                        'type' => 'text',
                        'value' => 'SPM Page inside'
                    ),
                    'add_mode' => array(
                        'type' => 'radio',
                        'value' => 'inside'   // after | inside
                    ),
                    'post_status' => array(
                        'type' => 'radio',
                        'value' => 'publish'   // draft | publish
                    )
                ) )
            ) );

            $this->Probe( 'SPM.SubPageExist', 'page last has sub page inside', array(
                'plugin_name' => $this->pluginName,
                'page' => 'SPM Page last',
                'sub_page' => 'SPM Page inside'
            ) );

            $this->Probe( 'SPM.XPagesExist', '6', array(
                'plugin_name' => $this->pluginName,
                'x_pages' => 6
            ) );

            if( $ss_mode === 'wp' ) {

                // dojh Add test for Edit Page button in SS mode

                $this->Probe( 'SPM.EditPageContent', '1 -> SPM Page 1', array(
                    'plugin_name' => $this->pluginName,
                    'page' => 'WP Page 1',
                    'values' => json_encode( array(
                        'post_title' => array(
                            'type' => 'text',
                            'value' => 'SPM Page 1'
                        )
                    ) )
                ) );

                $this->Probe( 'SPM.PageExists', 'SPM Page 1 at pos 1', array(
                    'plugin_name' => $this->pluginName,
                    'page' => 'SPM Page 1',
                    'x_pages' => 1,
                    'at_pos' => 1
                ) );

            } // if mode wp

        }
    }

    ////////////////////////////////////////
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();
