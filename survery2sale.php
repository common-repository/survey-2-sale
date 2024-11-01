<?php
/**
 * Plugin Name: Survey-2-Sale
 * Plugin URI: http://www.survery2sale.com/wordpress
 * Description: Making your visitors work for a discount creates a reason for them to spend. Survey-2-Sale packages this psychological technique into a nice, easy to install plugin for any website.
 * Version: 0.1
 * Author: Survey-2-Sale
 * Author URI: http://www.survey2sale.com/
 * License: GPL2
 */

/*  Copyright 2014  Crafty Codr Inc.  (email :  info@survey2sale.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

    add_action('admin_init', 's2s_plugin_init' );
    add_action( 'admin_menu', 's2s_plugin_menu' );


	function s2s_plugin_init() {
		//register our settings to white list our options
		register_setting( 's2s_plugin_options', 's2s_options', 's2s_plugin_validation' );
		// Register our stylesheet. 
       wp_register_style( 's2s_style', plugins_url('s2s_style.css', __FILE__) );
	}

	function s2s_plugin_menu() {
    	// add admin menu page
		$page = add_options_page( 'Survey-2-Sale Options', 'Survey-2-Sale', 'manage_options', 's2s_plugin', 's2s_plugin_page' );

       add_action( 'admin_print_styles-' . $page, 'load_s2s_style' );
	}

	function s2s_plugin_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'Uh oh.  You do not have sufficient permissions to access this page.' ) );
		}
	?>
		<div class="wrap">
			<form action="options.php" method="post" id="s2s">
				<?php settings_fields('s2s_plugin_options'); ?>
            	<?php $options = get_option('s2s_options'); ?>

            	<img src="<?php echo plugins_url('img/Survey2Sale-100px.png', __FILE__ ); ?>" />
            	<h1>Survey-2-Sale WordPress Settings</h1>

    			<script type="text/javascript">
    				// A $( document ).ready() block.
					jQuery( document ).ready(function() {
					    jQuery("#s2s_btn").click(function() {
					    	jQuery("#s2s_enable").val(
					    			<?php 
					    				if($options['s2s_enable']) {
					    					$options['s2s_enable'] = false;
					    				}
					    				else {
					    					$options['s2s_enable'] = true;
					    				}
					    				echo $options['s2s_enable'];
					    			?>
					    		);

					    	jQuery("#s2s").submit();
					    });
					});
    			</script>
    			<div class="s2s_enable_container">
    				<div class="s2s_enable_btn">
        				<input type="hidden" name="s2s_options[s2s_enable]" id="s2s_enable" value="<?php echo $options['s2s_enable']; ?>"  />
            			
            				<?php 
            					if($options['s2s_enable']) {
            				?>		<div class="button-primary s2s_submit_btn" id="s2s_btn">
            				<?php		_e('Enable Survey-2-Sale');
            				?>
									</div>
									<div class="s2s_enable_msg">
										<em>By enabling Survey-2-Sale on your site, you are agreeing that a "powered by Survey-2-Sale" <br />
		            						link will be included as part of the survey that is displayed to your users.
		            					</em>
		            				</div>
		            		<?php
            					}
            					else {
            				?>
            						<div class="button-primary s2s_submit_btn s2s_disable" id="s2s_btn">
            				<?php		_e('Disable Survey-2-Sale');	
            				?>
            						</div>
            				<?php
            					} 
            				?> 
            		</div>
	            </div>
        		
        		
		        <h3 class="s2s_header">Are you including or excluding pages?</h3>
		        <p>
		            You can specify specific pages to exclude (set as exclusion list)  <br />
		            or specify only certain pages where your survey is included (set as inclusion list)
		        </p>
		        <?php 
			        if(empty($options['list_type'])) { 
			        	$options['list_type'] = "exclusionary"; 
			        }
			    ?>
				<div class="s2s_inclusion_option"><input type="radio" name="s2s_options[list_type]" value="inclusionary" <?php checked('inclusionary', $options['list_type']); ?> /> Inclusion list</div>
                <div><input type="radio" name="s2s_options[list_type]" value="exclusionary" <?php checked('exclusionary', $options['list_type']); ?> /> Exclusion list </div>
				

                <h3 class="s2s_header s2s_list_header">Which pages are we talking about?</h3>
                <p>Enter one entry per line or comma separated (either the post/page id, or permalink title) </p>
                <textarea name="s2s_options[list_posts]" class="s2s_list_textarea"><?php echo $options['list_posts']; ?></textarea>
    			<p class="submit">
    				<input type="submit" class="button-primary s2s_submit_btn" value="<?php _e('Save Changes') ?>" />
    			</p>
			</form>
		</div>
		<?php 
	}

	function s2s_plugin_validation($input) 
	{
		// Take input from list_posts and validate it
		//  This means removing any spaces and cleaning up special characters
		// The cleaned up version will be saved
		$posts = preg_split( "/(\n)/", $input['list_posts']);
		$output = "";
		foreach($posts as $p)
		{
			$output .= filter_var(trim($p, ','), FILTER_SANITIZE_URL) . "\n";
		}
		$input['list_posts'] = rtrim($output);
		return $input;
	}

	function enqueue_s2s( $content )
	{
	    $options = get_option("s2s_options");

	    // Is the plugin enabled?
	    if($options['s2s_enable']) 
	    {
		    // Is list inclusionary or exclusionary
		    $list_type = $options['list_type'];

	        // Get list of posts/pages impacted
		    //  Parse the list by commas and line breaks
		    $list_posts = $options['list_posts'];
		    $posts = preg_split( "/(\n|,)/", $list_posts);

			// Get the current page and determine if it's on the 
			// list to display s2s script
			global $post;
			$post_id = $post->ID;
			$post_slug = $post->post_name;

			$in_list = false;

			foreach($posts as $p) 
			{
				if(!empty($p) && (strcmp($post_id, $p) == 0 || strcmp($post_slug, $p) == 0))
				{
					$in_list = true;
				} 
			}

			if(	$in_list == true && (strcmp($list_type, "inclusionary") == 0 ) ||
				$in_list == false && (strcmp($list_type, "exclusionary") == 0 ) )
			{
	            wp_enqueue_script(
					's2s',
					plugins_url( '/js/s2s.js' , __FILE__ ),
					null,
					false,
					true
				);
			}
		}
	}
	add_action( 'wp_enqueue_scripts', 'enqueue_s2s' );


	function load_s2s_style() {
		wp_enqueue_style( 's2s_style' );
	}