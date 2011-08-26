<?php

class TinyMCE extends Plugin {

	/**
	 * Respond to the submitted configure form
	 *
	 * @param FormUI $form The form that was submitted
	 * @return boolean Whether to save the returned values.
	 */
	public function updated_config( $form )
	{
		// set the options
		$editor = $form->controls['editor']->value;
		$options = array();
		$options[] = 'mode: "textareas"';
		if ( $editor == null || $editor == 'simple') {
			$options[] = 'theme: "simple"';
		}
		elseif ( $editor == 'advanced' ) {
			$options[] = 'theme: "advanced"';
		}
		elseif ( $editor == 'resizable' ) {
			$options[] = 'theme: "advanced"';
			// Add extra configuration options
			$options[] = 'theme_advanced_statusbar_location : "bottom",
										theme_advanced_resize_horizontal : false,
										theme_advanced_resizing : true';
		}
		$user = User::identify();
		$user->info->tinymce_options = implode( $options, ',' );
		$user->info->commit();

		// No need to save input values
		return false;
	}

	/**
	 * Implement the simple plugin configuration.
	 * @return FormUI The configuration form
	 */
	public function configure()
	{
		// Add extra configuration options
		$form = new FormUI( strtolower( get_class( $this ) ) );
		$form->append( 'select', 'editor', 'null', _t( 'Editor theme:' ) );
		$form->editor->options = array(
			'simple' => 'Simple Editor',
			'advanced' => 'Advanced Editor',
			'resizable' => 'Advanced Resizable Editor'
		);
		$form->append( 'submit', 'save', _t( 'Save' ) );
		$form->on_success(array($this, 'updated_config'));
		return $form->get();
	}

	/**
	 * Add the required javascript
	 *
	 * @param Theme $theme The admin theme
	 */
	public function action_admin_header( $theme )
	{
		if ( $theme->page == 'publish' ) {
			Stack::add( 'admin_header_javascript', $this->get_url() . '/tinymce/tiny_mce.js', 'tiny_mce' );
			$options = User::identify()->info->tinymce_options;
			if ( $options == '' ) {
				$options = 'mode: "textareas", theme: "simple"';
			}
			$js = <<<TINYMCE
			$('#content').removeAttr('for');
			tinyMCE.init({
				{$options}
			});

			habari.editor = {
				insertSelection: function(value) {
					tinyMCE.activeEditor.selection.setContent(tinyMCE.activeEditor.selection.getContent() + value);
				}
			}
TINYMCE;
			Stack::add('admin_header_javascript', $js, 'tinymce_init', 'tinymce');
		}
	}

	/**
	 * Remove the change checking from the content textarea
	 * @todo Need a better solution than this to the problem of incorrectly saying you're navigating away
	 *
	 * @param FormUI $form The publish form
	 * @param Post $post
	 */
	public function action_form_publish( $form, $post )
	{
		$key = array_search( 'check-change', $form->content->class );
		if ( $key !== FALSE ) {
			unset( $form->content->class[ $key ] );
		}
	}
}

?>
