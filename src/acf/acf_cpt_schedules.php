<?php

namespace postplanpro\acf;

/**
 * This will generate the parent page for postplanpro with 
 * and icon as well as all of the sub-pages underneath.
 */
class acf_cpt_schedules
{

    public $singular = 'Schedule'; 
    public $plural = 'Schedules';

    public function __construct(){
        add_action( 'init', [$this, 'register_cpt'], 8 );
    }



    public function register_cpt() {


        $post_type = register_post_type( 
			$this->singular, 
			[
				'label'                 => $this->singular,
				'description'           => 'Custom Post Type',
				'labels'                => 	[
					'name'                  => $this->singular,
					'singular_name'         => $this->singular,
					'menu_name'             => $this->singular,
					'name_admin_bar'        => $this->plural,
					'archives'              => $this->singular . ' Archives',
					'attributes'            => $this->singular . ' Attributes',
					'parent_item_colon'     => $this->plural . ' :',
					'all_items'             => $this->plural,
					'add_new_item'          => 'Add New '.$this->singular,
					'add_new'               => 'Add New',
					'new_item'              => 'New '.$this->singular,
					'edit_item'             => 'Edit '.$this->singular,
					'update_item'           => 'Update '.$this->singular,
					'view_item'             => 'View '.$this->singular,
					'view_items'            => 'View '.$this->plural,
					'search_items'          => 'Search '.$this->plural,
					'not_found'             => 'Not found',
					'not_found_in_trash'    => 'Not found in Trash',
					'featured_image'        => 'Featured Image',
					'set_featured_image'    => 'Set featured image',
					'remove_featured_image' => 'Remove featured image',
					'use_featured_image'    => 'Use as featured image',
					'insert_into_item'      => 'Insert into '.$this->singular,
					'uploaded_to_this_item' => 'Uploaded to this '.$this->singular,
					'items_list'            => $this->plural . ' list',
					'items_list_navigation' => $this->plural . ' list navigation',
					'filter_items_list'     => 'Filter '.$this->plural.' list',
				],
				'supports'              => array( 'title' ),
				'hierarchical'          => true,
				'public'                => true,
				'show_ui'               => true,
				'show_in_menu'          => 'postplanpro',
				'show_in_rest' 			=> true,
				'menu_position'         => 1000,
				'show_in_admin_bar'     => true,
				'show_in_nav_menus'     => true,
				'can_export'            => true,
				'exclude_from_search'   => false,
				'publicly_queryable'    => true,
				'capability_type'       => 'page',
				'has_archive'           => false,
				'rewrite'               => $rewrite,
			] 
		);
    }


}



