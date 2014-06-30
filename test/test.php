<?php

include 'lib/ss_story.php';

////////////////////////////////////////

class ThisStory extends SSStory {
    protected $pluginName = 'Swifty Page Manager';

    function TakeAction() {
        parent::TakeAction();   // Must be called first!

        $this->wordpress->Login();

        $this->Probe( 'SPM.CheckRunning', array(
            "plugin_name" => $this->pluginName
        ) );

        $this->Probe( 'WP.DeleteAllPages', array(
            'plugin_name' => $this->pluginName
        ) );

        $this->Probe( 'WP.EmptyTrash', array(
            'plugin_name' => $this->pluginName
        ) );

        $this->Probe( 'SPM.NoPagesExist', array(
            'plugin_name' => $this->pluginName
        ) );

        $this->Probe( 'WP.CreateXDraftPages', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 4
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 4
        ) );

        $this->Probe( 'SPM.MovePage', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 1',
            'destination' => 'WP Page 4',
            'position'    => 'before',
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 1',
            'at_pos'      => 3
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 3',
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.MovePage', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 1',
            'destination' => 'WP Page 2',
            'position'    => 'before',
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 1',
            'at_pos'      => 1
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'  => 'WP Page 2',
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.MovePage', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 2',
            'destination' => 'WP Page 4',
            'position'    => 'after',
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 2',
            'at_pos'      => 4
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 4',
            'at_pos'      => 3
        ) );

        $this->Probe( 'SPM.MovePage', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 2',
            'destination' => 'WP Page 1',
            'position'    => 'after',
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 2',
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 4',
            'at_pos'      => 4
        ) );

        $this->Probe( 'SPM.SavePage', array(
            'plugin_name' => $this->pluginName,
            'action'      => 'add',
            'page'        => 'WP Page 4',
            'values'      => json_encode( array(
                'post_title' => array(
                    'type'  => 'text',
                    'value' => 'SPM Page last'
                ),
                'add_mode' => array(
                    'type'  => 'radio',
                    'value' => 'after'
                ),
                'post_status' => array(
                    'type'  => 'radio',
                    'value' => 'draft'
                ),
                'page_template' => array(
                    'type'  => 'select',
                    'value' => 'Full Width Page'
                )
            ) )
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 5
        ) );

        $this->Probe( 'SPM.SavePage', array(
            'plugin_name' => $this->pluginName,
            'action'      => 'add',
            'page'        => 'WP Page 1',
            'values'      => json_encode( array(
                'post_title' => array(
                    'type'  => 'text',
                    'value' => 'SPM Page second'
                ),
                'add_mode' => array(
                    'type'  => 'radio',
                    'value' => 'after'
                ),
                'post_status' => array(
                    'type'  => 'radio',
                    'value' => 'draft'
                )
            ) )
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 6
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'SPM Page last',
            'x_pages'     => 1,
            'at_pos'      => 6
        ) );

        $this->Probe( 'SPM.CheckPageStatus', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'SPM Page last',
            'is_status'   => 'draft'   // draft | publish
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'SPM Page second',
            'x_pages'     => 1,
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.SavePage', array(
            'plugin_name' => $this->pluginName,
            'action'      => 'edit',
            'page'        => 'SPM Page second',
            'values'      => json_encode( array(
                'post_title' => array(
                    'type'  => 'text',
                    'value' => 'Tweede SPM Pagina'
                ),
                'post_status' => array(
                    'type'  => 'radio',
                    'value' => 'publish'
                ),
                'page_template' => array(
                    'type'  => 'select',
                    'value' => 'Contributor Page'
                )
            ) )
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'Tweede SPM Pagina',
            'x_pages'     => 1,
            'at_pos'      => 2
        ) );

        $this->Probe( 'SPM.CheckPageStatus', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'Tweede SPM Pagina',
            'is_status'   => 'publish'   // draft | publish
        ) );

        $this->Probe( 'SPM.MovePage', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 2',
            'destination' => 'Tweede SPM Pagina',
            'position'    => 'inside',
        ) );

        $this->Probe( 'SPM.SubPageExist', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'Tweede SPM Pagina',
            'sub_page'    => 'WP Page 2'
        ) );

        $this->Probe( 'SPM.DeletePage', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'Tweede SPM Pagina',
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 5
        ) );

        $this->Probe( 'SPM.PublishPage', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'SPM Page last',
        ) );

        $this->Probe( 'SPM.CheckPageStatus', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'SPM Page last',
            'is_status'   => 'publish'   // draft | publish
        ) );

        $this->Probe( 'SPM.SavePage', array(
            'plugin_name' => $this->pluginName,
            'action'      => 'add',
            'page'        => 'SPM Page last',
            'values'      => json_encode( array(
                'post_title' => array(
                    'type'  => 'text',
                    'value' => 'SPM Page inside'
                ),
                'add_mode' => array(
                    'type'  => 'radio',
                    'value' => 'inside'   // after | inside
                ),
                'post_status' => array(
                    'type'  => 'radio',
                    'value' => 'publish'   // draft | publish
                )
            ) )
        ) );

        $this->Probe( 'SPM.SubPageExist', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'SPM Page last',
            'sub_page'    => 'SPM Page inside'
        ) );

        $this->Probe( 'SPM.XPagesExist', array(
            'plugin_name' => $this->pluginName,
            'x_pages'     => 6
        ) );

        $this->Probe( 'SPM.EditPageContent', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'WP Page 1',
            'values'      => json_encode( array(
                'post_title' => array(
                    'type'  => 'text',
                    'value' => 'SPM Page 1'
                )
            ) )
        ) );

        $this->Probe( 'SPM.PageExists', array(
            'plugin_name' => $this->pluginName,
            'page'        => 'SPM Page 1',
            'x_pages'     => 1,
            'at_pos'      => 1
        ) );
    }

    ////////////////////////////////////////
}

////////////////////////////////////////

$GLOBALS['ssStory'] = new ThisStory();

////////////////////////////////////////
