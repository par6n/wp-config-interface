<?php
/**
 * Render options for user interface.
 *
 * @author 					Ehsaan
 * @package 				WPCFG
 * @subpackage 				Render
 */

class WPCFG_Render_Engine {
	/**
	 * Options to render
	 * @var 			array $options
	 */
	public $options;

	/**
	 * Construct the engine
	 *
	 * @return 			void
	 */
	public function __construct( $options, $tab = 'general' ) {
		$this->options = $options[ $tab ];
	}

	/**
	 * Render the interface.
	 *
	 * @return 			void
	 */
	public function render() {
		foreach ( $this->options as $key => $option ) {
			if ( $option['input'] != 'plain' ) {
				echo '<tr>';
				echo '<th scope="row">';
				echo '<label for="' . $key . '">' . $option[ 'name' ] . '</label>';
				echo '</th>';
				echo '<td>';
				call_user_func( array( $this, 'callback_' . $option[ 'input' ] ), $option );
				echo '</td>';
				echo '</tr>';
			} else {
				echo '<p>' . $option['desc'] . '</p>';
			}
		}
	}

	/**
	 * Callback for checkbox input
	 *
	 * @param 			array $option
	 * @return 			void
	 */
	public function callback_check( $option ) {
		$checked = ( $option['value'] == true ? ' checked' : '' );
		echo '<input type="hidden" name="wpcfg[' . $option['id'] . ']" value="0">';
		echo '<label><input id="' . $option['id'] . '" type="checkbox" value="1" name="wpcfg[' . $option['id'] . ']"' . $checked . '><span class="description">' . $option[ 'desc' ] . '</span></label>';
	}

	/**
	 * Callback for horizontal rule
	 *
	 * @param 			array $option
	 * @return 			void
	 */
	public function callback_hr( $option ) {
		echo '<hr>';
	}

	/**
	 * Callback for number input
	 *
	 * @param 			array $option
	 * @return 			void
	 */
	public function callback_number( $option ) {
		echo '<input type="number" name="wpcfg[' . $option['id'] . ']" value="' . $option['value'] . '" class="regluar-text ltr"><p class="description">' . $option[ 'desc' ] . '</p>';
	}

	/**
	 * Callback for select input
	 *
	 * @param 			array $option
	 * @return 			void
	 */
	public function callback_select( $option ) {
		echo '<select name="wpcfg[' . $option['id'] . ']">';
		foreach( $option['options'] as $key => $value ) {
			$selected = '';
			if ( $option['value'] == $key ) $selected = ' selected';

			echo '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}
		echo '</select><p class="description">' . $option['desc'] . '</p>';
	}

	/**
	 * Callback for regular input
	 *
	 * @param 			array $option
	 * @return 			void
	 */
	public function callback_text( $option ) {
		if ( ! isset( $option['value'] ) )
			$value = '';
		else
			$value = $option['value'];

		echo '<input type="text" name="wpcfg[' . $option['id'] . ']" class="regular-text" value="' . $value . '"><p class="description">' . $option['desc'] . '</p>';
	}

	/**
	 * Callback for hyper links
	 *
	 * @param 			array $option
	 * @return 			void
	 */
	public function callback_link( $option ) {
		$classes = '';
		if ( isset( $option['class'] ) )
			$classes = $option['class'];

		echo '<a href="' . $option['href'] . '" class="' . $classes . '">' . $option['caption'] . '</a><p class="description">' . $option['desc'] . '</p>';
	}
}